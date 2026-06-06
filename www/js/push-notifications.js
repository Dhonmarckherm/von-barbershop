/**
 * Push Notification Module for VON BARBER STUDIO
 * Uses Capacitor Local Notifications plugin
 * Color scheme: silver-black-white theme
 */

// Initialize Capacitor Local Notifications
const { LocalNotifications } = Capacitor.Plugins;

/**
 * Initialize push notifications on app launch
 */
async function initPushNotifications() {
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
  }
}

/**
 * Check if running in native Capacitor app
 */
function isCapacitorNative() {
  return typeof Capacitor !== 'undefined' && Capacitor.isNativePlatform && Capacitor.isNativePlatform();
}

// Make functions available globally
window.showNotification = showNotification;
window.isCapacitorNative = isCapacitorNative;

// Initialize on DOM ready
if (typeof Capacitor !== 'undefined') {
  document.addEventListener('DOMContentLoaded', initPushNotifications);
}
