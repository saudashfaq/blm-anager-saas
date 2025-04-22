/**
 * ProxyManager specific JavaScript
 */
document.addEventListener('DOMContentLoaded', function() {
    // Toggle all checkboxes
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.proxy-select');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }

    // Confirm removal of selected proxies
    const removeSelectedBtn = document.getElementById('remove-selected');
    if (removeSelectedBtn) {
        removeSelectedBtn.addEventListener('click', function(e) {
            const checkedBoxes = document.querySelectorAll('.proxy-select:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one proxy to remove.');
                return false;
            }
            if (!confirm(`Are you sure you want to remove ${checkedBoxes.length} selected proxies?`)) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Handle proxy testing
    const testButtons = document.querySelectorAll('.test-proxy');
    testButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const proxyId = this.getAttribute('data-id');
            const resultSpan = document.getElementById(`test-result-${proxyId}`);
            
            // Show loading state
            this.disabled = true;
            resultSpan.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Testing...';
            
            // Call the test proxy endpoint
            fetch(`config/test_proxy.php?id=${proxyId}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    resultSpan.innerHTML = `<span class="text-success">Working! Response time: ${data.response_time}ms</span>`;
                } else {
                    resultSpan.innerHTML = `<span class="text-danger">Failed: ${data.error}</span>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultSpan.innerHTML = `<span class="text-danger">Error: ${error.message}</span>`;
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });

    // Handle update free proxies button
    const updateFreeProxiesBtn = document.getElementById('update-free-proxies');
    if (updateFreeProxiesBtn) {
        updateFreeProxiesBtn.addEventListener('click', function() {
            // Show loading state
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Updating...';

            // Make AJAX request to run the ProxyScraperValidator job
            fetch('jobs/ProxyScraperValidator.php', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                // The job doesn't return JSON, so we'll just show a success message
                alert('Successfully updated free proxies. The page will now reload to show the updated proxies.');
                // Reload the page to show updated proxies
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating free proxies: ' + error.message);
            })
            .finally(() => {
                // Reset button state
                this.disabled = false;
                this.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-refresh me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg> Update Free Proxies';
            });
        });
    }
}); 