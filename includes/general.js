/**
 * General JavaScript functions used across the site
 */

// Get CSRF token from meta tag
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// Add CSRF token to AJAX requests
document.addEventListener('DOMContentLoaded', function() {
    // Set up AJAX CSRF headers for jQuery if jQuery is present
    if (typeof jQuery !== 'undefined') {
        jQuery.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': getCsrfToken()
            }
        });
    }
    
    // Set up CSRF headers for fetch API
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Only add the CSRF token for same-origin requests
        if (new URL(url, window.location.href).origin === window.location.origin) {
            options.headers = options.headers || {};
            if (!(options.headers instanceof Headers)) {
                options.headers = new Headers(options.headers);
            }
            
            if (!options.headers.has('X-CSRF-TOKEN')) {
                options.headers.append('X-CSRF-TOKEN', getCsrfToken());
            }
        }
        
        return originalFetch(url, options);
    };
});

// Format date in user-friendly format
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

// Helper to show notifications (if a notification system is used)
function showNotification(message, type = 'info') {
    // Implementation depends on the notification system used
    if (console) {
        console.log(`[${type}] ${message}`);
    }
} 