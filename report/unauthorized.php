<?php
session_start();
// Redirect if user is authenticated and admin
if (isset($_SESSION['SESS_MEMBER_ID']) && $_SESSION['role'] === 'Admin') {
    // header("Location: home.php");
    // exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Unauthorized | OOUTH Salary Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../css/dark-mode.css" rel="stylesheet">
    <link rel="icon" href="img/header_logo.png" type="image/png">
    <script src="../js/theme-manager.js"></script>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="w-full bg-white shadow py-3 px-4 flex items-center">
        <a href="index.php">
            <img src="img/oouth_logo.png" alt="OOUTH Logo"
                class="w-20 h-20 rounded-full shadow bg-white/70 ring-2 ring-blue-200 mb-2">
        </a>
        <span class="ml-4 text-lg font-semibold text-blue-800">OOUTH Salary Manager</span>
    </header>
    <main class="flex-1 flex flex-col justify-center items-center px-4">
        <div
            class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full text-center border-t-8 border-red-500 animate-fade-in">
            <div class="text-red-500 mb-2">
                <svg class="mx-auto h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold mb-2 text-gray-900">Unauthorized Access</h1>
            <p class="mb-4 text-gray-700">You do not have permission to access this page.<br>
                Please login as an administrator to continue.
            </p>
            <a href="index.php"
                class="inline-block bg-blue-600 hover:bg-blue-800 text-white font-semibold px-6 py-2 rounded-lg transition shadow mt-2">
                Back to Login
            </a>
        </div>
    </main>
    <footer class="text-center text-gray-400 text-xs py-4 mt-auto">
        &copy; <?= date('Y') ?> OOUTH Salary Manager. Visit our
        <a href="#" class="text-blue-500 hover:underline" target="_blank">website</a>
        for the latest updates.
    </footer>
    <style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(40px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.7s cubic-bezier(0.4, 0, 0.2, 1);
    }
    </style>
</body>

</html>