<?php
// Ensure session is started (in case footer.php is included standalone)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- Footer Start -->
<footer class="bg-white shadow-md border-t border-gray-200 mt-auto">
    <div class="max-w-7xl mx-auto py-4 px-6">
        <div class="flex flex-col md:flex-row justify-between items-center">
            <div class="text-gray-600 text-sm mb-2 md:mb-0">
                <span>&copy; <?php echo date('Y'); ?> OOUTH Salary Management System. All rights reserved.</span>
            </div>
            <div class="flex items-center space-x-4 text-sm text-gray-600">
                <a href="#" class="hover:text-blue-600 transition-colors">About</a>
                <span class="text-gray-400">|</span>
                <a href="#" class="hover:text-blue-600 transition-colors">Support</a>
                <span class="text-gray-400">|</span>
                <a href="#" class="hover:text-blue-600 transition-colors">Contact</a>
                <span class="text-gray-400">|</span>
                <span class="text-gray-500">v14.1</span>
            </div>
        </div>
    </div>
</footer>
<!-- Footer End -->


<script>
// Global utility functions
function showLoading() {
    // Show loading overlay if needed
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.classList.remove('hidden');
    }
}

function hideLoading() {
    // Hide loading overlay if needed
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.classList.add('hidden');
    }
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll(
        '.alert, .bg-red-100, .bg-green-100, .bg-blue-100, .bg-yellow-100');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert && alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 500);
            }
        }, 5000);
    });
});

// Handle form submissions with loading states
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-loading="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            showLoading();
        });
    });
});

// Handle AJAX errors globally
$(document).ajaxError(function(event, xhr, settings, error) {
    console.error('AJAX Error:', error);
    hideLoading();

    // Show error message if not already handled
    if (xhr.status !== 0) { // 0 means request was aborted
        Swal.fire({
            icon: 'error',
            title: 'Request Failed',
            text: 'An error occurred while processing your request. Please try again.',
            timer: 3000,
            showConfirmButton: false
        });
    }
});

// Handle AJAX success globally
$(document).ajaxSuccess(function(event, xhr, settings) {
    hideLoading();
});
</script>

</body>

</html>