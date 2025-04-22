/**
 * JavaScript for the user form functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submission with AJAX
    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Clear previous messages
            const alertDiv = document.querySelector('.alert');
            const alertMessage = document.getElementById('alert-message');
            const errorMessages = document.querySelectorAll('.error-message');
            
            alertDiv.style.display = 'none';
            errorMessages.forEach(msg => {
                msg.style.display = 'none';
                msg.textContent = '';
            });

            // Use Fetch API for form submission
            fetch('save.php', {
                method: 'POST',
                body: new FormData(userForm),
            })
            .then(response => response.json())
            .then(data => {
                // Update alert classes and message
                alertDiv.classList.remove('alert-success', 'alert-danger');

                if (data.success) {
                    // Show success message
                    alertDiv.classList.add('alert-success');
                    alertMessage.textContent = data.message;
                    alertDiv.style.display = 'block';

                    // Redirect to index.php after a short delay
                    setTimeout(function() {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    // Show error message
                    alertDiv.classList.add('alert-danger');
                    alertMessage.textContent = data.message;
                    alertDiv.style.display = 'block';

                    // Display validation errors if any
                    if (data.errors) {
                        Object.entries(data.errors).forEach(([field, errors]) => {
                            const inputField = document.querySelector(`[name="${field}"]`);
                            if (inputField) {
                                const errorContainer = inputField.nextElementSibling;
                                if (errorContainer && errorContainer.classList.contains('error-message')) {
                                    errorContainer.textContent = errors[0];
                                    errorContainer.style.display = 'block';
                                }
                            }
                        });
                    }
                }
            })
            .catch(error => {
                // Handle server errors
                alertDiv.classList.remove('alert-success');
                alertDiv.classList.add('alert-danger');
                alertMessage.textContent = 'An error occurred while processing your request. Please try again. ' + error;
                alertDiv.style.display = 'block';
            });
        });
    }
}); 