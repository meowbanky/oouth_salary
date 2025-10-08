<?php
/**
 * API Management Dashboard
 * Admin interface for managing API keys, webhooks, and monitoring usage
 */

session_start();
require_once 'includes/dbConnection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['SESS_MEMBER_ID']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit();
}

$page_title = "API Management";

// Fetch organizations
try {
    $stmt = $conn->prepare('SELECT * FROM api_organizations ORDER BY org_name');
    $stmt->execute();
    $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $organizations = [];
}

// Fetch allowances and deductions for key generation
try {
    $stmt = $conn->prepare('SELECT ed_id, ed_name, types FROM tbl_earning_deduction ORDER BY ed_name');
    $stmt->execute();
    $earningDeductions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $earningDeductions = [];
}

// Get API statistics
try {
    $stats = [];
    
    // Total API keys
    $stmt = $conn->prepare('SELECT COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active FROM api_keys');
    $stmt->execute();
    $stats['keys'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Requests last 24 hours
    $stmt = $conn->prepare('SELECT COUNT(*) as total, AVG(response_time_ms) as avg_time FROM api_request_logs WHERE request_timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)');
    $stmt->execute();
    $stats['requests_24h'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Active webhooks
    $stmt = $conn->prepare('SELECT COUNT(*) FROM api_webhooks WHERE is_active = 1');
    $stmt->execute();
    $stats['active_webhooks'] = $stmt->fetchColumn();
    
    // Security alerts (unresolved)
    $stmt = $conn->prepare('SELECT COUNT(*) FROM api_security_alerts WHERE is_resolved = 0');
    $stmt->execute();
    $stats['security_alerts'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $stats = [
        'keys' => ['total' => 0, 'active' => 0],
        'requests_24h' => ['total' => 0, 'avg_time' => 0],
        'active_webhooks' => 0,
        'security_alerts' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - OOUTH Salary System</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Dark Mode CSS -->
    <link rel="stylesheet" href="css/dark-mode.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Theme Manager -->
    <script src="js/theme-manager.js"></script>

    <style>
    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .tab-button.active {
        border-bottom: 3px solid #3b82f6;
        color: #3b82f6;
    }

    .code-block {
        background: #1e293b;
        color: #e2e8f0;
        padding: 1rem;
        border-radius: 0.5rem;
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        overflow-x: auto;
    }
    </style>
</head>

<body class="bg-gray-100">

    <?php include 'header.php'; ?>

    <div class="flex">
        <?php include 'sidebar.php'; ?>

        <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
            <!-- Breadcrumb -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li><a href="home.php" class="text-gray-700 hover:text-blue-600"><i class="fas fa-home"></i>
                            Home</a></li>
                    <li><span class="mx-2 text-gray-400">/</span></li>
                    <li class="text-gray-500"><?php echo $page_title; ?></li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><i class="fas fa-code mr-2"></i>API Management</h1>
                    <p class="text-gray-600">Manage API keys, webhooks, and monitor usage</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="openModal('newOrganizationModal')"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-building mr-2"></i>New Organization
                    </button>
                    <button onclick="openModal('newApiKeyModal')"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-key mr-2"></i>Generate API Key
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">API Keys</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['keys']['active']; ?></p>
                            <p class="text-gray-500 text-xs">of <?php echo $stats['keys']['total']; ?> total</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-key text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Requests (24h)</p>
                            <p class="text-3xl font-bold text-gray-900">
                                <?php echo number_format($stats['requests_24h']['total']); ?></p>
                            <p class="text-gray-500 text-xs"><?php echo round($stats['requests_24h']['avg_time']); ?>ms
                                avg</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-chart-line text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Active Webhooks</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['active_webhooks']; ?></p>
                            <p class="text-gray-500 text-xs">configured</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-webhook text-purple-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Security Alerts</p>
                            <p
                                class="text-3xl font-bold text-<?php echo $stats['security_alerts'] > 0 ? 'red' : 'gray'; ?>-900">
                                <?php echo $stats['security_alerts']; ?>
                            </p>
                            <p class="text-gray-500 text-xs">unresolved</p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-shield-alt text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-lg shadow">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button class="tab-button active px-6 py-4 font-medium text-gray-700"
                            onclick="switchTab('organizations')">
                            <i class="fas fa-building mr-2"></i>Organizations
                        </button>
                        <button class="tab-button px-6 py-4 font-medium text-gray-700" onclick="switchTab('apiKeys')">
                            <i class="fas fa-key mr-2"></i>API Keys
                        </button>
                        <button class="tab-button px-6 py-4 font-medium text-gray-700" onclick="switchTab('webhooks')">
                            <i class="fas fa-webhook mr-2"></i>Webhooks
                        </button>
                        <button class="tab-button px-6 py-4 font-medium text-gray-700" onclick="switchTab('logs')">
                            <i class="fas fa-list mr-2"></i>Request Logs
                        </button>
                        <button class="tab-button px-6 py-4 font-medium text-gray-700" onclick="switchTab('alerts')">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Security Alerts
                        </button>
                        <button class="tab-button px-6 py-4 font-medium text-gray-700" onclick="switchTab('docs')">
                            <i class="fas fa-book mr-2"></i>Documentation
                        </button>
                    </nav>
                </div>

                <!-- Tab Contents -->
                <div class="p-6">

                    <!-- Organizations Tab -->
                    <div id="organizations" class="tab-content active">
                        <table id="organizationsTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>Organization Name</th>
                                    <th>Code</th>
                                    <th>Contact Email</th>
                                    <th>Rate Limit</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <!-- API Keys Tab -->
                    <div id="apiKeys" class="tab-content">
                        <table id="apiKeysTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>API Key</th>
                                    <th>Organization</th>
                                    <th>Resource</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Total Requests</th>
                                    <th>Last Used</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Webhooks Tab -->
                    <div id="webhooks" class="tab-content">
                        <div class="mb-4">
                            <button onclick="openModal('newWebhookModal')"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Register Webhook
                            </button>
                        </div>
                        <table id="webhooksTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Organization</th>
                                    <th>URL</th>
                                    <th>Events</th>
                                    <th>Status</th>
                                    <th>Success Rate</th>
                                    <th>Last Delivery</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Request Logs Tab -->
                    <div id="logs" class="tab-content">
                        <div class="mb-4 flex gap-2">
                            <select id="logOrgFilter" class="border rounded px-3 py-2">
                                <option value="">All Organizations</option>
                                <?php foreach ($organizations as $org): ?>
                                <option value="<?php echo $org['org_id']; ?>">
                                    <?php echo htmlspecialchars($org['org_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="logStatusFilter" class="border rounded px-3 py-2">
                                <option value="">All Status</option>
                                <option value="200">200 (Success)</option>
                                <option value="401">401 (Unauthorized)</option>
                                <option value="429">429 (Rate Limit)</option>
                                <option value="500">500 (Error)</option>
                            </select>
                            <button onclick="refreshLogs()"
                                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                <i class="fas fa-sync mr-2"></i>Refresh
                            </button>
                        </div>
                        <table id="logsTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Organization</th>
                                    <th>Endpoint</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Response Time</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Security Alerts Tab -->
                    <div id="alerts" class="tab-content">
                        <table id="alertsTable" class="display w-full">
                            <thead>
                                <tr>
                                    <th>Severity</th>
                                    <th>Alert Type</th>
                                    <th>Organization</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Documentation Tab -->
                    <div id="docs" class="tab-content">
                        <div class="prose max-w-none">
                            <h2 class="text-2xl font-bold mb-4">API Documentation</h2>

                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                                <p class="font-semibold"><i class="fas fa-info-circle mr-2"></i>API Base URL:</p>
                                <code
                                    class="text-sm"><?php echo (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']; ?>/api/v1</code>
                            </div>

                            <h3 class="text-xl font-bold mb-3">Authentication Flow</h3>
                            <ol class="list-decimal list-inside mb-6 space-y-2">
                                <li>Generate API key from this dashboard</li>
                                <li>Obtain JWT token using API key</li>
                                <li>Use JWT token for all subsequent requests</li>
                                <li>Refresh token before expiration (15 minutes)</li>
                            </ol>

                            <h3 class="text-xl font-bold mb-3">Example: Generate Token</h3>
                            <div class="code-block mb-6">
                                POST /api/v1/auth/token
                                Content-Type: application/json

                                {
                                "api_key": "oouth_001_allow_5_a8f3c9d2e1b4f6e7",
                                "timestamp": <?php echo time(); ?>,
                                "signature": "your_hmac_signature"
                                }
                            </div>

                            <h3 class="text-xl font-bold mb-3">Example: Get Allowance Data</h3>
                            <div class="code-block mb-6">
                                GET /api/v1/payroll/allowances/5?period=44
                                Authorization: Bearer eyJhbGc...
                                X-API-Key: oouth_001_allow_5_a8f3c9d2e1b4f6e7
                            </div>

                            <h3 class="text-xl font-bold mb-3">Rate Limits</h3>
                            <ul class="list-disc list-inside mb-6 space-y-1">
                                <li><strong>Per API Key:</strong> 100 requests per minute</li>
                                <li><strong>Per Organization:</strong> 500 requests per minute</li>
                                <li>Rate limit headers included in every response</li>
                            </ul>

                            <h3 class="text-xl font-bold mb-3">Response Formats</h3>
                            <p class="mb-2">Supported formats: JSON (default), XML, CSV</p>
                            <div class="code-block mb-6">
                                # JSON (default)
                                Accept: application/json

                                # XML
                                Accept: application/xml

                                # CSV
                                Accept: text/csv
                            </div>

                            <p class="mt-6">
                                <a href="api/README.md" target="_blank" class="text-blue-600 hover:underline">
                                    <i class="fas fa-external-link-alt mr-2"></i>View Full Documentation
                                </a>
                            </p>
                        </div>
                    </div>

                </div>
            </div>

        </main>
    </div>

    <!-- Modals will be added here -->

    <script>
    // Tab switching
    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });

        // Remove active class from all buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });

        // Show selected tab content
        document.getElementById(tabName).classList.add('active');

        // Add active class to clicked button
        event.target.closest('.tab-button').classList.add('active');

        // Initialize DataTables if not already initialized
        initializeDataTable(tabName);
    }

    // Initialize DataTables
    let initializedTables = {};

    function initializeDataTable(tabName) {
        const tableMap = {
            'organizations': 'organizationsTable',
            'apiKeys': 'apiKeysTable',
            'webhooks': 'webhooksTable',
            'logs': 'logsTable',
            'alerts': 'alertsTable'
        };

        const tableId = tableMap[tabName];
        if (!tableId || initializedTables[tableId]) return;

        // Initialize the table (data will be loaded via AJAX in next phase)
        $(`#${tableId}`).DataTable({
            pageLength: 25,
            order: [
                [0, 'desc']
            ]
        });

        initializedTables[tableId] = true;
    }

    // Initialize first table on load
    $(document).ready(function() {
        initializeDataTable('organizations');
    });

    // Modal functions
    function openModal(modalId) {
        Swal.fire({
            title: 'Coming Soon',
            text: 'This feature will be implemented in Phase 2',
            icon: 'info'
        });
    }

    function refreshLogs() {
        Swal.fire({
            title: 'Refreshing...',
            text: 'Loading latest request logs',
            icon: 'info',
            timer: 1500,
            showConfirmButton: false
        });
    }
    </script>

</body>

</html>