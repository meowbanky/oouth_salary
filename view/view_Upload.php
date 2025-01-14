<?php
require_once '../libs/App.php';
$App = new App();
$App->checkAuthentication();

$selectDrops = $App->selectDrop();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto"> <!-- Increased from max-w-md to max-w-4xl -->
        <div class="bg-white p-8 rounded-lg shadow-md w-full">
            <h1 class="text-3xl font-bold mb-6 text-center">Upload Allowances/Deductions</h1> <!-- Increased text size -->

            <!-- Dropzone with adjusted width -->
            <form action="libs/process_upload.php" class="dropzone w-full mb-6" id="file-dropzone">
                <div class="dz-message needsclick w-full text-center p-8"> <!-- Added padding -->
                    <div class="mb-4">
                        <i class="mgc_upload_3_line text-5xl text-gray-300 dark:text-gray-200"></i>
                    </div>
                    <h5 class="text-2xl text-gray-600 dark:text-gray-200">Drop files here or click to upload.</h5>
                </div>
            </form>

            <!-- Form fields with larger text and spacing -->
            <div class="space-y-6"> <!-- Added vertical spacing between fields -->
                <div class="mb-4">
                    <label for="allowDedSelect" class="block text-lg font-medium text-gray-700 mb-2">Allow/Ded</label>
                    <select id="allow_id" name="allow_id" class="employee-select w-full p-3 text-lg rounded-md border-gray-300">
                        <option value="">Select Item</option>
                        <?php foreach ($selectDrops as $selectDrop) : ?>
                            <option value="<?= $selectDrop['ed_id'] ?>"><?= $selectDrop['ed'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="counter" class="block text-lg font-medium text-gray-700 mb-2">Counter</label>
                    <input type="number" value="0" id="counter" name="counter" min="0"
                           class="mt-1 block w-full p-3 text-lg border border-gray-300 rounded-md">
                </div>

                <div class="mb-4">
                    <label for="add_remove" class="block text-lg font-medium text-gray-700 mb-2">Add/Remove</label>
                    <select id="add_remove" name="add_remove"
                            class="employee-select w-full p-3 text-lg rounded-md border-gray-300">
                        <option value="1">Add Item</option>
                        <option value="-1">Remove Item</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="verification" class="block text-lg font-medium text-gray-700 mb-2">Verification Column</label>
                    <select id="verification" name="verification"
                            class="employee-select w-full p-3 text-lg rounded-md border-gray-300">
                        <option value="staff_id">Staff ID</option>
                        <option value="NAME">Name</option>
                    </select>
                </div>
            </div>

            <!-- Buttons with larger size -->
            <div class="text-center mt-8 space-x-4">
                <button type="button" id="send-files"
                        class="btn bg-violet-500 border-violet-500 text-white px-6 py-3 text-lg rounded-lg hover:bg-violet-600">
                    Send Files
                </button>

                <a href="template.xlsx"
                   class="btn bg-blue-600 border-blue-600 text-white px-6 py-3 text-lg rounded-lg hover:bg-blue-700"
                   download>
                    Download Template
                </a>
            </div>
        </div>
    </div>
</div>
<div class="backdrop" id="backdrop">
    <div class="spinner">Processing</div>
</div>
<script>
    Dropzone.autoDiscover = false;
    var myDropzone = new Dropzone("#file-dropzone", {
        url: "libs/process_upload.php",
        autoProcessQueue: false,
        acceptedFiles: ".xlsx",
        init: function() {
            var dz = this;

            document.getElementById("send-files").addEventListener("click", function() {
                // Get all required field values
                var counter = $('#counter').val();
                var allow_id = $('#allow_id').val();
                var add_remove = $('#add_remove').val();
                var verification = $('#verification').val();

                // Validate all fields
                if (!allow_id) {
                    displayAlert('Please select an Allow/Ded item', 'center', 'error');
                    return;
                }

                if (counter === '' || counter < 0) {
                    displayAlert('Please enter a valid counter value (0 or greater)', 'center', 'error');
                    return;
                }

                if (!add_remove) {
                    displayAlert('Please select Add/Remove option', 'center', 'error');
                    return;
                }

                if (!verification) {
                    displayAlert('Please select a verification column', 'center', 'error');
                    return;
                }

                // Check if file is uploaded
                if (dz.getQueuedFiles().length === 0) {
                    displayAlert('Please upload an Excel file', 'center', 'error');
                    return;
                }

                if (dz.getQueuedFiles().length > 0) {
                    document.getElementById("backdrop").style.display = "flex";
                    dz.processQueue();
                } else {
                    displayAlert('No file uploaded','center','error');
                }
            });

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

            dz.on("complete", function(file) {
                dz.removeFile(file);
            });

            dz.on("success", function(file, response) {
                document.getElementById("backdrop").style.display = "none";
                if (response.status = 'success') {
                    displayAlert(response.message, 'center', 'success');
                } else {
                    displayAlert(response.message, 'center', 'error');
                }
            });

            dz.on("error", function(file, response) {
                document.getElementById("backdrop").style.display = "none";
                var errorMessage = (typeof response === 'object' && response.message) ? response.message : 'An error occurred during upload.';
                displayAlert(errorMessage, 'center', 'error');
            });
        }
    });

</script>
