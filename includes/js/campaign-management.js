/**
 * JavaScript for campaign management functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const searchCampaign = document.getElementById('search-campaign');
    const sortCampaigns = document.getElementById('sort-campaigns');
    const filterStatus = document.getElementById('filter-status');
    const campaignsContainer = document.getElementById('campaigns-container');
    const campaignCards = document.querySelectorAll('.campaign-card');
    
    // Campaign add/edit form
    const campaignForm = document.getElementById('campaign-form');
    const campaignEditForm = document.getElementById('campaign-edit-form');
    
    // Get CSRF token from meta tag
    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltips.length) {
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
    }
    
    // Search functionality
    if (searchCampaign) {
        searchCampaign.addEventListener('input', filterCampaigns);
    }
    
    // Sort functionality
    if (sortCampaigns) {
        sortCampaigns.addEventListener('change', filterCampaigns);
    }
    
    // Filter functionality
    if (filterStatus) {
        filterStatus.addEventListener('change', filterCampaigns);
    }
    
    // Populate edit form when edit modal is opened
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.hasAttribute('data-edit-campaign') || e.target.closest('[data-edit-campaign]'))) {
            const button = e.target.hasAttribute('data-edit-campaign') ? e.target : e.target.closest('[data-edit-campaign]');
            const campaignId = button.getAttribute('data-campaign-id');
            
            // Create form data for the request
            const formData = new FormData();
            formData.append('action', 'get');
            formData.append('campaign_id', campaignId);
            formData.append('csrf_token', csrfToken);
            
            // Fetch campaign data from the CRUD endpoint
            fetch('../campaigns/campaign_management_crud.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.campaign) {
                    const campaign = data.campaign;
                    
                    // Set form values
                    document.getElementById('edit-campaign-id').value = campaign.id;
                    document.getElementById('edit-name').value = campaign.name;
                    document.getElementById('edit-base-url').value = campaign.base_url; // Base URL is now read-only
                    document.getElementById('edit-verification-frequency').value = campaign.verification_frequency;
                    document.getElementById('edit-status').value = campaign.status;
                } else {
                    showAlert('danger', data.message || 'Failed to load campaign data.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while fetching campaign data.');
            });
        }
        
        // Handle delete campaign button click
        if (e.target && (e.target.hasAttribute('data-delete-campaign') || e.target.closest('[data-delete-campaign]'))) {
            const button = e.target.hasAttribute('data-delete-campaign') ? e.target : e.target.closest('[data-delete-campaign]');
            const campaignId = button.getAttribute('data-campaign-id');
            const campaignName = button.getAttribute('data-campaign-name');
            
            // Set delete form values
            document.getElementById('delete-campaign-id').value = campaignId;
            document.getElementById('delete-campaign-name').textContent = campaignName;
        }
    });
    
    // Handle campaign delete form submission
    const deleteCampaignForm = document.getElementById('delete-campaign-form');
    if (deleteCampaignForm) {
        deleteCampaignForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Remove any previous alerts
            document.querySelectorAll('#delete-campaign-modal .alert').forEach(el => {
                el.remove();
            });
            
            // Get form data
            const formData = new FormData(deleteCampaignForm);
            
            // Debug form data
            console.log('Delete form data being sent:', {
                action: formData.get('action'),
                campaign_id: formData.get('campaign_id'),
                csrf_token: formData.get('csrf_token')
            });
            
            // Send AJAX request using jQuery
            $.ajax({
                url: '../campaigns/campaign_management_crud.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    console.log('Starting AJAX request to delete campaign...');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                },
                success: function(response, textStatus, xhr) {
                    console.log('Raw server response:', response);
                    
                    try {
                        // Ensure response is an object
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        console.log('Parsed response data:', data);
                        
                        if (data.success) {
                            console.log('Campaign deletion successful');
                            
                            // Add alert to the modal body
                            const modalBody = document.querySelector('#delete-campaign-modal .modal-body');
                            const successAlert = document.createElement('div');
                            successAlert.className = 'alert alert-success mt-3 text-center';
                            successAlert.textContent = data.message;
                            modalBody.appendChild(successAlert);
                            
                            // Disable delete button to prevent multiple submissions
                            const deleteButton = document.querySelector('#delete-campaign-form button[type="submit"]');
                            if (deleteButton) {
                                deleteButton.disabled = true;
                                deleteButton.innerHTML = 'Deleted';
                            }
                            
                            // Close modal and refresh page after delay
                            setTimeout(() => {
                                console.log('Closing modal and refreshing page...');
                                document.querySelector('#delete-campaign-modal button[data-bs-dismiss="modal"]').click();
                                window.location.reload();
                            }, 1500);
                        } else {
                            console.log('Campaign deletion failed:', data.message);
                            
                            // Add alert to the modal body
                            const modalBody = document.querySelector('#delete-campaign-modal .modal-body');
                            const errorAlert = document.createElement('div');
                            errorAlert.className = 'alert alert-danger mt-3 text-center';
                            errorAlert.textContent = data.message || 'An error occurred while deleting the campaign.';
                            modalBody.appendChild(errorAlert);
                        }
                    } catch (parseError) {
                        console.error('Error parsing JSON response:', parseError);
                        
                        // Show parse error in the modal
                        const modalBody = document.querySelector('#delete-campaign-modal .modal-body');
                        const errorAlert = document.createElement('div');
                        errorAlert.className = 'alert alert-danger mt-3 text-center';
                        errorAlert.textContent = 'Invalid response from server';
                        modalBody.appendChild(errorAlert);
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    console.error('AJAX request failed:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        textStatus: textStatus,
                        errorThrown: errorThrown
                    });
                    
                    // Show AJAX error in the modal
                    const modalBody = document.querySelector('#delete-campaign-modal .modal-body');
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger mt-3 text-center';
                    errorAlert.textContent = 'An error occurred while connecting to the server';
                    modalBody.appendChild(errorAlert);
                }
            });
        });
    }
    
    // Handle campaign form submission
    if (campaignForm) {
        campaignForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
            });
            
            // Remove any previous alerts
            document.querySelectorAll('#add-campaign-modal .alert').forEach(el => {
                el.remove();
            });
            
            // Get form data
            const formData = new FormData(campaignForm);
            
            // Debug form data before sending
            console.log('Form data being sent:', {
                action: formData.get('action'),
                campaign_name: formData.get('campaign_name'),
                base_url: formData.get('base_url'),
                verification_frequency: formData.get('verification_frequency'),
                status: formData.get('status'),
                csrf_token: formData.get('csrf_token')
            });
            
            // Send AJAX request using jQuery
            $.ajax({
                url: '../campaigns/campaign_management_crud.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    console.log('Starting AJAX request to create campaign...');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                },
                success: function(response, textStatus, xhr) {
                    console.log('Raw server response:', response);
                    
                    try {
                        // Ensure response is an object
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        console.log('Parsed response data:', data);
                        
                        if (data.success) {
                            console.log('Campaign creation successful');
                            
                            // Add alert to the modal body instead of the main page
                            const modalBody = document.querySelector('#add-campaign-modal .modal-body');
                            const successAlert = document.createElement('div');
                            successAlert.className = 'alert alert-success alert-dismissible fade show mt-3';
                            successAlert.innerHTML = `
                                ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            modalBody.prepend(successAlert);
                            
                            // Reset form
                            campaignForm.reset();
                            
                            // Close modal and refresh page after delay
                            setTimeout(() => {
                                document.querySelector('#add-campaign-modal button[data-bs-dismiss="modal"]').click();
                                window.location.reload();
                            }, 1500);
                        } else {
                            console.log('Campaign creation failed:', data.message);
                            
                            // Show validation errors
                            if (data.errors) {
                                console.log('Validation errors:', data.errors);
                                Object.keys(data.errors).forEach(field => {
                                    const errorId = `${field}-error`;
                                    const errorElement = document.getElementById(errorId);
                                    if (errorElement) {
                                        errorElement.textContent = data.errors[field];
                                        console.log(`Set error for ${field}:`, data.errors[field]);
                                    } else {
                                        console.log(`Error element not found for field: ${field} (looked for #${errorId})`);
                                        
                                        // If error element not found, create an alert in the modal
                                        const modalBody = document.querySelector('#add-campaign-modal .modal-body');
                                        const errorAlert = document.createElement('div');
                                        errorAlert.className = 'alert alert-danger mt-2';
                                        errorAlert.textContent = `${field}: ${data.errors[field]}`;
                                        modalBody.prepend(errorAlert);
                                    }
                                });
                            }
                            
                            // Show general error message
                            if (data.message) {
                                const modalBody = document.querySelector('#add-campaign-modal .modal-body');
                                const errorAlert = document.createElement('div');
                                errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                                errorAlert.innerHTML = `
                                    ${data.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                `;
                                modalBody.prepend(errorAlert);
                            }
                        }
                    } catch (parseError) {
                        console.error('Error parsing JSON response:', parseError);
                        
                        // Show parse error in the modal
                        const modalBody = document.querySelector('#add-campaign-modal .modal-body');
                        const errorAlert = document.createElement('div');
                        errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                        errorAlert.innerHTML = `
                            Invalid response from server
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        modalBody.prepend(errorAlert);
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    console.error('AJAX request failed:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        textStatus: textStatus,
                        errorThrown: errorThrown
                    });
                    
                    // Show AJAX error in the modal
                    const modalBody = document.querySelector('#add-campaign-modal .modal-body');
                    const errorAlert = document.createElement('div');
                    errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                    errorAlert.innerHTML = `
                        An error occurred while connecting to the server
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    `;
                    modalBody.prepend(errorAlert);
                }
            });
        });
    }
    
    // Handle campaign edit form submission
    if (campaignEditForm) {
        campaignEditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
            });
            
            // Remove any previous alerts
            document.querySelectorAll('#edit-campaign-modal .alert').forEach(el => {
                el.remove();
            });
            
            // Get form data
            const formData = new FormData(campaignEditForm);
            
            // Debug form data before sending
            console.log('Form data being sent:', {
                action: formData.get('action'),
                campaign_name: formData.get('campaign_name'),
                verification_frequency: formData.get('verification_frequency'),
                status: formData.get('status'),
                csrf_token: formData.get('csrf_token'),
                campaign_id: formData.get('campaign_id')
            });

            // Send AJAX request using jQuery
            $.ajax({
                url: '../campaigns/campaign_management_crud.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    console.log('Starting AJAX request to update campaign...');
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                },
                success: function(response, textStatus, xhr) {
                    console.log('Raw server response:', response);
                    console.log('Response status:', xhr.status);
                    console.log('Response headers:', xhr.getAllResponseHeaders());

                    try {
                        // Ensure response is an object
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        console.log('Parsed response data:', data);

                        if (data.success) {
                            console.log('Campaign update successful');
                            
                            // Add alert to the modal body instead of the main page
                            const modalBody = document.querySelector('#edit-campaign-modal .modal-body');
                            const successAlert = document.createElement('div');
                            successAlert.className = 'alert alert-success alert-dismissible fade show mt-3';
                            successAlert.innerHTML = `
                                ${data.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            modalBody.prepend(successAlert);
                            
                            // Close modal and refresh page after delay
                            setTimeout(() => {
                                console.log('Closing modal and refreshing page...');
                                document.querySelector('#edit-campaign-modal button[data-bs-dismiss="modal"]').click();
                                window.location.reload();
                            }, 1500);
                        } else {
                            console.log('Campaign update failed:', data.message);
                            // Show validation errors
                            if (data.errors) {
                                console.log('Validation errors:', data.errors);
                                Object.keys(data.errors).forEach(field => {
                                    // Match error element IDs to the field names
                                    const errorId = field === 'campaign_name' ? 'edit-campaign_name-error' : `edit-${field}-error`;
                                    const errorElement = document.querySelector(`#${errorId}`);
                                    if (errorElement) {
                                        errorElement.textContent = data.errors[field];
                                        console.log(`Set error for ${field}:`, data.errors[field]);
                                    } else {
                                        console.log(`Error element not found for field: ${field} (looked for #${errorId})`);
                                        
                                        // If error element not found, create an alert in the modal
                                        const modalBody = document.querySelector('#edit-campaign-modal .modal-body');
                                        const errorAlert = document.createElement('div');
                                        errorAlert.className = 'alert alert-danger mt-2';
                                        errorAlert.textContent = `${field}: ${data.errors[field]}`;
                                        modalBody.prepend(errorAlert);
                                    }
                                });
                            }
                            
                            // Show general error message
                            if (data.message) {
                                const modalBody = document.querySelector('#edit-campaign-modal .modal-body');
                                const errorAlert = document.createElement('div');
                                errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                                errorAlert.innerHTML = `
                                    ${data.message}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                `;
                                modalBody.prepend(errorAlert);
                            }
                        }
                    } catch (parseError) {
                        console.error('Error parsing JSON response:', parseError);
                        console.log('Raw response that failed to parse:', response);
                        
                        // Show parse error in the modal
                        const modalBody = document.querySelector('#edit-campaign-modal .modal-body');
                        const errorAlert = document.createElement('div');
                        errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                        errorAlert.innerHTML = `
                            Invalid response from server
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        modalBody.prepend(errorAlert);
                    }
                },
                error: function(xhr, textStatus, errorThrown) {
                    console.error('AJAX request failed:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        textStatus: textStatus,
                        errorThrown: errorThrown
                    });

                    // Try to parse response if it exists
                    if (xhr.responseText) {
                        try {
                            const errorResponse = JSON.parse(xhr.responseText);
                            
                            // Show error in the modal
                            const modalBody = document.querySelector('#edit-campaign-modal .modal-body');
                            const errorAlert = document.createElement('div');
                            errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                            errorAlert.innerHTML = `
                                ${errorResponse.message || 'Server error occurred'}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            modalBody.prepend(errorAlert);
                            
                            console.log('Parsed error response:', errorResponse);
                        } catch (e) {
                            console.error('Could not parse error response:', e);
                            console.log('Raw error response:', xhr.responseText);
                            
                            // Show general error in the modal
                            const modalBody = document.querySelector('#edit-campaign-modal .modal-body');
                            const errorAlert = document.createElement('div');
                            errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                            errorAlert.innerHTML = `
                                An unexpected error occurred
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            `;
                            modalBody.prepend(errorAlert);
                        }
                    } else {
                        // Show connection error in the modal
                        const modalBody = document.querySelector('#edit-campaign-modal .modal-body');
                        const errorAlert = document.createElement('div');
                        errorAlert.className = 'alert alert-danger alert-dismissible fade show mt-3';
                        errorAlert.innerHTML = `
                            Could not connect to server
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        modalBody.prepend(errorAlert);
                    }
                },
                complete: function(xhr, textStatus) {
                    console.log('Request completed with status:', textStatus);
                }
            });
        });
    }
    
    // Filter campaigns based on search, sort, and filter criteria
    function filterCampaigns() {
        const searchTerm = searchCampaign ? searchCampaign.value.toLowerCase() : '';
        const sortBy = sortCampaigns ? sortCampaigns.value : 'name-asc';
        const statusFilter = filterStatus ? filterStatus.value : 'all';
        
        // First filter by search term and status
        let filteredCampaigns = Array.from(campaignCards).filter(card => {
            const name = card.dataset.name || '';
            const baseUrl = card.dataset.baseUrl || '';
            const status = card.dataset.status || '';
            
            const matchesSearch = name.includes(searchTerm) || baseUrl.includes(searchTerm);
            const matchesStatus = statusFilter === 'all' || status === statusFilter;
            
            return matchesSearch && matchesStatus;
        });
        
        // Then sort the filtered campaigns
        filteredCampaigns.sort((a, b) => {
            const aName = a.dataset.name || '';
            const bName = b.dataset.name || '';
            
            if (sortBy === 'name-asc') {
                return aName.localeCompare(bName);
            } else if (sortBy === 'name-desc') {
                return bName.localeCompare(aName);
            }
            return 0;
        });
        
        // Hide all campaigns first
        campaignCards.forEach(card => {
            card.style.display = 'none';
        });
        
        // Show filtered and sorted campaigns
        filteredCampaigns.forEach(card => {
            card.style.display = '';
        });
        
        // Show message if no campaigns match
        if (filteredCampaigns.length === 0) {
            let noResultsElement = document.getElementById('no-results');
            if (!noResultsElement) {
                noResultsElement = document.createElement('div');
                noResultsElement.id = 'no-results';
                noResultsElement.className = 'col-12 text-center';
                noResultsElement.innerHTML = `
                    <div class="empty">
                        <div class="empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-mood-sad" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <circle cx="12" cy="12" r="9" />
                                <line x1="9" y1="10" x2="9.01" y2="10" />
                                <line x1="15" y1="10" x2="15.01" y2="10" />
                                <path d="M9.5 15.25a3.5 3.5 0 0 1 5 0" />
                            </svg>
                        </div>
                        <p class="empty-title">No campaigns found</p>
                        <p class="empty-subtitle text-muted">
                            Try adjusting your search or filter criteria
                        </p>
                    </div>
                `;
                campaignsContainer.appendChild(noResultsElement);
            }
        } else {
            const noResultsElement = document.getElementById('no-results');
            if (noResultsElement) {
                noResultsElement.remove();
            }
        }
    }
    
    // Function to show alert messages
    function showAlert(type, message) {
        const alertsContainer = document.getElementById('alerts-container');
        if (alertsContainer) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            alertsContainer.appendChild(alert);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                alert.classList.remove('show');
                setTimeout(() => {
                    alert.remove();
                }, 150);
            }, 5000);
        }
    }
}); 