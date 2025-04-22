/**
 * JavaScript for bulk upload functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    // DOM elements
    const bulkUploadForm = document.getElementById('bulk-upload-form');
    const fileInput = document.getElementById('csv-file');
    const previewTable = document.getElementById('csv-preview');
    const previewContainer = document.getElementById('preview-container');
    
    // If file input exists, add event listener for file selection
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            // Get the selected file
            const file = this.files[0];
            
            // Check if file is selected and is a CSV
            if (file && file.type === 'text/csv') {
                // Show file name in the input
                const fileNameDisplay = document.querySelector('.custom-file-label');
                if (fileNameDisplay) {
                    fileNameDisplay.textContent = file.name;
                }
                
                // Read the file
                const reader = new FileReader();
                reader.onload = function(e) {
                    const csvData = e.target.result;
                    displayCSVPreview(csvData);
                };
                reader.readAsText(file);
            } else if (file) {
                // Show error if not a CSV file
                showAlert('danger', 'Please select a valid CSV file.');
                this.value = '';
                const fileNameDisplay = document.querySelector('.custom-file-label');
                if (fileNameDisplay) {
                    fileNameDisplay.textContent = 'Choose file...';
                }
            }
        });
    }
    
    // Display CSV preview
    function displayCSVPreview(csvData) {
        if (!previewTable) return;
        
        // Clear existing preview
        previewTable.innerHTML = '';
        
        // Parse CSV data
        const rows = csvData.split('\n');
        if (rows.length < 2) {
            showAlert('warning', 'The CSV file appears to be empty or invalid.');
            return;
        }
        
        // Show preview container
        if (previewContainer) {
            previewContainer.style.display = 'block';
        }
        
        // Create table header from the first row
        const headerRow = document.createElement('tr');
        const headers = parseCSVRow(rows[0]);
        
        headers.forEach(header => {
            const th = document.createElement('th');
            th.textContent = header;
            headerRow.appendChild(th);
        });
        
        const thead = document.createElement('thead');
        thead.appendChild(headerRow);
        previewTable.appendChild(thead);
        
        // Create table body from the rest of the rows (limit to 5 for preview)
        const tbody = document.createElement('tbody');
        const maxRows = Math.min(rows.length, 6);
        
        for (let i = 1; i < maxRows; i++) {
            if (rows[i].trim() === '') continue; // Skip empty rows
            
            const rowData = parseCSVRow(rows[i]);
            const tr = document.createElement('tr');
            
            rowData.forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell;
                tr.appendChild(td);
            });
            
            tbody.appendChild(tr);
        }
        
        previewTable.appendChild(tbody);
        
        // Show row count information
        const totalRows = rows.length - 1; // Subtract header row
        const previewInfo = document.getElementById('preview-info');
        if (previewInfo) {
            previewInfo.textContent = `Showing ${Math.min(5, totalRows)} of ${totalRows} rows`;
        }
    }
    
    // Parse CSV row, handling quotes and commas correctly
    function parseCSVRow(row) {
        const result = [];
        let inQuotes = false;
        let currentValue = '';
        
        for (let i = 0; i < row.length; i++) {
            const char = row[i];
            
            if (char === '"') {
                // Toggle quotes state
                inQuotes = !inQuotes;
            } else if (char === ',' && !inQuotes) {
                // End of field, add to result
                result.push(currentValue.trim());
                currentValue = '';
            } else {
                // Add character to current field
                currentValue += char;
            }
        }
        
        // Add the last field
        if (currentValue) {
            result.push(currentValue.trim());
        }
        
        return result;
    }
    
    // Handle form submission
    if (bulkUploadForm) {
        bulkUploadForm.addEventListener('submit', function(e) {
            const file = fileInput.files[0];
            if (!file) {
                e.preventDefault();
                showAlert('danger', 'Please select a CSV file to upload.');
                return;
            }
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...';
            }
            
            // Form will submit normally
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
}); 