<?php
// Ensure session is started (in case header.php is included standalone)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$today = date('l, F d, Y');
?>

<header class="bg-white shadow-md p-4 flex justify-between items-center">
    <!-- Logo -->
    <a href="index.php" class="flex items-center">
    <img src="img/oouth_logo.png" alt="OOUTH Logo" class="w-20 h-20 rounded-full shadow bg-white/70 ring-2 ring-blue-200 mb-2">
        <div class="ml-3">
            <div class="font-bold text-2xl text-blue-800 tracking-wide">OOUTH Salary Manager</div>
            <div class="text-blue-700/80 text-sm">Secure Payroll System Access</div>
        </div>
    </a>

    <!-- User Navigation -->
    <nav class="flex items-center space-x-4">
        <!-- Welcome User -->
        <div class="text-gray-700 hidden md:flex items-center">
            <i class="fas fa-user text-blue-600 mr-2"></i>
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['SESS_FIRST_NAME'] ?? 'User'); ?></strong></span>
        </div>

        <!-- Current Period -->
        <div class="text-gray-700 hidden md:flex items-center">
            <i class="fas fa-calendar text-blue-600 mr-2"></i>
            <span>Period: <strong><?php echo htmlspecialchars($_SESSION['activeperiodDescription'] ?? 'No Active Period'); ?></strong></span>
        </div>

        <!-- Current Date -->
        <div class="text-gray-700 hidden md:flex items-center">
            <i class="fas fa-clock text-blue-600 mr-2"></i>
            <span><?php echo $today; ?></span>
        </div>

        <!-- Settings -->
        <a href="#" class="text-gray-600 hover:text-blue-600" title="Settings">
            <i class="fas fa-cog text-lg"></i>
        </a>

        <!-- Pending Requests (Up) -->
        <a href="pendingRequest.php" class="text-gray-600 hover:text-blue-600 relative" title="Pending Requests (Up)">
            <i class="fas fa-angle-double-up text-lg"></i>
            <span id="bell" class="badge1 absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full px-2 py-1 hidden"></span>
        </a>

        <!-- Pending Requests (Down) -->
        <a href="pendingRequest.php" class="text-gray-600 hover:text-blue-600 relative" title="Pending Requests (Down)">
            <i class="fas fa-angle-double-down text-lg"></i>
            <span id="bell2" class="badge1 absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full px-2 py-1 hidden"></span>
        </a>

        <!-- Logout -->
        <a href="index.php" class="text-gray-600 hover:text-blue-600" title="Logout">
            <i class="fas fa-power-off text-lg"></i>
        </a>

        <!-- Mobile Menu Toggle -->
        <button id="menu-trigger" class="text-gray-600 hover:text-blue-600 md:hidden">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </nav>
</header>

<script>
    // Mobile menu toggle
    document.getElementById('menu-trigger')?.addEventListener('click', () => {
        document.getElementById('sidebar')?.classList.toggle('hidden');
    });

    // Placeholder for badge updates (assumed to be handled by existing AJAX)
    // Example: Update badges via AJAX (uncomment and customize if needed)
    /*
    fetch('get_pending_requests.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('bell').textContent = data.up_count || '';
            document.getElementById('bell').classList.toggle('hidden', !data.up_count);
            document.getElementById('bell2').textContent = data.down_count || '';
            document.getElementById('bell2').classList.toggle('hidden', !data.down_count);
        });
    */
</script>