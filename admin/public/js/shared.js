// Shared JavaScript functions for the SSO Admin Panel
// Compatible with PHP 7.4.33

/**
 * Show custom toast notification
 * Creates and displays a Bootstrap toast notification
 * @param {string} message - The message to display in the toast
 * @param {string} type - The type of toast (success, warning, danger, info) - defaults to success
 */
function showCustomToast(message = 'Operation completed successfully!', type = 'success') {
    // Map type to Bootstrap background classes
    const typeClasses = {
        'success': 'bg-success',
        'warning': 'bg-warning',
        'danger': 'bg-danger',
        'info': 'bg-info'
    };
    
    // Get the appropriate background class, default to success
    const bgClass = typeClasses[type] || 'bg-success';
    
    // Create toast element
    const toast = document.createElement('div');
    toast.innerHTML = `
        <div class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    // Add to document
    document.body.appendChild(toast);
    
    // Initialize and show toast
    const bsToast = new bootstrap.Toast(toast.querySelector('.toast'), {
        delay: 2000
    });
    bsToast.show();
    
    // Remove from DOM after hidden
    toast.querySelector('.toast').addEventListener('hidden.bs.toast', function() {
        document.body.removeChild(toast);
    });
}

/**
 * Show custom copy success notification
 * Specialized version of showCustomToast for copy operations
 * @param {string} message - The message to display in the toast
 */
function showCustomCopySuccess(message = 'Copied to clipboard successfully!') {
    showCustomToast(message, 'success');
}