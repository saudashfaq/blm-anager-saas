/**
 * JavaScript for profile management functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Clear previous messages
            const alertContainer = document.getElementById('alert-container');
            const alertMessage = document.getElementById('alert-message');
            const errorMessages = document.querySelectorAll('.error-message');
            
            alertContainer.style.display = 'none';
            errorMessages.forEach(msg => {
                msg.style.display = 'none';
                msg.textContent = '';
            });

            // Use Fetch API for form submission
            fetch('update_profile.php', {
                method: 'POST',
                body: new FormData(profileForm),
            })
            .then(response => response.json())
            .then(data => {
                alertMessage.classList.remove('alert-success', 'alert-danger');

                if (data.success) {
                    alertMessage.classList.add('alert-success');
                    alertMessage.innerHTML = data.message + ' <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    alertContainer.style.display = 'block';
                } else {
                    alertMessage.classList.add('alert-danger');
                    alertMessage.innerHTML = data.message + ' <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    alertContainer.style.display = 'block';

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
                alertMessage.classList.remove('alert-success');
                alertMessage.classList.add('alert-danger');
                alertMessage.innerHTML = 'An error occurred while processing your request. Please try again. ' + 
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                alertContainer.style.display = 'block';
            });
        });
    }
}); 