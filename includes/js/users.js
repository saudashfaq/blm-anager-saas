/**
 * Common JavaScript functions for the users section
 */
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete user confirmation
    const deleteLinks = document.querySelectorAll('a[href^="delete.php"]');
    if (deleteLinks) {
        deleteLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this user?')) {
                    e.preventDefault();
                }
            });
        });
    }
}); 