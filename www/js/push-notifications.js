/**
 * Push Notification Module for VON BARBER STUDIO
 * Uses Capacitor Local Notifications plugin
 * Color scheme: silver-black-white theme
 * 
 * NOTE: Push notifications only work in native mobile apps (Android/iOS)
 * In web browsers, this module gracefully disables itself.
 */

// Check if running in native Capacitor app
function isCapacitorNative() {
  return typeof Capacitor !== 'undefined' && Capacitor.isNativePlatform && Capacitor.isNativePlatform();
}

// Initialize Capacitor plugins ONLY in native mode
let LocalNotifications = null;

if (isCapacitorNative()) {
  try {
    LocalNotifications = Capacitor.Plugins.LocalNotifications;
  } catch (error) {
    console.warn('Capacitor LocalNotifications plugin not available:', error.message);
  }
}

/**
 * Initialize push notifications on app launch
 * Only works in native mobile apps, skipped in web browsers
 */
async function initPushNotifications() {
  // Skip if not in native mode
  if (!isCapacitorNative() || !LocalNotifications) {
    console.log('Push notifications disabled (web browser mode)');
    return;
  }
  
  try {
    // Request notification permission
    const granted = await LocalNotifications.requestPermissions();
    
    if (granted.display === 'granted' || granted.display === 'prompt' || granted.display === 'prompt-with-ray') {
      console.log('Push notifications enabled');
      setupNotificationListeners();
    } else {
      console.log('Push notifications permission denied');
    }
  } catch (error) {
    console.error('Error initializing push notifications:', error);
  }
}

/**
 * Schedule a local notification
 * @param {string} title - Notification title
 * @param {string} body - Notification body text
 * @param {number} id - Unique notification ID
 * @param {string} iconColor - Icon color (default: silver #c0c0c0)
 */
async function scheduleNotification(title, body, id, iconColor = '#c0c0c0') {
  if (!isCapacitorNative() || !LocalNotifications) {
    console.log('Notification skipped (web browser):', title);
    return;
  }
  
  try {
    await LocalNotifications.schedule({
      notifications: [{
        title: title,
        body: body,
        id: id,
        schedule: { at: new Date(Date.now() + 1000) }, // Show immediately
        smallIcon: 'ic_notification',
        iconColor: iconColor,
        sound: 'default',
        actionTypeId: '',
        extra: null
      }]
    });
    console.log('Notification scheduled:', title);
  } catch (error) {
    console.error('Error scheduling notification:', error);
  }
}

/**
 * Setup notification event listeners
 */
function setupNotificationListeners() {
  if (!isCapacitorNative() || !LocalNotifications) {
    return;
  }
  
  // Listen for notification received while app is in foreground
  LocalNotifications.addListener('localNotificationReceived', (notification) => {
    console.log('Notification received:', notification);
  });
  
  // Listen for notification tap action
  LocalNotifications.addListener('localNotificationActionPerformed', (action) => {
    console.log('Notification tapped:', action);
    // Navigate to appointments page when notification is tapped
    window.location.href = '/my_appointments.php';
  });
}

/**
 * Show notification (global function for use in other scripts)
 * @param {string} title - Notification title
 * @param {string} body - Notification body text
 * @param {number} id - Unique notification ID
 */
function showNotification(title, body, id) {
  if (isCapacitorNative()) {
    scheduleNotification(title, body, id);
  } else {
    console.log('Push notification skipped (browser):', title);
  }
}

// Make functions available globally
window.showNotification = showNotification;
window.isCapacitorNative = isCapacitorNative;

// Initialize on DOM ready - only in native mode
if (isCapacitorNative()) {
  document.addEventListener('DOMContentLoaded', initPushNotifications);
}
