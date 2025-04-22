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
    
    // Handle campaign form submission
    if (campaignForm) {
        campaignForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
            });
            
            // Get form data
            const formData = new FormData(campaignForm);
            
            // Send AJAX request
            fetch('campaign_management_crud.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('success', data.message);
                    
                    // Close modal and refresh page after delay
                    setTimeout(() => {
                        document.querySelector('#add-campaign-modal .btn-close').click();
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errorElement = document.querySelector(`#${field}-error`);
                            if (errorElement) {
                                errorElement.textContent = data.errors[field];
                            }
                        });
                    }
                    
                    // Show general error message
                    if (data.message) {
                        showAlert('danger', data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while processing your request.');
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
            
            // Get form data
            const formData = new FormData(campaignEditForm);
            
            // Send AJAX request
            fetch('campaign_management_crud.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert('success', data.message);
                    
                    // Close modal and refresh page after delay
                    setTimeout(() => {
                        document.querySelector('#edit-campaign-modal .btn-close').click();
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errorElement = document.querySelector(`#edit-${field}-error`);
                            if (errorElement) {
                                errorElement.textContent = data.errors[field];
                            }
                        });
                    }
                    
                    // Show general error message
                    if (data.message) {
                        showAlert('danger', data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while processing your request.');
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
    
    // Populate edit form when edit modal is opened
    document.addEventListener('click', function(e) {
        if (e.target && e.target.hasAttribute('data-edit-campaign')) {
            const campaignId = e.target.getAttribute('data-campaign-id');
            
            // Fetch campaign data
            fetch(`campaign_management_crud.php?action=get&id=${campaignId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.campaign) {
                        const campaign = data.campaign;
                        
                        // Set form values
                        document.getElementById('edit-campaign-id').value = campaign.id;
                        document.getElementById('edit-name').value = campaign.name;
                        document.getElementById('edit-base-url').value = campaign.base_url;
                        document.getElementById('edit-verification-frequency').value = campaign.verification_frequency;
                        document.getElementById('edit-status').value = campaign.status;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred while fetching campaign data.');
                });
        }
    });
}); 