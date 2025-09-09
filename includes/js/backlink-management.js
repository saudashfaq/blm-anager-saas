/**
 * JavaScript for backlink management functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const selectAllCheckbox = document.getElementById('select-all');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const backlinksTable = document.querySelector('.table');
    const filterForm = document.getElementById('filter-form');
    const addBacklinkForm = document.getElementById('add-backlink-form');
    const editBacklinkForm = document.getElementById('edit-backlink-form');
    const deleteBacklinkButtons = document.querySelectorAll('[data-action="delete-backlink"]');
    const campaignSelector = document.getElementById('campaign-selector');
    
    // Tooltips: rely on native browser title attribute; no external library needed
    
    // Handle campaign selection change
    if (campaignSelector) {
        campaignSelector.addEventListener('change', function() {
            const campaignId = this.value;
            if (campaignId) {
                window.location.href = `backlink_management.php?campaign_id=${campaignId}`;
            }
        });
    }
    
    // Handle filter form submission
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(filterForm);
            const filterType = formData.get('filter_type');
            const filterValue = formData.get('filter_value');
            const campaignId = formData.get('campaign_id');
            
            // Redirect to filtered view
            window.location.href = `backlink_management.php?campaign_id=${campaignId}&filter_type=${filterType}&filter_value=${filterValue}`;
        });
    }
    
    // Handle select all checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.backlink-select');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });
    }

    // Handle individual checkbox changes (change + click for robustness)
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('backlink-select')) {
            updateBulkDeleteButton();
            
            // Update select all checkbox state
            const allCheckboxes = document.querySelectorAll('.backlink-select');
            const checkedCheckboxes = document.querySelectorAll('.backlink-select:checked');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
            }
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('backlink-select')) {
            updateBulkDeleteButton();
        }
    });

    // Also respond to input events (covers some browsers/assistive tech)
    document.addEventListener('input', function(e) {
        if (e.target && e.target.classList && e.target.classList.contains('backlink-select')) {
            updateBulkDeleteButton();
        }
    });

    // Attach direct listeners as a fallback (in case delegation is blocked)
    const rowCheckboxes = document.querySelectorAll('.backlink-select');
    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkDeleteButton);
        cb.addEventListener('click', updateBulkDeleteButton);
    });

    // Update bulk delete button state
    function updateBulkDeleteButton() {
        const selectedCheckboxes = document.querySelectorAll('.backlink-select:checked');
        if (bulkDeleteBtn) {
            const shouldDisable = selectedCheckboxes.length === 0;
            bulkDeleteBtn.disabled = shouldDisable;
            if (shouldDisable) {
                bulkDeleteBtn.setAttribute('disabled', 'disabled');
            } else {
                bulkDeleteBtn.removeAttribute('disabled');
            }
        }
    }

    // Initialize button state on load
    updateBulkDeleteButton();

    // Handle bulk delete
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const selectedIds = Array.from(document.querySelectorAll('.backlink-select:checked'))
                .map(checkbox => checkbox.value);

            if (selectedIds.length === 0) {
                showAlert('warning', 'Please select backlinks to delete.');
                return;
            }

            if (confirm('Are you sure you want to delete the selected backlinks?')) {
                const formData = new FormData();
                formData.append('action', 'bulk_delete');
                formData.append('ids', JSON.stringify(selectedIds));
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

                fetch('backlink_management_crud.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert('danger', data.message || 'An error occurred while deleting the backlinks.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred while processing your request.');
                });
            }
        });
    }

    // Handle single delete
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.closest('.delete-single') || e.target.matches('.delete-single *'))) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.closest('.delete-single');
            const backlinkId = button.dataset.id;

            if (confirm('Are you sure you want to delete this backlink?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', backlinkId);
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

                fetch('backlink_management_crud.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', data.message);
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showAlert('danger', data.message || 'An error occurred while deleting the backlink.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred while processing your request.');
                });
            }
        }
    });
    
    // Handle add backlink form submission
    if (addBacklinkForm) {
        addBacklinkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
            });
            
            // Get form data
            const formData = new FormData(addBacklinkForm);
            
            // Send AJAX request
            fetch(addBacklinkForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        document.querySelector('#add-backlink-modal .btn-close').click();
                        window.location.reload();
                    }, 1500);
                } else {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errorElement = document.querySelector(`[name="${field}"] + .error-message`);
                            if (errorElement) {
                                errorElement.textContent = data.errors[field];
                            }
                        });
                    }
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
    
    // Handle edit backlink form submission
    if (editBacklinkForm) {
        editBacklinkForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous error messages
            document.querySelectorAll('.error-message').forEach(el => {
                el.textContent = '';
            });
            
            // Get form data
            const formData = new FormData(editBacklinkForm);
            
            // Send AJAX request
            fetch('backlink_management_crud.php', {
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
                        document.querySelector('#edit-backlink-modal .btn-close').click();
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
    
    // Populate edit form when edit modal is opened
    document.addEventListener('click', function(e) {
        if (e.target && e.target.hasAttribute('data-edit-backlink')) {
            const backlinkId = e.target.getAttribute('data-backlink-id');
            
            // Fetch backlink data
            fetch(`backlink_management_crud.php?action=get&id=${backlinkId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.backlink) {
                        const backlink = data.backlink;
                        
                        // Set form values
                        document.getElementById('edit-backlink-id').value = backlink.id;
                        document.getElementById('edit-campaign-id').value = backlink.campaign_id;
                        document.getElementById('edit-anchor-text').value = backlink.anchor_text;
                        document.getElementById('edit-target-url').value = backlink.target_url;
                        document.getElementById('edit-source-url').value = backlink.source_url;
                        
                        // Set other fields if present
                        if (document.getElementById('edit-status')) {
                            document.getElementById('edit-status').value = backlink.status || 'pending';
                        }
                        if (document.getElementById('edit-nofollow')) {
                            document.getElementById('edit-nofollow').checked = backlink.nofollow === 'yes';
                        }
                        if (document.getElementById('edit-sponsored')) {
                            document.getElementById('edit-sponsored').checked = backlink.sponsored === 'yes';
                        }
                        if (document.getElementById('edit-notes')) {
                            document.getElementById('edit-notes').value = backlink.notes || '';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', 'An error occurred while fetching backlink data.');
                });
        }
    });
    
    // Handle bulk upload form
    const bulkUploadForm = document.getElementById('bulk-upload-form');
    if (bulkUploadForm) {
        bulkUploadForm.addEventListener('submit', function(e) {
            // Show loading indicator
            const submitButton = bulkUploadForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...';
            
            // Form will submit normally (non-AJAX) for file uploads
        });
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
    
    // Handle status filter clicks
    const statLinks = document.querySelectorAll('.stat-link');
    if (statLinks) {
        statLinks.forEach(link => {
            link.addEventListener('click', function() {
                const filterType = this.getAttribute('data-filter-type');
                const filterValue = this.getAttribute('data-filter-value');
                const campaignId = this.getAttribute('data-campaign-id');
                
                // Remove active class from all stat links
                statLinks.forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Redirect to filtered view
                window.location.href = `backlink_management.php?campaign_id=${campaignId}&filter_type=${filterType}&filter_value=${filterValue}`;
            });
        });
    }
}); 