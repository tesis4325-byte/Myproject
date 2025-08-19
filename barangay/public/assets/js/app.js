/**
 * Barangay Document Request and Tracking System
 * Main JavaScript File
 */

// Global variables
let currentUser = null;
let notifications = [];

// Global error handler for jQuery issues
window.addEventListener('error', function(e) {
    if (e.message && e.message.includes('$')) {
        console.error('jQuery-related error detected:', e.message);
        console.log('jQuery status:', typeof $, typeof jQuery);
    }
});

// jQuery availability check
function waitForJQuery(callback, maxAttempts = 50) {
    if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
        console.log('jQuery loaded successfully, initializing app...');
        console.log('jQuery version:', $.fn.jquery);
        callback();
    } else if (maxAttempts > 0) {
        console.log('Waiting for jQuery... Attempts remaining:', maxAttempts);
        setTimeout(() => waitForJQuery(callback, maxAttempts - 1), 100);
    } else {
        console.warn('jQuery not available after maximum attempts');
        console.log('Final jQuery status - $:', typeof $, 'jQuery:', typeof jQuery);
    }
}

// Initialize application
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be available, then initialize
    waitForJQuery(function() {
        $(document).ready(function() {
            initializeApp();
        });
    });
});

/**
 * Initialize the application
 */
function initializeApp() {
    // jQuery is guaranteed to be available here
    console.log('Initializing application with jQuery version:', $.fn.jquery);
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize AJAX requests
    initializeAjax();
    
    // Initialize real-time updates
    initializeRealTimeUpdates();
    
    // Initialize print functionality
    initializePrint();
    
    // Initialize file uploads
    initializeFileUploads();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize notifications
    initializeNotifications();
}

/**
 * Initialize Bootstrap tooltips
 */
function initializeTooltips() {
    // Check if Bootstrap is available
    if (typeof bootstrap === 'undefined') {
        console.warn('Bootstrap is not loaded. Skipping tooltip initialization.');
        return;
    }
    
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize form validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Initialize AJAX functionality
 */
function initializeAjax() {
    // jQuery is guaranteed to be available here
    console.log('Initializing AJAX functionality...');
    
    // Add CSRF token to all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });
    
    // Global AJAX error handler
    $(document).ajaxError(function(event, xhr, settings, error) {
        console.error('AJAX Error:', error);
        showNotification('An error occurred while processing your request.', 'error');
    });
}

/**
 * Initialize real-time updates
 */
function initializeRealTimeUpdates() {
    // Update request status in real-time
    if (document.querySelector('.request-status')) {
        // jQuery is guaranteed to be available here
        setInterval(updateRequestStatus, 30000); // Update every 30 seconds
    }
}

/**
 * Initialize print functionality
 */
function initializePrint() {
    const printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            printDocument(this.dataset.target);
        });
    });
}

/**
 * Initialize file uploads
 */
function initializeFileUploads() {
    const fileInputs = document.querySelectorAll('.file-upload');
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            handleFileUpload(this, e);
        });
    });
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function(e) {
            performSearch(this.value, this.dataset.target);
        }, 300));
    });
}

/**
 * Initialize notifications
 */
function initializeNotifications() {
    // Check for session messages
    const message = getSessionMessage();
    if (message) {
        showNotification(message.message, message.type);
    }
}

/**
 * Update request status
 */
function updateRequestStatus() {
    // jQuery is guaranteed to be available here
    
    const requestIds = Array.from(document.querySelectorAll('.request-status'))
        .map(el => el.dataset.requestId);
    
    if (requestIds.length === 0) return;
    
    $.ajax({
        url: '../api/request_api.php',
        method: 'POST',
        data: {
            action: 'get_status_updates',
            request_ids: requestIds
        },
        success: function(response) {
            if (response.success) {
                response.data.forEach(request => {
                    updateStatusDisplay(request.id, request.status);
                });
            }
        }
    });
}

/**
 * Update status display
 */
function updateStatusDisplay(requestId, status) {
    const statusElement = document.querySelector(`[data-request-id="${requestId}"]`);
    if (statusElement) {
        statusElement.textContent = getStatusText(status);
        statusElement.className = `request-status ${getStatusBadgeClass(status)}`;
    }
}

/**
 * Handle file upload
 */
function handleFileUpload(input, event) {
    const file = event.target.files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    
    // Validate file size
    if (file.size > maxSize) {
        showNotification('File size must be less than 5MB.', 'error');
        input.value = '';
        return;
    }
    
    // Validate file type
    if (!allowedTypes.includes(file.type)) {
        showNotification('Please upload only JPG, PNG, PDF, DOC, or DOCX files.', 'error');
        input.value = '';
        return;
    }
    
    // Show preview if it's an image
    if (file.type.startsWith('image/')) {
        showImagePreview(file, input);
    }
}

/**
 * Show image preview
 */
function showImagePreview(file, input) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = input.parentNode.querySelector('.image-preview');
        if (preview) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
    };
    reader.readAsDataURL(file);
}

/**
 * Perform search
 */
function performSearch(query, target) {
    // jQuery is guaranteed to be available here
    
    if (query.length < 2) {
        resetSearchResults(target);
        return;
    }
    
    $.ajax({
        url: '../api/search_api.php',
        method: 'POST',
        data: {
            query: query,
            target: target
        },
        success: function(response) {
            if (response.success) {
                displaySearchResults(response.data, target);
            }
        }
    });
}

/**
 * Display search results
 */
function displaySearchResults(results, target) {
    const container = document.querySelector(`#${target}-results`);
    if (!container) return;
    
    container.innerHTML = '';
    
    if (results.length === 0) {
        container.innerHTML = '<p class="text-muted">No results found.</p>';
        return;
    }
    
    results.forEach(result => {
        const item = document.createElement('div');
        item.className = 'search-result-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                <div>
                    <strong>${result.title}</strong>
                    <br>
                    <small class="text-muted">${result.description}</small>
                </div>
                <button class="btn btn-sm btn-primary" onclick="selectSearchResult('${result.id}', '${target}')">
                    Select
                </button>
            </div>
        `;
        container.appendChild(item);
    });
}

/**
 * Reset search results
 */
function resetSearchResults(target) {
    const container = document.querySelector(`#${target}-results`);
    if (container) {
        container.innerHTML = '';
    }
}

/**
 * Select search result
 */
function selectSearchResult(id, target) {
    const input = document.querySelector(`#${target}-input`);
    if (input) {
        input.value = id;
        input.dataset.selectedId = id;
    }
    
    resetSearchResults(target);
}

/**
 * Print document
 */
function printDocument(target) {
    const printWindow = window.open('', '_blank');
    const content = document.querySelector(target);
    
    if (content) {
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print Document</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @media print {
                        .no-print { display: none !important; }
                    }
                    body { font-family: Arial, sans-serif; }
                </style>
            </head>
            <body>
                ${content.outerHTML}
                <script>window.print();</script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const alertClass = `alert-${type}`;
    const alertId = 'alert-' + Date.now();
    
    const alertHtml = `
        <div id="${alertId}" class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Add to page
    const container = document.querySelector('.alert-container') || document.body;
    container.insertAdjacentHTML('afterbegin', alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            if (typeof bootstrap !== 'undefined') {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                // Fallback: just remove the alert
                alert.remove();
            }
        }
    }, 5000);
}

/**
 * Get session message
 */
function getSessionMessage() {
    // This would typically be set by PHP
    return window.sessionMessage || null;
}

/**
 * Confirm action
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Show loading spinner
 */
function showLoading(element) {
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.id = 'loading-spinner';
    
    element.appendChild(spinner);
    element.style.position = 'relative';
}

/**
 * Hide loading spinner
 */
function hideLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Format date
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

/**
 * Format date and time
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Get status text
 */
function getStatusText(status) {
    const statusMap = {
        'pending': 'Pending',
        'processing': 'Processing',
        'approved': 'Approved',
        'rejected': 'Rejected',
        'released': 'Released'
    };
    return statusMap[status] || 'Unknown';
}

/**
 * Get status badge class
 */
function getStatusBadgeClass(status) {
    const classMap = {
        'pending': 'badge bg-warning',
        'processing': 'badge bg-info',
        'approved': 'badge bg-success',
        'rejected': 'badge bg-danger',
        'released': 'badge bg-primary'
    };
    return classMap[status] || 'badge bg-secondary';
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Validate form
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Reset form
 */
function resetForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        form.querySelectorAll('.is-invalid').forEach(input => {
            input.classList.remove('is-invalid');
        });
    }
}

/**
 * Export to CSV
 */
function exportToCSV(data, filename) {
    const csvContent = "data:text/csv;charset=utf-8," + data;
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * Generate CSV from table
 */
function generateCSVFromTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return '';
    
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    
    return csv.join('\n');
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard!', 'success');
    }).catch(() => {
        showNotification('Failed to copy to clipboard.', 'error');
    });
}

/**
 * Toggle password visibility
 */
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.querySelector(`[data-toggle="${inputId}"]`);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

/**
 * Auto-save form data
 */
function autoSaveForm(formId, interval = 30000) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    setInterval(() => {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        localStorage.setItem(`autosave_${formId}`, JSON.stringify({
            data: data,
            timestamp: Date.now()
        }));
    }, interval);
}

/**
 * Load auto-saved form data
 */
function loadAutoSavedForm(formId) {
    const saved = localStorage.getItem(`autosave_${formId}`);
    if (!saved) return;
    
    const { data, timestamp } = JSON.parse(saved);
    const form = document.getElementById(formId);
    
    // Only load if saved within last hour
    if (Date.now() - timestamp < 3600000) {
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = data[key];
            }
        });
    }
}

// Export functions for global use
window.BarangayApp = {
    showNotification,
    confirmAction,
    showLoading,
    hideLoading,
    formatDate,
    formatDateTime,
    validateForm,
    resetForm,
    exportToCSV,
    copyToClipboard,
    togglePasswordVisibility,
    autoSaveForm,
    loadAutoSavedForm
};
