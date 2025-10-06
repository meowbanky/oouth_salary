<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active class
$currentPage = basename($_SERVER['SCRIPT_NAME']);

// Define sidebar menu items
$menuItems = [
    ['href' => 'home.php', 'icon' => 'fas fa-home', 'label' => 'Dashboard'],
    ['href' => 'report/index.php', 'icon' => 'fas fa-file-lines', 'label' => 'Reports'],
    ['href' => 'index.php', 'icon' => 'fas fa-power-off', 'label' => 'Logout'],
];

// Admin-only menu items
$adminItems = [
    ['href' => 'employee.php', 'icon' => 'fas fa-user', 'label' => 'Employees'],
    ['href' => 'empearnings.php', 'icon' => 'fas fa-credit-card', 'label' => 'Earnings/Deductions'],
    ['href' => 'upload.php', 'icon' => 'fas fa-cloud-upload-alt', 'label' => 'Upload Items'],
    ['href' => 'tax.php', 'icon' => 'fas fa-file-invoice-dollar', 'label' => 'Update Tax'],
    ['href' => 'payperiods.php', 'icon' => 'fas fa-calendar', 'label' => 'Pay Periods'],
    ['href' => 'edit_conhess_conmess.php', 'icon' => 'fas fa-table', 'label' => 'Salary Table'],
    ['href' => 'users.php', 'icon' => 'fas fa-users', 'label' => 'Users'],
    ['href' => 'permissions.php', 'icon' => 'fas fa-shield-alt', 'label' => 'Permissions'],
    ['href' => 'payprocess.php', 'icon' => 'fas fa-cog', 'label' => 'Proces'],
    ['href' => 'multiAdjustment.php', 'icon' => 'fas fa-shopping-cart', 'label' => 'Periodic Payments'],
    ['href' => 'departments.php', 'icon' => 'fas fa-home', 'label' => 'Department'],
    ['href' => 'bank.php', 'icon' => 'fas fa-university', 'label' => 'Bank'],
    ['href' => 'editpfa.php', 'icon' => 'fas fa-coins', 'label' => 'PFA'],
    ['href' => 'email_deduction.php', 'icon' => 'fas fa-envelope', 'label' => 'Email Deductions'],
    ['href' => 'monthly_comparison_report.php', 'icon' => 'fas fa-chart-line', 'label' => 'Monthly Comparison'],
    ['href' => 'abeokuta_variance_tracking_enhanced.php', 'icon' => 'fas fa-exchange-alt', 'label' => 'Abeokuta Variance'],
    ['href' => 'abeokuta_audit_report.php', 'icon' => 'fas fa-clipboard-list', 'label' => 'Abeokuta Audit'],
];

// Merge admin items for admin users
if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
    $menuItems = array_merge(array_slice($menuItems, 0, 1), $adminItems, array_slice($menuItems, 1));
}
?>

<aside id="sidebar" class="w-64 min-h-screen p-4 hidden md:block" style="background-color: var(--bg-secondary); color: var(--text-primary);">
    <h2 class="text-xl font-bold mb-6">Menu</h2>
    <ul class="space-y-2">
        <?php foreach ($menuItems as $item): ?>
        <li>
            <a href="<?php echo $item['href']; ?>"
                class="flex items-center py-2 px-4 rounded transition-colors duration-200"
                style="background-color: <?php echo $currentPage === $item['href'] ? 'var(--text-accent)' : 'transparent'; ?>; color: var(--text-primary);"
                onmouseover="this.style.backgroundColor='<?php echo $currentPage === $item['href'] ? 'var(--text-accent)' : 'var(--bg-hover)'; ?>'"
                onmouseout="this.style.backgroundColor='<?php echo $currentPage === $item['href'] ? 'var(--text-accent)' : 'transparent'; ?>'">
                <i class="<?php echo $item['icon']; ?> mr-3 text-lg"></i>
                <span><?php echo $item['label']; ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</aside>