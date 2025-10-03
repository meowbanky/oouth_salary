<?php
require_once('../Connections/paymaster.php');
include_once('../classes/model.php');
require_once('../libs/App.php');
$App = new App();
$App->checkAuthentication();
require_once('../libs/middleware.php');
checkPermission();

// Initialize variables
$month = '';
$period = isset($_POST['period']) ? $_POST['period'] : (isset($_GET['period']) ? $_GET['period'] : -1);

// Get period information
if ($period != -1) {
    try {
        $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear FROM payperiods WHERE periodId = ?');
        $query->execute([$period]);
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $month = $result['description'] . '-' . $result['periodYear'];
        }
    } catch (PDOException $e) {
        $month = '';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php include('../header.php'); ?>
    <div class="flex min-h-screen">
        <?php include('report_sidebar_modern.php'); ?>
        <!-- Breadcrumb Navigation -->
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


        <main class="flex-1 px-2 md:px-8 py-4 flex flex-col">
            <div class="w-full max-w-7xl mx-auto flex-1 flex flex-col">
                <!-- Header Section -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-file-export"></i> Tax Export Report
                        </h1>
                        <p class="text-sm text-blue-700/70 mt-1">Generate and export tax computation reports for selected pay periods.</p>
                    </div>
                </div>

                <!-- Report Form -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-6">
                    <div class="bg-blue-50 px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-blue-800 flex items-center gap-2">
                            <i class="fas fa-filter"></i> Report Parameters
                        </h2>
                    </div>
                    <div class="p-6">
                        <form method="POST" action="taxcomputation.php" class="space-y-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <label for="period" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-calendar-alt mr-2 text-blue-600"></i>Pay Period
                                    </label>
                                    <select name="period" id="period"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                                        required>
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
                                </div>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit" name="generate_report"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                                    <i class="fas fa-play"></i>
                                    Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Header -->
                <?php if ($month): ?>
                <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
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
                <?php endif; ?>

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

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl p-6 flex items-center space-x-4 shadow-2xl">
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
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: 'OK'
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