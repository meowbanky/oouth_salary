<?php
/**
 * Dark Mode Includes - Universal Template
 * Include this file in all pages to add dark mode support
 * This file should be included after the <head> tag and before the <body> tag
 */
?>

<!-- Dark Mode CSS and JavaScript -->
<link rel="stylesheet" href="<?php echo (strpos($_SERVER['REQUEST_URI'], '/report/') !== false) ? '../' : ''; ?>css/dark-mode.css">
<script src="<?php echo (strpos($_SERVER['REQUEST_URI'], '/report/') !== false) ? '../' : ''; ?>js/theme-manager.js"></script>

<style>
/* Additional dark mode styles for specific components */
[data-theme="dark"] .card {
    background-color: var(--bg-secondary) !important;
    border-color: var(--border-primary) !important;
}

[data-theme="dark"] .card-header {
    background-color: var(--bg-tertiary) !important;
    border-bottom-color: var(--border-primary) !important;
}

[data-theme="dark"] .btn-primary {
    background-color: var(--text-accent) !important;
    border-color: var(--text-accent) !important;
}

[data-theme="dark"] .btn-primary:hover {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
}

[data-theme="dark"] .btn-secondary {
    background-color: var(--bg-tertiary) !important;
    border-color: var(--border-primary) !important;
    color: var(--text-primary) !important;
}

[data-theme="dark"] .btn-secondary:hover {
    background-color: var(--bg-hover) !important;
}

[data-theme="dark"] .form-control {
    background-color: var(--bg-tertiary) !important;
    border-color: var(--border-primary) !important;
    color: var(--text-primary) !important;
}

[data-theme="dark"] .form-control:focus {
    border-color: var(--border-accent) !important;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25) !important;
}

[data-theme="dark"] .table {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .table th {
    background-color: var(--bg-tertiary) !important;
    border-color: var(--border-primary) !important;
}

[data-theme="dark"] .table td {
    background-color: var(--bg-secondary) !important;
    border-color: var(--border-primary) !important;
}

[data-theme="dark"] .table-striped tbody tr:nth-of-type(odd) td {
    background-color: var(--bg-tertiary) !important;
}

/* SweetAlert2 Dark Mode */
[data-theme="dark"] .swal2-popup {
    background-color: var(--bg-secondary) !important;
    color: var(--text-primary) !important;
}

[data-theme="dark"] .swal2-title {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .swal2-content {
    color: var(--text-secondary) !important;
}

[data-theme="dark"] .swal2-confirm {
    background-color: var(--text-accent) !important;
}

[data-theme="dark"] .swal2-cancel {
    background-color: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
}

/* DataTables Dark Mode */
[data-theme="dark"] .dataTables_wrapper {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .dataTables_filter input {
    background-color: var(--bg-tertiary) !important;
    border-color: var(--border-primary) !important;
    color: var(--text-primary) !important;
}

[data-theme="dark"] .dataTables_length select {
    background-color: var(--bg-tertiary) !important;
    border-color: var(--border-primary) !important;
    color: var(--text-primary) !important;
}

[data-theme="dark"] .dataTables_info {
    color: var(--text-secondary) !important;
}

[data-theme="dark"] .paginate_button {
    background-color: var(--bg-secondary) !important;
    border-color: var(--border-primary) !important;
    color: var(--text-primary) !important;
}

[data-theme="dark"] .paginate_button:hover {
    background-color: var(--bg-hover) !important;
}

[data-theme="dark"] .paginate_button.current {
    background-color: var(--text-accent) !important;
    border-color: var(--text-accent) !important;
}

/* Loading Spinner Dark Mode */
[data-theme="dark"] .loading {
    background-color: var(--bg-secondary) !important;
}

[data-theme="dark"] .spinner {
    border-color: var(--border-primary) !important;
    border-top-color: var(--text-accent) !important;
}

/* Progress Bar Dark Mode */
[data-theme="dark"] .progress {
    background-color: var(--bg-tertiary) !important;
}

[data-theme="dark"] .progress-bar {
    background-color: var(--text-accent) !important;
}

/* Tooltip Dark Mode */
[data-theme="dark"] .tooltip {
    background-color: var(--bg-secondary) !important;
    color: var(--text-primary) !important;
    border-color: var(--border-primary) !important;
}

/* Accordion Dark Mode */
[data-theme="dark"] .accordion-header {
    background-color: var(--bg-tertiary) !important;
    color: var(--text-primary) !important;
    border-color: var(--border-primary) !important;
}

[data-theme="dark"] .accordion-content {
    background-color: var(--bg-secondary) !important;
    border-color: var(--border-primary) !important;
}

/* Tabs Dark Mode */
[data-theme="dark"] .tab-nav {
    background-color: var(--bg-tertiary) !important;
    border-color: var(--border-primary) !important;
}

[data-theme="dark"] .tab-item {
    color: var(--text-secondary) !important;
}

[data-theme="dark"] .tab-item.active {
    color: var(--text-accent) !important;
    background-color: var(--bg-secondary) !important;
}

[data-theme="dark"] .tab-content {
    background-color: var(--bg-secondary) !important;
    border-color: var(--border-primary) !important;
}

/* Ensure print styles remain unchanged */
@media print {
    [data-theme="dark"] * {
        background: white !important;
        color: black !important;
        box-shadow: none !important;
    }
}
</style>

<script>
// Additional dark mode JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    // Apply theme to dynamically loaded content
    function applyThemeToNewContent() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (window.themeManager) {
                            window.themeManager.applyThemeToElement(node);
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Initialize theme application for new content
    applyThemeToNewContent();
    
    // Listen for theme changes and update third-party components
    document.addEventListener('themeChanged', function(event) {
        const theme = event.detail.theme;
        
        // Update SweetAlert2 theme
        if (window.Swal) {
            Swal.mixin({
                customClass: {
                    popup: theme === 'dark' ? 'dark-mode' : '',
                    title: theme === 'dark' ? 'dark-mode' : '',
                    content: theme === 'dark' ? 'dark-mode' : ''
                }
            });
        }
        
        // Update DataTables if present
        if ($.fn.DataTable) {
            $('.dataTable').each(function() {
                $(this).DataTable().draw();
            });
        }
        
        // Update any other third-party components here
    });
});
</script>
