<?php
/**
 * API Management Dashboard
 * Admin interface for managing API keys, webhooks, and monitoring usage
 */

session_start();
require_once 'Connections/paymaster.php';

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

        // Destroy existing table if it exists
        if ($.fn.DataTable.isDataTable(`#${tableId}`)) {
            $(`#${tableId}`).DataTable().destroy();
        }

        // Initialize the table with AJAX data source
        $(`#${tableId}`).DataTable({
            ajax: {
                url: 'api_management_data.php?action=' + tabName,
                dataSrc: 'data',
                error: function(xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error, thrown);
                }
            },
            columns: getColumnsForTable(tableId),
            pageLength: 25,
            order: [
                [0, 'desc']
            ],
            responsive: true,
            language: {
                emptyTable: "No data available",
                loadingRecords: "Loading...",
                processing: "Processing..."
            }
        });

        initializedTables[tableId] = true;
    }

    // Column configurations for each table
    function getColumnsForTable(tableId) {
        const configs = {
            organizationsTable: [{
                    data: 'org_name',
                    title: 'Organization'
                },
                {
                    data: 'org_code',
                    title: 'Code'
                },
                {
                    data: 'contact_email',
                    title: 'Email'
                },
                {
                    data: 'rate_limit_per_min',
                    title: 'Rate Limit'
                },
                {
                    data: 'is_active',
                    title: 'Status',
                    render: function(data) {
                        return data == 1 ?
                            '<span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">Active</span>' :
                            '<span class="px-2 py-1 bg-red-100 text-red-800 rounded text-sm">Inactive</span>';
                    }
                },
                {
                    data: 'created_at',
                    title: 'Created',
                    render: function(data) {
                        return data ? new Date(data).toLocaleDateString() : 'N/A';
                    }
                },
                {
                    data: null,
                    title: 'Actions',
                    orderable: false,
                    render: function(data) {
                        return '<button class="text-blue-600 hover:text-blue-800"><i class="fas fa-edit"></i></button>';
                    }
                }
            ],
            apiKeysTable: [{
                    data: 'api_key',
                    title: 'API Key'
                },
                {
                    data: 'org_name',
                    title: 'Organization'
                },
                {
                    data: 'ed_name',
                    title: 'Resource'
                },
                {
                    data: 'resource_type',
                    title: 'Type'
                },
                {
                    data: 'is_active',
                    title: 'Status',
                    render: function(data) {
                        return data == 1 ?
                            '<span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">Active</span>' :
                            '<span class="px-2 py-1 bg-red-100 text-red-800 rounded text-sm">Inactive</span>';
                    }
                },
                {
                    data: 'total_requests',
                    title: 'Requests'
                },
                {
                    data: 'last_used_at',
                    title: 'Last Used',
                    render: function(data) {
                        return data ? new Date(data).toLocaleString() : 'Never';
                    }
                },
                {
                    data: null,
                    title: 'Actions',
                    orderable: false,
                    render: function(data) {
                        return '<button class="text-blue-600 hover:text-blue-800"><i class="fas fa-eye"></i></button>';
                    }
                }
            ],
            webhooksTable: [{
                    data: 'webhook_name',
                    title: 'Name'
                },
                {
                    data: 'org_name',
                    title: 'Organization'
                },
                {
                    data: 'url',
                    title: 'URL'
                },
                {
                    data: 'events',
                    title: 'Events',
                    render: function(data) {
                        try {
                            const events = typeof data === 'string' ? JSON.parse(data) : data;
                            return Array.isArray(events) ? events.length + ' events' : '0 events';
                        } catch (e) {
                            return '0 events';
                        }
                    }
                },
                {
                    data: 'is_active',
                    title: 'Status',
                    render: function(data) {
                        return data == 1 ?
                            '<span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">Active</span>' :
                            '<span class="px-2 py-1 bg-red-100 text-red-800 rounded text-sm">Inactive</span>';
                    }
                },
                {
                    data: 'success_rate',
                    title: 'Success Rate',
                    render: function(data) {
                        return data + '%';
                    }
                },
                {
                    data: 'last_delivery_at',
                    title: 'Last Delivery',
                    render: function(data) {
                        return data ? new Date(data).toLocaleString() : 'Never';
                    }
                },
                {
                    data: null,
                    title: 'Actions',
                    orderable: false,
                    render: function(data) {
                        return '<button class="text-blue-600 hover:text-blue-800"><i class="fas fa-paper-plane"></i></button>';
                    }
                }
            ],
            logsTable: [{
                    data: 'request_id',
                    title: 'Request ID'
                },
                {
                    data: 'org_name',
                    title: 'Organization'
                },
                {
                    data: 'endpoint',
                    title: 'Endpoint'
                },
                {
                    data: 'method',
                    title: 'Method'
                },
                {
                    data: 'response_status',
                    title: 'Status',
                    render: function(data) {
                        const colorClass = data < 300 ? 'green' : data < 400 ? 'blue' : 'red';
                        return `<span class="px-2 py-1 bg-${colorClass}-100 text-${colorClass}-800 rounded text-sm">${data}</span>`;
                    }
                },
                {
                    data: 'response_time_ms',
                    title: 'Time',
                    render: function(data) {
                        return data + ' ms';
                    }
                },
                {
                    data: 'ip_address',
                    title: 'IP Address'
                },
                {
                    data: 'request_timestamp',
                    title: 'Timestamp',
                    render: function(data) {
                        return new Date(data).toLocaleString();
                    }
                }
            ],
            alertsTable: [{
                    data: 'severity',
                    title: 'Severity',
                    render: function(data) {
                        const colors = {
                            low: 'blue',
                            medium: 'yellow',
                            high: 'orange',
                            critical: 'red'
                        };
                        const color = colors[data] || 'gray';
                        return `<span class="px-2 py-1 bg-${color}-100 text-${color}-800 rounded text-sm uppercase">${data}</span>`;
                    }
                },
                {
                    data: 'alert_type',
                    title: 'Type'
                },
                {
                    data: 'org_name',
                    title: 'Organization'
                },
                {
                    data: 'description',
                    title: 'Description'
                },
                {
                    data: 'ip_address',
                    title: 'IP Address'
                },
                {
                    data: 'status',
                    title: 'Status',
                    render: function(data) {
                        return data === 'Resolved' ?
                            '<span class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">Resolved</span>' :
                            '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-sm">Pending</span>';
                    }
                },
                {
                    data: 'created_at',
                    title: 'Created',
                    render: function(data) {
                        return new Date(data).toLocaleString();
                    }
                },
                {
                    data: null,
                    title: 'Actions',
                    orderable: false,
                    render: function(data) {
                        return data.status === 'Pending' ?
                            '<button class="text-green-600 hover:text-green-800"><i class="fas fa-check"></i></button>' :
                            '';
                    }
                }
            ]
        };

        return configs[tableId] || [];
    }

    // Initialize first table on load
    $(document).ready(function() {
        initializeDataTable('organizations');
    });

    // Modal functions
    function openModal(modalId) {
        if (modalId === 'newOrganizationModal') {
            showNewOrganizationModal();
        } else if (modalId === 'newApiKeyModal') {
            showNewApiKeyModal();
        } else {
            Swal.fire({
                title: 'Coming Soon',
                text: 'This feature will be implemented in a future update',
                icon: 'info'
            });
        }
    }

    function refreshLogs() {
        if ($.fn.DataTable.isDataTable('#logsTable')) {
            $('#logsTable').DataTable().ajax.reload();
            Swal.fire({
                title: 'Refreshed!',
                text: 'Latest request logs loaded',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    }
    
    // Show new organization modal
    function showNewOrganizationModal() {
        Swal.fire({
            title: '<i class="fas fa-building mr-2"></i>New Organization',
            html: `
                <div class="text-left">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Organization Name *</label>
                        <input type="text" id="org_name" class="w-full border rounded-lg px-3 py-2" placeholder="e.g., Finance Department">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Organization Code *</label>
                        <input type="text" id="org_code" class="w-full border rounded-lg px-3 py-2" placeholder="e.g., FIN_DEPT" style="text-transform: uppercase;">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email *</label>
                        <input type="email" id="contact_email" class="w-full border rounded-lg px-3 py-2" placeholder="email@example.com">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                        <input type="text" id="contact_phone" class="w-full border rounded-lg px-3 py-2" placeholder="+234...">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rate Limit (requests/min)</label>
                        <input type="number" id="rate_limit" class="w-full border rounded-lg px-3 py-2" value="500">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Allowed IPs (optional, comma-separated)</label>
                        <input type="text" id="allowed_ips" class="w-full border rounded-lg px-3 py-2" placeholder="192.168.1.1, 10.0.0.1">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Create Organization',
            confirmButtonColor: '#3b82f6',
            width: '600px',
            preConfirm: () => {
                const orgName = $('#org_name').val();
                const orgCode = $('#org_code').val();
                const contactEmail = $('#contact_email').val();
                
                if (!orgName || !orgCode || !contactEmail) {
                    Swal.showValidationMessage('Please fill in all required fields');
                    return false;
                }
                
                return {
                    org_name: orgName,
                    org_code: orgCode.toUpperCase(),
                    contact_email: contactEmail,
                    contact_phone: $('#contact_phone').val(),
                    rate_limit: $('#rate_limit').val(),
                    allowed_ips: $('#allowed_ips').val()
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                createOrganization(result.value);
            }
        });
    }
    
    // Create organization
    function createOrganization(data) {
        $.ajax({
            url: 'api_management_actions.php',
            method: 'POST',
            data: { action: 'create_organization', ...data },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,
                        icon: 'success'
                    }).then(() => {
                        // Reload organizations table
                        if ($.fn.DataTable.isDataTable('#organizationsTable')) {
                            $('#organizationsTable').DataTable().ajax.reload();
                        }
                    });
                } else {
                    Swal.fire('Error', response.error, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Failed to create organization: ' + error, 'error');
            }
        });
    }
    
    // Show new API key modal
    function showNewApiKeyModal() {
        // First, load organizations and allowances/deductions
        $.ajax({
            url: 'api_management_actions.php',
            method: 'POST',
            data: { action: 'get_allowances_deductions' },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showApiKeyForm(response.allowances, response.deductions);
                } else {
                    Swal.fire('Error', 'Failed to load data', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to load form data', 'error');
            }
        });
    }
    
    function showApiKeyForm(allowances, deductions) {
        const allowancesOptions = allowances.map(a => `<option value="${a.ed_id}">${a.ed_name}</option>`).join('');
        const deductionsOptions = deductions.map(d => `<option value="${d.ed_id}">${d.ed_name}</option>`).join('');
        const orgsOptions = <?php echo json_encode(array_map(function($org) {
            return ['org_id' => $org['org_id'], 'org_name' => $org['org_name'], 'is_active' => $org['is_active']];
        }, $organizations)); ?>.filter(o => o.is_active == 1).map(o => `<option value="${o.org_id}">${o.org_name}</option>`).join('');
        
        Swal.fire({
            title: '<i class="fas fa-key mr-2"></i>Generate API Key',
            html: `
                <div class="text-left">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Organization *</label>
                        <select id="api_org_id" class="w-full border rounded-lg px-3 py-2">
                            <option value="">Select Organization</option>
                            ${orgsOptions}
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Resource Type *</label>
                        <select id="api_ed_type" class="w-full border rounded-lg px-3 py-2" onchange="toggleResourceList()">
                            <option value="">Select Type</option>
                            <option value="1">Allowance</option>
                            <option value="2">Deduction</option>
                        </select>
                    </div>
                    <div class="mb-4" id="allowance_select" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Allowance *</label>
                        <select id="api_ed_id_allow" class="w-full border rounded-lg px-3 py-2">
                            <option value="">Select Allowance</option>
                            ${allowancesOptions}
                        </select>
                    </div>
                    <div class="mb-4" id="deduction_select" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Deduction *</label>
                        <select id="api_ed_id_deduc" class="w-full border rounded-lg px-3 py-2">
                            <option value="">Select Deduction</option>
                            ${deductionsOptions}
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rate Limit (requests/min)</label>
                        <input type="number" id="api_rate_limit" class="w-full border rounded-lg px-3 py-2" value="100">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiration Date (optional)</label>
                        <input type="date" id="api_expires_at" class="w-full border rounded-lg px-3 py-2">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Allowed IPs (optional, comma-separated)</label>
                        <input type="text" id="api_allowed_ips" class="w-full border rounded-lg px-3 py-2" placeholder="192.168.1.1, 10.0.0.1">
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Generate API Key',
            confirmButtonColor: '#3b82f6',
            width: '600px',
            didOpen: () => {
                window.toggleResourceList = function() {
                    const type = $('#api_ed_type').val();
                    if (type == '1') {
                        $('#allowance_select').show();
                        $('#deduction_select').hide();
                    } else if (type == '2') {
                        $('#allowance_select').hide();
                        $('#deduction_select').show();
                    } else {
                        $('#allowance_select').hide();
                        $('#deduction_select').hide();
                    }
                };
            },
            preConfirm: () => {
                const orgId = $('#api_org_id').val();
                const edType = $('#api_ed_type').val();
                const edId = edType == '1' ? $('#api_ed_id_allow').val() : $('#api_ed_id_deduc').val();
                
                if (!orgId || !edType || !edId) {
                    Swal.showValidationMessage('Please fill in all required fields');
                    return false;
                }
                
                return {
                    org_id: orgId,
                    ed_type: edType,
                    ed_id: edId,
                    rate_limit: $('#api_rate_limit').val(),
                    expires_at: $('#api_expires_at').val(),
                    allowed_ips: $('#api_allowed_ips').val()
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                generateApiKey(result.value);
            }
        });
    }
    
    // Generate API key
    function generateApiKey(data) {
        $.ajax({
            url: 'api_management_actions.php',
            method: 'POST',
            data: { action: 'generate_api_key', ...data },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        html: `
                            <div class="text-left">
                                <p class="mb-4">${response.message}</p>
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                                    <p class="text-yellow-700 font-bold">${response.warning}</p>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">API Key:</label>
                                    <code class="block bg-gray-100 p-2 rounded text-sm break-all">${response.api_key}</code>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">API Secret:</label>
                                    <code class="block bg-gray-100 p-2 rounded text-sm break-all">${response.api_secret}</code>
                                </div>
                            </div>
                        `,
                        icon: 'success',
                        width: '600px'
                    }).then(() => {
                        // Reload API keys table
                        if ($.fn.DataTable.isDataTable('#apiKeysTable')) {
                            $('#apiKeysTable').DataTable().ajax.reload();
                        }
                    });
                } else {
                    Swal.fire('Error', response.error, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Failed to generate API key: ' + error, 'error');
            }
        });
    }
    </script>

</body>

</html>