<?php
ini_set('max_execution_time', 300);
require_once 'Connections/paymaster.php';
require_once 'classes/model.php';
require_once 'libs/App.php';
require_once 'libs/middleware.php';

$App = new App();
$App->checkAuthentication();
checkPermission();

session_start();

if (!isset($_SESSION['SESS_MEMBER_ID']) || trim($_SESSION['SESS_MEMBER_ID']) === '' || ($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Load dropdown options
$selectDrops = $App->selectDrop();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Allowances/Deductions - Salary Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.css" type="text/css" />
    
    <style>
        .dropzone {
            min-height: 200px;
            border: 2px dashed #4F46E5;
            border-radius: 0.5rem;
            background: #F9FAFB;
            transition: all 0.3s ease;
        }
        
        .dropzone:hover {
            border-color: #3730A3;
            background: #F3F4F6;
        }
        
        .dropzone.dz-drag-hover {
            border-color: #059669;
            background: #ECFDF5;
        }
        
        .backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .spinner {
            color: white;
            font-size: 1.25rem;
            padding: 1rem 2rem;
            background: rgba(0, 0, 0, 0.8);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .spinner i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
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
                <span>Upload Allowances/Deductions</span>
            </nav>
            
            <?php if (isset($_SESSION['msg'])): ?>
                <div class="bg-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-100 text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-800 p-4 rounded-md mb-6 flex justify-between items-center">
                    <span><?php echo htmlspecialchars($_SESSION['msg']); ?></span>
                    <button onclick="this.parentElement.remove()" class="text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-600 hover:text-<?php echo $_SESSION['alertcolor'] ?? 'blue'; ?>-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['msg'], $_SESSION['alertcolor']); ?>
            <?php endif; ?>
            
            <h1 class="text-3xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-upload mr-2"></i> Upload Allowances/Deductions
                <small class="text-base text-gray-600 ml-2">Bulk upload employee allowances and deductions</small>
            </h1>
            
            <div class="max-w-4xl mx-auto">
                <div class="bg-white p-8 rounded-lg shadow-md">
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Upload Excel File</h2>
                        <form action="libs/process_upload.php" class="dropzone" id="file-dropzone">
                            <div class="dz-message needsclick text-center p-8">
                                <div class="mb-4">
                                    <i class="fas fa-cloud-upload-alt text-5xl text-gray-300"></i>
                                </div>
                                <h5 class="text-xl text-gray-600 mb-2">Drop Excel file here or click to upload</h5>
                                <p class="text-sm text-gray-500">Only .xlsx files are accepted</p>
                            </div>
                        </form>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <label for="allow_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Allowance/Deduction Type <span class="text-red-500">*</span>
                            </label>
                            <select id="allow_id" name="allow_id" class="w-full p-3 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Item</option>
                                <?php foreach ($selectDrops as $selectDrop): ?>
                                    <option value="<?php echo htmlspecialchars($selectDrop['ed_id']); ?>">
                                        <?php echo htmlspecialchars($selectDrop['ed']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="counter" class="block text-sm font-medium text-gray-700 mb-2">
                                Counter
                            </label>
                            <input type="number" id="counter" name="counter" value="0" min="0" 
                                   class="w-full p-3 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="add_remove" class="block text-sm font-medium text-gray-700 mb-2">
                                Action <span class="text-red-500">*</span>
                            </label>
                            <select id="add_remove" name="add_remove" 
                                    class="w-full p-3 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="1">Add Item</option>
                                <option value="-1">Remove Item</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="verification" class="block text-sm font-medium text-gray-700 mb-2">
                                Verification Column <span class="text-red-500">*</span>
                            </label>
                            <select id="verification" name="verification" 
                                    class="w-full p-3 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="staff_id">Staff ID</option>
                                <option value="NAME">Name</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button type="button" id="send-files" 
                                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-upload mr-2"></i>
                            Process Upload
                        </button>
                        
                        <a href="template.xlsx" download 
                           class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-200 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            Download Template
                        </a>
                        
                        <button type="button" id="clear-form" 
                                class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 focus:ring-4 focus:ring-gray-200 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-undo mr-2"></i>
                            Clear Form
                        </button>
                    </div>
                    
                    <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>Instructions
                        </h3>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• Download the template file and fill in your data</li>
                            <li>• First column should contain Staff ID or Name (based on verification column)</li>
                            <li>• Second column should contain the allowance/deduction amount</li>
                            <li>• Only .xlsx files are accepted</li>
                            <li>• Invalid rows will be skipped automatically</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Backdrop -->
<div class="backdrop" id="backdrop">
    <div class="spinner">
        <i class="fas fa-spinner"></i>
        Processing upload...
    </div>
</div>

<script>
Dropzone.autoDiscover = false;

$(document).ready(function() {
    var myDropzone = new Dropzone("#file-dropzone", {
        url: "libs/process_upload.php",
        autoProcessQueue: false,
        acceptedFiles: ".xlsx",
        maxFiles: 1,
        maxFilesize: 10, // MB
        addRemoveLinks: true,
        dictDefaultMessage: "Drop Excel file here or click to upload",
        dictFileTooBig: "File is too big ({{filesize}}MB). Max filesize: {{maxFilesize}}MB.",
        dictInvalidFileType: "You can't upload files of this type.",
        dictRemoveFile: "Remove",
        
        init: function() {
            var dz = this;
            
            // Send files button
            document.getElementById("send-files").addEventListener("click", function() {
                validateAndProcess(dz);
            });
            
            // Clear form button
            document.getElementById("clear-form").addEventListener("click", function() {
                clearForm(dz);
            });
            
            // File added event
            dz.on("addedfile", function(file) {
                if (dz.files.length > 1) {
                    dz.removeFile(dz.files[0]);
                }
            });
            
            // Sending event
            dz.on("sending", function(file, xhr, formData) {
                var counter = $('#counter').val();
                var allow_id = $('#allow_id').val();
                var add_remove = $('#add_remove').val();
                var verification = $('#verification').val();
                
                formData.append("counter", counter);
                formData.append("allow_id", allow_id);
                formData.append("add_remove", add_remove);
                formData.append("verification", verification);
            });
            
            // Success event
            dz.on("success", function(file, response) {
                document.getElementById("backdrop").style.display = "none";
                
                try {
                    var result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Upload Successful!',
                            text: result.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                        clearForm(dz);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: result.message || 'An error occurred during upload'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: 'Invalid response from server'
                    });
                }
            });
            
            // Error event
            dz.on("error", function(file, response) {
                document.getElementById("backdrop").style.display = "none";
                
                var errorMessage = 'An error occurred during upload.';
                if (typeof response === 'object' && response.message) {
                    errorMessage = response.message;
                } else if (typeof response === 'string') {
                    try {
                        var result = JSON.parse(response);
                        errorMessage = result.message || errorMessage;
                    } catch (e) {
                        errorMessage = response;
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Upload Failed',
                    text: errorMessage
                });
            });
            
            // Complete event
            dz.on("complete", function(file) {
                dz.removeFile(file);
            });
        }
    });
    
    function validateAndProcess(dz) {
        var counter = $('#counter').val();
        var allow_id = $('#allow_id').val();
        var add_remove = $('#add_remove').val();
        var verification = $('#verification').val();
        
        // Validation
        if (!allow_id) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please select an Allowance/Deduction type'
            });
            return;
        }
        
        if (counter === '' || counter < 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please enter a valid counter value (0 or greater)'
            });
            return;
        }
        
        if (!add_remove) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please select an action (Add/Remove)'
            });
            return;
        }
        
        if (!verification) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please select a verification column'
            });
            return;
        }
        
        if (dz.getQueuedFiles().length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please upload an Excel file'
            });
            return;
        }
        
        // Show loading backdrop
        document.getElementById("backdrop").style.display = "flex";
        
        // Process the queue
        dz.processQueue();
    }
    
    function clearForm(dz) {
        // Clear dropzone
        dz.removeAllFiles();
        
        // Reset form fields
        $('#allow_id').val('');
        $('#counter').val('0');
        $('#add_remove').val('1');
        $('#verification').val('staff_id');
        
        Swal.fire({
            icon: 'success',
            title: 'Form Cleared',
            text: 'All fields have been reset',
            timer: 1500,
            showConfirmButton: false
        });
    }
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>

