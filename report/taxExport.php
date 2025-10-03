<?php
session_start();

include_once('../classes/model.php');
require_once('../Connections/paymaster.php');
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
	header("location: ../index.php");
	exit();
}

// Initialize variables
$month = '';
$period = isset($_POST['period']) ? $_POST['period'] : -1;

// Get period information
if ($period != -1) {
	try {
		$query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
		$query->execute(array($period));
		$result = $query->fetch(PDO::FETCH_ASSOC);
		if ($result) {
			$month = $result['description'] . '-' . $result['periodYear'];
		}
	} catch (PDOException $e) {
		$month = '';
	}
}

if (!function_exists("GetSQLValueString")) {
	function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
	{
		global $salary;

		$theValue = function_exists("mysql_real_escape_string") ? mysqli_real_escape_string($salary, $theValue) : mysqli_escape_string($salary, $theValue);

		switch ($theType) {
			case "text":
				$theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
				break;
			case "long":
			case "int":
				$theValue = ($theValue != "") ? intval($theValue) : "NULL";
				break;
			case "double":
				$theValue = ($theValue != "") ? doubleval($theValue) : "NULL";
				break;
			case "date":
				$theValue = ($theValue != "") ? "'" . $theValue . "'" : "NULL";
				break;
			case "defined":
				$theValue = ($theValue != "") ? $theDefinedValue : $theNotDefinedValue;
				break;
		}
		return $theValue;
	}
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Export Report - OOUTH Salary Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    .header-gradient {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    }

    .card-shadow {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .btn-primary {
        background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
        transform: translateY(-1px);
    }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="header-gradient shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <img src="../img/header_logo.png" alt="OOUTH Logo" class="h-12 w-auto mr-4">
                    <div class="text-white">
                        <h1 class="text-xl font-bold">OOUTH Salary Management</h1>
                        <p class="text-blue-100 text-sm">Tax Export Report</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4 text-white">
                    <div class="text-sm">
                        <i class="fas fa-user mr-2"></i>
                        Welcome, <strong><?php echo $_SESSION['SESS_FIRST_NAME']; ?></strong>
                    </div>
                    <div class="text-sm">
                        <i class="fas fa-clock mr-2"></i>
                        <?php echo date('l, F d, Y'); ?>
                    </div>
                    <a href="../logout.php" class="text-white hover:text-blue-200 transition-colors">
                        <i class="fas fa-sign-out-alt mr-1"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <?php include("report_sidebar_modern.php"); ?>

        <!-- Main Content -->
        <div class="flex-1 p-6">
            <!-- Breadcrumb -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="../home.php"
                            class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">

                            <i class="fas fa-home w-4 h-4 mr-2"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                            <a href="index.php"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2">Reports</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">Tax Export</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Page Title -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-file-export text-blue-600 mr-3"></i>
                    Tax Export Report
                </h1>
                <p class="text-gray-600 mt-2">Generate and export tax computation reports for selected pay periods</p>
            </div>

            <!-- Report Header -->
            <div class="bg-white rounded-lg card-shadow p-8 mb-6">
                <div class="text-center">
                    <img src="../img/oouth_logo.gif" alt="OOUTH Logo" class="mx-auto mb-4 h-16 w-auto">
                    <h2 class="text-2xl font-bold text-gray-900 uppercase">
                        Olabisi Onabanjo University Teaching Hospital
                    </h2>
                    <h3 class="text-xl text-gray-700 mt-2">
                        Tax Export for the Month of
                        <span class="font-semibold text-blue-600"><?php echo $month; ?></span>
                    </h3>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-lg card-shadow p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-cog text-blue-600 mr-2"></i>
                        Report Configuration
                    </h3>
                </div>

                <form method="POST" action="taxcomputation.php" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>
                                Pay Period
                            </label>
                            <div class="relative">
                                <select name="period" id="period"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                                    <option value="">Select Pay Period</option>
                                    <?php
                                    try {
                                        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE payrollRun = ? order by periodId desc');
                                        $query->execute(array('1'));
                                        $periods = $query->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($periods as $row) {
                                            $selected = ($row['periodId'] == $_SESSION['currentactiveperiod']) ? 'selected' : '';
                                            echo '<option value="' . $row['periodId'] . '" ' . $selected . '>';
                                            echo $row['description'] . ' - ' . $row['periodYear'];
                                            echo '</option>';
                                        }
                                    } catch (PDOException $e) {
                                        echo '<option value="">Error loading periods</option>';
                                    }
                                    ?>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" name="generate_report"
                            class="btn-primary text-white px-8 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center">
                            <i class="fas fa-play mr-2"></i>
                            Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <footer class="mt-12 text-center text-gray-600 text-sm">
                <div class="bg-white rounded-lg card-shadow p-6">
                    <p>Please visit our
                        <a href="http://www.oouth.com/" target="_blank"
                            class="text-blue-600 hover:text-blue-800 transition-colors">
                            website
                        </a>
                        to learn the latest information about the project.
                    </p>
                    <div class="mt-2">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            Version 14.1
                        </span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="text-gray-700 font-medium">Generating report...</span>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Form submission handling
        $('form').on('submit', function(e) {
            const period = $('#period').val();

            if (!period) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Selection Required',
                    text: 'Please select a pay period before generating the report.',
                    confirmButtonColor: '#1E40AF'
                });
                return false;
            }

            // Show loading overlay
            $('#loading-overlay').removeClass('hidden');
        });

        // Period change handling
        $('#period').on('change', function() {
            const selectedPeriod = $(this).val();
            if (selectedPeriod) {
                // Update the month display if needed
                // This could be enhanced with AJAX to fetch period details
            }
        });
    });
    </script>
</body>

</html>