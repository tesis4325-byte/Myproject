// Toast notification function
function showToast(message, type = 'success') {
    const toast = `
        <div class="toast" role="alert" style="min-width: 200px">
            <div class="toast-body d-flex align-items-center ${type === 'success' ? 'text-success' : 'text-danger'}">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            </div>
        </div>
    `;
    
    if (!document.querySelector('.toast-container')) {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    
    const container = document.querySelector('.toast-container');
    container.insertAdjacentHTML('beforeend', toast);
    
    const toastElement = container.lastElementChild;
    const bsToast = new bootstrap.Toast(toastElement, { delay: 3000 });
    bsToast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Loading button function
function toggleButtonLoading(button, isLoading) {
    if (isLoading) {
        button.setAttribute('data-original-text', button.innerHTML);
        button.innerHTML = `
            <span class="loading-spinner me-2"></span>
            Loading...
        `;
        button.disabled = true;
    } else {
        button.innerHTML = button.getAttribute('data-original-text');
        button.disabled = false;
    }
}

// Initialize DataTables with common settings
function initDataTable(tableId, options = {}) {
    return $(tableId).DataTable({
        pageLength: 10,
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        language: {
            search: "",
            searchPlaceholder: "Search...",
            lengthMenu: "_MENU_ per page"
        },
        ...options
    });
}

// Form validation
function validateForm(formElement) {
    const form = document.querySelector(formElement);
    if (!form) return true;

    form.classList.add('was-validated');
    return form.checkValidity();
}

// Confirm action with custom modal
function confirmAction(message, callback) {
    const modalId = 'confirmActionModal';
    let modal = document.getElementById(modalId);
    
    if (!modal) {
        const modalHTML = `
            <div class="modal fade" id="${modalId}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Action</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary confirm-btn">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        modal = document.getElementById(modalId);
    }
    
    const modalInstance = new bootstrap.Modal(modal);
    modal.querySelector('.modal-body').textContent = message;
    
    modal.querySelector('.confirm-btn').onclick = () => {
        modalInstance.hide();
        callback();
    };
    
    modalInstance.show();
}

// Initialize tooltips and popovers
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize all popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Check for success/error messages in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showToast(urlParams.get('success'), 'success');
    }
    if (urlParams.has('error')) {
        showToast(urlParams.get('error'), 'error');
    }
});