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
                    challenge: Uint8Array.from(options.challenge, c => c.charCodeAt(0)),
                    rp: {
                        name: 'VON BARBER STUDIO',
                        id: window.location.hostname
                    },
                    user: {
                        id: Uint8Array.from(options.user_id, c => c.charCodeAt(0)),
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
            const verifyResponse = await fetch('/api/biometric_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'register',
                    credential: {
                        id: credential.id,
                        rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))),
                        response: {
                            attestationObject: btoa(String.fromCharCode(...new Uint8Array(credential.response.attestationObject))),
                            clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON)))
                        },
                        type: credential.type
                    }
                })
            });

            const result = await verifyResponse.json();
            
            if (result.success) {
                return { success: true, message: 'Biometric login enabled!' };
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
            // Get challenge from server
            const response = await fetch('/api/biometric_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_challenge' })
            });
            
            const options = await response.json();
            
            if (options.error) {
                throw new Error(options.error);
            }

            // Get credential using device biometrics
            const assertion = await navigator.credentials.get({
                publicKey: {
                    challenge: Uint8Array.from(options.challenge, c => c.charCodeAt(0)),
                    allowCredentials: options.allowCredentials.map(cred => ({
                        id: Uint8Array.from(atob(cred.id), c => c.charCodeAt(0)),
                        type: 'public-key',
                        transports: cred.transports
                    })),
                    timeout: 60000,
                    userVerification: 'required'
                }
            });

            // Verify credential with server
            const verifyResponse = await fetch('/api/biometric_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'login',
                    assertion: {
                        id: assertion.id,
                        rawId: btoa(String.fromCharCode(...new Uint8Array(assertion.rawId))),
                        response: {
                            authenticatorData: btoa(String.fromCharCode(...new Uint8Array(assertion.response.authenticatorData))),
                            clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(assertion.response.clientDataJSON))),
                            signature: btoa(String.fromCharCode(...new Uint8Array(assertion.response.signature))),
                            userHandle: assertion.response.userHandle ? 
                                btoa(String.fromCharCode(...new Uint8Array(assertion.response.userHandle))) : null
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
