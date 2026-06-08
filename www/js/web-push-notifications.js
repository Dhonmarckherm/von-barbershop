/**
 * Web Push Notification Service for VON BARBER STUDIO
 * Enables push notifications in web browsers and PWA
 * Works on Chrome, Firefox, Edge, and Safari
 * 
 * Color scheme: silver-black-white theme
 */

// Service Worker Registration
let swRegistration = null;
let isPushSupported = false;

/**
 * Initialize Web Push Notifications
 */
async function initWebPush() {
  // Check if service workers are supported
  if (!('serviceWorker' in navigator)) {
    console.log('Service Workers not supported');
    return;
  }

  // Check if Push API is supported
  if (!('PushManager' in window)) {
    console.log('Push API not supported');
    return;
  }

  try {
    // Register service worker
    swRegistration = await navigator.serviceWorker.register('/sw.js');
    console.log('Service Worker registered:', swRegistration);

    // Check current permission status
    const permission = await Notification.requestPermission();
    console.log('Notification permission:', permission);

    if (permission === 'granted') {
      isPushSupported = true;
      await subscribeToPush();
    } else {
      console.log('Notification permission denied');
    }
  } catch (error) {
    console.error('Error initializing web push:', error);
  }
}

/**
 * Subscribe to push notifications
 */
async function subscribeToPush() {
  if (!swRegistration) return;

  try {
    // Check for existing subscription
    let subscription = await swRegistration.pushManager.getSubscription();

    if (!subscription) {
      // Create new subscription
      subscription = await swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array('YOUR_VAPID_PUBLIC_KEY') // Replace with your VAPID key
      });

      console.log('Push subscription created:', subscription);
      
      // Send subscription to server
      await sendSubscriptionToServer(subscription);
    }

    isPushSupported = true;
    console.log('User subscribed to push notifications');
  } catch (error) {
    console.error('Error subscribing to push:', error);
  }
}

/**
 * Send subscription to server
 */
async function sendSubscriptionToServer(subscription) {
  try {
    const response = await fetch('/api/save_push_subscription.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        subscription: subscription,
        user_id: getUserId() // Get from session or cookie
      })
    });

    if (response.ok) {
      console.log('Subscription saved to server');
    }
  } catch (error) {
    console.error('Error saving subscription:', error);
  }
}

/**
 * Request notification permission
 */
async function requestNotificationPermission() {
  if (!('Notification' in window)) {
    console.log('Notifications not supported');
    return false;
  }

  const permission = await Notification.requestPermission();
  
  if (permission === 'granted') {
    isPushSupported = true;
    await subscribeToPush();
    return true;
  }

  return false;
}

/**
 * Show local notification (fallback for web)
 * @param {string} title - Notification title
 * @param {string} body - Notification body
 * @param {string} icon - Icon URL
 * @param {string} url - URL to open when clicked
 */
function showWebNotification(title, body, icon = '/assets/images/rubiks.jpg', url = '/my_appointments.php') {
  if (Notification.permission === 'granted') {
    const notification = new Notification(title, {
      body: body,
      icon: icon,
      badge: '/assets/images/rubiks.jpg',
      tag: 'von-barbershop-notification',
      requireInteraction: false,
      actions: [
        {
          action: 'view',
          title: 'View',
          icon: '/assets/images/rubiks.jpg'
        }
      ]
    });

    notification.onclick = function(event) {
      event.preventDefault();
      window.open(url, '_blank');
      notification.close();
    };

    notification.onclose = function() {
      console.log('Notification closed');
    };

    notification.onerror = function(error) {
      console.error('Notification error:', error);
    };
  } else if (Notification.permission === 'default') {
    // Permission not yet requested
    requestNotificationPermission().then(granted => {
      if (granted) {
        showWebNotification(title, body, icon, url);
      }
    });
  }
}

/**
 * Utility: Convert URL-safe base64 to Uint8Array
 */
function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

/**
 * Get user ID from session/cookie
 */
function getUserId() {
  // Try to get from cookie or session storage
  const cookies = document.cookie.split(';');
  for (let cookie of cookies) {
    const [name, value] = cookie.trim().split('=');
    if (name === 'auth_user_id') {
      return value;
    }
  }
  return null;
}

/**
 * Send notification to specific user (server-side function)
 * This would be called from PHP backend
 */
async function sendNotificationToUser(userId, title, body, url = '/my_appointments.php') {
  try {
    const response = await fetch('/api/send_push_notification.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        user_id: userId,
        title: title,
        body: body,
        url: url
      })
    });

    return await response.json();
  } catch (error) {
    console.error('Error sending notification:', error);
    return { success: false, error: error.message };
  }
}

// Make functions available globally
window.initWebPush = initWebPush;
window.showWebNotification = showWebNotification;
window.requestNotificationPermission = requestNotificationPermission;
window.isWebPushSupported = () => isPushSupported;

// Initialize on page load
if ('serviceWorker' in navigator && 'PushManager' in window) {
  document.addEventListener('DOMContentLoaded', initWebPush);
}
