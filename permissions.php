<?php
require_once('Connections/paymaster.php');
include_once('classes/model.php');
require_once('libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('libs/middleware.php');
checkPermission();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) == '' || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permissions Management - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/theme-manager.js"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('sidebar.php'); ?>
        
        <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
            <!-- Breadcrumb Navigation -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="home.php"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                            <i class="fas fa-home w-4 h-4 mr-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Permissions</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-shield-alt"></i> Permissions Management
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Manage user roles and page access permissions across the system.</p>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="flex-1">
                    <div id="loadContent">
                        <!-- Loading Skeleton -->
                        <div class="bg-white rounded-xl shadow-lg p-6 animate-pulse">
                            <div class="flex justify-between items-center mb-6">
                                <div class="h-6 bg-gray-200 rounded w-48"></div>
                                <div class="h-10 bg-gray-200 rounded w-20"></div>
                            </div>
                            <div class="space-y-4">
                                <div class="h-4 bg-gray-200 rounded w-full"></div>
                                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                                <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <footer class="mt-auto pt-8">
                    <div class="bg-white rounded-xl shadow-lg p-6 text-center">
                        <p class="text-gray-600 text-sm">
                            Please visit our
                            <a href="http://www.oouth.com/" target="_blank"
                                class="text-blue-600 hover:text-blue-800 transition-colors font-medium">
                                website
                            </a>
                            to learn the latest information about the project.
                        </p>
                        <div class="mt-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Version 14.1
                            </span>
                        </div>
                    </div>
                </footer>
            </div>
        </main>
    </div>

    <script>
    $(document).ready(function() {
        // Load permissions content
        $('#loadContent').load('view/view_permissions.php');
    });
    </script>
</body>

</html>

