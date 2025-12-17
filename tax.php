<?php
session_start();
require_once 'Connections/paymaster.php';
require_once 'libs/App.php';
require_once 'libs/middleware.php';

$app = new App();
$app->checkAuthentication();
checkPermission();

// Restrict to admins only
if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '' || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Staff Tax - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/dark-mode.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/theme-manager.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 font-sans">
    <?php include 'header.php'; ?>
    <div class="flex min-h-screen">
        <?php include 'sidebar.php'; ?>
        <div class="flex-1 p-6">
            <div class="container mx-auto">
                <nav class="mb-6">
                    <a href="home.php" class="text-blue-600 hover:underline"><i class="fas fa-home"></i> Dashboard</a>
                    <span class="mx-2">/</span>
                    <span>Upload Staff Tax</span>
                </nav>

                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="bg-<?php echo $_SESSION['alertcolor']; ?>-100 text-<?php echo $_SESSION['alertcolor']; ?>-800 p-4 rounded-md mb-6 flex justify-between items-center">
                        <span><?php echo htmlspecialchars($_SESSION['msg']); ?></span>
                        <button onclick="this.parentElement.remove()" class="text-<?php echo $_SESSION['alertcolor']; ?>-600 hover:text-<?php echo $_SESSION['alertcolor']; ?>-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <?php unset($_SESSION['msg'], $_SESSION['alertcolor']); ?>
                <?php endif; ?>

                <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-upload mr-2"></i> Upload Staff Tax
                </h1>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <form id="upload_excel" action="excel_import/import_tax.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label for="file" class="block text-sm font-medium text-gray-700 mb-2">Select Excel/CSV File</label>
                                <input type="file" name="file" id="file" accept=".csv,.xlsx,.xls" class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="hasHeader" id="hasHeader" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="hasHeader" class="ml-2 text-sm text-gray-700">File Has Header?</label>
                            </div>
                            <div class="flex items-center">
                                <a href="download/tax_template.xlsx" class="text-blue-600 hover:underline">
                                    <i class="fas fa-download mr-2"></i> Download Tax Template
                                </a>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" id="upload" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#upload_excel').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const uploadButton = $('#upload');
                uploadButton.text('Uploading...').prop('disabled', true);

                const formData = new FormData(this);
                formData.append('hasHeaders', $('#hasHeader').is(':checked') ? 1 : 0);

                $.ajax({
                    url: form.attr('action'),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        uploadButton.text('Upload').prop('disabled', false);
                        form.trigger('reset');
                        Swal.fire({
                            icon: response.status === 'success' ? 'success' : 'error',
                            title: response.status === 'success' ? 'Success' : 'Error',
                            text: response.message || 'Upload completed.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            if (response.status === 'success') {
                                window.location.reload();
                            }
                        });
                    },
                    error: function() {
                        uploadButton.text('Upload').prop('disabled', false);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred during upload. Please try again.'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>