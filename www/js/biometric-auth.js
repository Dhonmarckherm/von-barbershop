/**
 * WebAuthn Biometric Authentication Helper
 * Enables fingerprint/face login for supported browsers and devices
 * 
 * This uses the Web Authentication API (WebAuthn) which is supported by:
 * - Chrome/Edge on Windows (Windows Hello)
 * - Safari on macOS/iOS (Touch ID / Face ID)
 * - Chrome on Android (Fingerprint)
 * - Any device with biometric support
 */

// Helper functions for base64url encoding/decoding
function base64urlToBuffer(base64url) {
    if (!base64url || typeof base64url !== 'string') {
        throw new Error('Invalid base64url input: ' + JSON.stringify(base64url));
    }
    
    // Normalize: remove padding and convert to base64url format
    let normalized = base64url.trim();
    normalized = normalized.replace(/=/g, ''); // Remove padding
    normalized = normalized.replace(/\+/g, '-'); // Convert + to -
    normalized = normalized.replace(/\//g, '_'); // Convert / to _
    
    // Validate base64url characters (A-Z, a-z, 0-9, -, _)
    if (!/^[A-Za-z0-9_-]+$/.test(normalized)) {
        console.error('[Base64url] Invalid characters in:', base64url, 'Normalized:', normalized);
        throw new Error('Base64url string contains invalid characters: ' + base64url);
    }
    
    const base64 = normalized.replace(/-/g, '+').replace(/_/g, '/');
    const pad = base64.length % 4;
    const padded = pad ? base64 + '='.repeat(4 - pad) : base64;
    
    try {
        const binary = atob(padded);
        const buffer = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            buffer[i] = binary.charCodeAt(i);
        }
        return buffer;
    } catch (e) {
        console.error('[Base64url] Failed to decode:', { original: base64url, normalized, converted: padded });
        throw new Error('Failed to decode base64url: ' + e.message);
    }
}

function stringToBuffer(str) {
    const buffer = new Uint8Array(str.length);
    for (let i = 0; i < str.length; i++) {
        buffer[i] = str.charCodeAt(i);
    }
    return buffer;
}

function bufferToBase64url(buffer) {
    let binary = '';
    for (let i = 0; i < buffer.length; i++) {
        binary += String.fromCharCode(buffer[i]);
    }
    const base64 = btoa(binary);
    return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

const BiometricAuth = {
    /**
     * Check if biometric authentication is supported
     */
    isSupported() {
        return window.PublicKeyCredential !== undefined && 
               navigator.credentials !== undefined;
    },

    /**
     * Check if device has biometric capability
     */
    async isBiometricAvailable() {
        if (!this.isSupported()) return false;
        
        try {
            return await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        } catch (err) {
            console.log('Biometric check failed:', err);
            return false;
        }
    },

    /**
     * Register a new biometric credential (after first login)
     */
    async register(email, userId) {
        try {
            // Get challenge from server
            const response = await fetch('/api/biometric_register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, user_id: userId })
            });
            
            const options = await response.json();
            
            if (options.error) {
                throw new Error(options.error);
            }

            // Create credential using device biometrics
            const credential = await navigator.credentials.create({
                publicKey: {
                    challenge: base64urlToBuffer(options.challenge),
                    rp: {
                        name: 'VON BARBER STUDIO',
                        id: window.location.hostname
                    },
                    user: {
                        id: stringToBuffer(String(options.user_id)),
                        name: options.email,
                        displayName: options.display_name
                    },
                    pubKeyCredParams: [
                        { type: 'public-key', alg: -7 },  // ES256
                        { type: 'public-key', alg: -257 } // RS256
                    ],
                    authenticatorSelection: {
                        authenticatorAttachment: 'platform', // Use device biometrics
                        userVerification: 'required',
                        requireResidentKey: false
                    },
                    timeout: 60000,
                    attestation: 'none'
                }
            });

            // Send credential to server for storage
            console.log('[Biometric Register] About to convert rawId...');
            console.log('[Biometric Register] credential.rawId type:', typeof credential.rawId);
            console.log('[Biometric Register] credential.rawId is ArrayBuffer:', credential.rawId instanceof ArrayBuffer);
            console.log('[Biometric Register] credential.rawId.byteLength:', credential.rawId.byteLength);
            
            const rawIdBytes = new Uint8Array(credential.rawId);
            console.log('[Biometric Register] rawIdBytes length:', rawIdBytes.length);
            console.log('[Biometric Register] rawIdBytes:', rawIdBytes);
            
            const credentialIdBase64url = bufferToBase64url(rawIdBytes);
            console.log('[Biometric Register] === CREDENTIAL INFO ===');
            console.log('[Biometric Register] credential.id (short):', credential.id);
            console.log('[Biometric Register] credential.id length:', credential.id.length);
            console.log('[Biometric Register] Converted to base64url:', credentialIdBase64url);
            console.log('[Biometric Register] base64url length:', credentialIdBase64url.length);
            console.log('[Biometric Register] base64url preview:', credentialIdBase64url.substring(0, 50));
            
            const verifyResponse = await fetch('/api/biometric_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'register',
                    credential: {
                        id: credentialIdBase64url,
                        rawId: credentialIdBase64url,
                        response: {
                            attestationObject: bufferToBase64url(new Uint8Array(credential.response.attestationObject)),
                            clientDataJSON: bufferToBase64url(new Uint8Array(credential.response.clientDataJSON))
                        },
                        type: credential.type
                    }
                })
            });

            const result = await verifyResponse.json();
            console.log('[Biometric Register] Server response:', result);
            
            if (result.success) {
                console.log('[Biometric Register] SUCCESS! Credential ID length stored:', credentialIdBase64url.length);
                return { success: true, message: 'Biometric login enabled!', credentialLength: credentialIdBase64url.length };
            } else {
                throw new Error(result.error || 'Registration failed');
            }
        } catch (err) {
            console.error('Biometric registration error:', err);
            return { success: false, error: err.message };
        }
    },

    /**
     * Login using biometric credential
     */
    async login() {
        try {
            console.log('[Biometric Login] Starting login process...');
            
            // Get challenge from server
            const response = await fetch('/api/biometric_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_challenge' })
            });
            
            const options = await response.json();
            console.log('[Biometric Login] Server response:', options);
            console.log('[Biometric Login] allowCredentials count:', options.allowCredentials?.length || 0);
            
            if (options.error) {
                throw new Error(options.error);
            }

            console.log('[Biometric Login] Requesting biometric authentication...');
            console.log('[Biometric Login] Credentials from server:', JSON.stringify(options.allowCredentials, null, 2));
            
            // Get credential using device biometrics
            // iOS Safari fix: Don't specify authenticatorAttachment to allow all types
            const getOptions = {
                publicKey: {
                    challenge: base64urlToBuffer(options.challenge),
                    allowCredentials: options.allowCredentials.map(cred => {
                        console.log('[Biometric Login] Processing credential ID:', cred.id);
                        console.log('[Biometric Login] Credential ID length:', cred.id.length);
                        console.log('[Biometric Login] Credential ID matches pattern:', /^[A-Za-z0-9_-]+$/.test(cred.id));
                        
                        return {
                            id: base64urlToBuffer(cred.id),
                            type: 'public-key',
                            // iOS Safari fix: Include all transports
                            transports: cred.transports || ['internal', 'hybrid']
                        };
                    }),
                    timeout: 60000,
                    userVerification: 'required',
                    // iOS Safari fix: Don't restrict to platform authenticator
                    rpId: window.location.hostname
                }
            };
            
            console.log('[Biometric Login] Getting credential with options:', getOptions);
            
            const assertion = await navigator.credentials.get(getOptions);
            
            console.log('[Biometric Login] Biometric verified! Assertion ID:', assertion.id);

            // Verify credential with server
            const verifyResponse = await fetch('/api/biometric_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'login',
                    assertion: {
                        id: assertion.id,
                        rawId: bufferToBase64url(new Uint8Array(assertion.rawId)),
                        response: {
                            authenticatorData: bufferToBase64url(new Uint8Array(assertion.response.authenticatorData)),
                            clientDataJSON: bufferToBase64url(new Uint8Array(assertion.response.clientDataJSON)),
                            signature: bufferToBase64url(new Uint8Array(assertion.response.signature)),
                            userHandle: assertion.response.userHandle ? 
                                bufferToBase64url(new Uint8Array(assertion.response.userHandle)) : null
                        },
                        type: assertion.type
                    }
                })
            });

            const result = await verifyResponse.json();
            
            if (result.success) {
                // Login successful - redirect to appropriate page
                if (result.role === 'admin' || result.role === 'barber') {
                    window.location.href = '/admin_dashboard.php';
                } else {
                    window.location.href = '/my_appointments.php';
                }
                return { success: true };
            } else {
                throw new Error(result.error || 'Login failed');
            }
        } catch (err) {
            console.error('Biometric login error:', err);
            return { success: false, error: err.message };
        }
    },

    /**
     * Remove biometric credential
     */
    async remove() {
        try {
            const response = await fetch('/api/biometric_remove.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            
            const result = await response.json();
            return result;
        } catch (err) {
            console.error('Remove biometric error:', err);
            return { success: false, error: err.message };
        }
    }
};

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BiometricAuth;
}
