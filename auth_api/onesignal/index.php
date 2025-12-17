<?php
require_once __DIR__ . '/../config/Database.php';
$database = new Database();
$db = $database->getConnection();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="bg-white shadow-lg rounded-lg p-8 max-w-md w-full">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Send Notification</h2>
    <form id="notificationForm" action="save_notification.php" method="POST">
        <!-- Title Field -->
        <div class="mb-4">
            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
            <input
                type="text"
                id="title"
                name="title"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2"
                placeholder="Enter notification title"
                required
            />
        </div>

        <!-- Message Field -->
        <div class="mb-4">
            <label for="message" class="block text-sm font-medium text-gray-700">Message</label>
            <textarea
                id="message"
                name="message"
                rows="4"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2"
                placeholder="Enter notification message"
                required
            ></textarea>
        </div>

        <!-- Device ID Dropdown -->
        <div class="mb-4">
            <label for="staffDevice" class="block text-sm font-medium text-gray-700">Select Staff</label>
            <select
                id="staffDevice"
                name="staffDevice"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2"
                required
            >
                <option value="">Select Staff</option>
                <option value="all">All Subscribers</option>
                <?php
                // Fetch staff details with non-null device IDs
                $query = "SELECT tbl_users.staff_id, tbl_users.device_id, employee.NAME FROM tbl_users
                          INNER JOIN employee ON tbl_users.staff_id = employee.staff_id
                          WHERE ISNULL(tbl_users.device_id) = FALSE";
                $stmt = $db->query($query);

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $staffId = $row['staff_id'];
                    $deviceId = $row['device_id'];
                    $name = $row['NAME'];
                    echo "<option value='{$staffId},{$deviceId}'>{$name} (Staff No: {$staffId})</option>";
                }
                ?>
            </select>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition duration-150"
        >
            Send Notification
        </button>
    </form>
</div>
<script>
    $(document).ready(function() {
        $('#staffDevice').select2({
            placeholder: "Search staff...",
            allowClear: true
        });
    });
</script>
</body>
</html>
