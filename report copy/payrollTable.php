<?php
session_start();
ini_set('max_execution_time', '0');
include_once('../classes/model.php');
require_once('Connections/paymaster.php');

// Check session
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '')) {
    header("location: ../index.php");
    exit();
}

// Get SQL Value String function
if (!function_exists("GetSQLValueString")) {
    function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "")
    {
        global $con;
        $theValue = function_exists("mysql_real_escape_string") ?
            mysqli_real_escape_string($con, $theValue) : mysqli_escape_string($con, $theValue);

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

// Function to get default period
function getDefaultPeriod($conn) {
    $query = $conn->prepare("
        SELECT periodId, CONCAT(description,' - ', periodYear) as description
        FROM payperiods 
        WHERE periodid = (
            SELECT MAX(periodid)
            FROM payperiods 
            WHERE active = 0
        )
    ");
    $query->execute();
    return $query->fetch(PDO::FETCH_ASSOC);
}

// Get default period
$defaultPeriod = getDefaultPeriod($conn);
$periodFrom = isset($_GET['periodFrom']) ? $_GET['periodFrom'] : $defaultPeriod['periodId'];
$periodTo = isset($_GET['periodTo']) ? $_GET['periodTo'] : $defaultPeriod['periodId'];

// Get period description for display
function getPeriodDescription($conn, $periodId) {
    $query = $conn->prepare("
        SELECT CONCAT(description,' - ', periodYear) as description
        FROM payperiods 
        WHERE periodId = ?
    ");
    $query->execute([$periodId]);
    $result = $query->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['description'] : '';
}

$periodFromDesc = getPeriodDescription($conn, $periodFrom);
$periodToDesc = getPeriodDescription($conn, $periodTo);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payroll Report Generator</title>
    <?php include('../header1.php'); ?>
    <style>
        .report-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-section {
            margin: 20px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .email-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .hidden-print {
            margin-bottom: 20px;
        }
        .total-column {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .validation-error {
            color: red;
            display: none;
            margin-top: 5px;
        }
    </style>
</head>

<body data-color="grey" class="flat">
<div class="modal fade hidden-print" id="myModal"></div>
<div id="wrapper">
    <!-- Header Section -->
    <div id="header" class="hidden-print">
        <h1>
            <a href="../index.php">
                <img src="img/header_logo.png" class="hidden-print header-log" id="header-logo" alt="">
            </a>
        </h1>
        <a id="menu-trigger" href="#"><i class="fa fa-bars fa fa-2x"></i></a>
        <div class="clear"></div>
    </div>

    <!-- User Navigation -->
    <div id="user-nav" class="hidden-print hidden-xs">
        <ul class="btn-group">
            <li class="btn hidden-xs">
                <a title="" href="switch_user" data-toggle="modal" data-target="#myModal">
                    <i class="icon fa fa-user fa-2x"></i>
                    <span class="text">Welcome <b><?php echo $_SESSION['SESS_FIRST_NAME']; ?></b></span>
                </a>
            </li>
            <li class="btn hidden-xs disabled">
                <a title="" href="/" onclick="return false;">
                    <i class="icon fa fa-clock-o fa-2x"></i>
                    <span class="text">
                            <?php
                            $Today = date('y:m:d', time());
                            $new = date('l, F d, Y', strtotime($Today));
                            echo $new;
                            ?>
                        </span>
                </a>
            </li>
            <li class="btn">
                <a href="#"><i class="icon fa fa-cog"></i><span class="text">Settings</span></a>
            </li>
            <li class="btn">
                <a href="index.php"><i class="fa fa-power-off"></i><span class="text">Logout</span></a>
            </li>
        </ul>
    </div>

    <?php include("report_sidebar.php"); ?>

    <!-- Main Content -->
    <div id="content" class="clearfix sales_content_minibar">
        <div id="content-header" class="hidden-print">
            <h1><i class="fa fa-beaker"></i> Payroll Report Generator</h1>
            <span id="ajax-loader" style="display: none;">
                    <img src="img/ajax-loader.gif" alt="Loading..." />
                </span>
        </div>

        <div id="breadcrumb" class="hidden-print">
            <a href="../home.php"><i class="fa fa-home"></i> Dashboard</a>
            <a href="index.php">Reports</a>
            <a class="current" href="#">Generate Payroll Report</a>
        </div>

        <!-- Main Form Section -->
        <div class="row">
            <div class="col-md-12">
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="fa fa-align-justify"></i></span>
                        <h5>Generate Payroll Report</h5>
                    </div>

                    <div class="widget-content">
                        <!-- Organization Header -->
                        <div class="report-header">
                            <img src="img/oouth_logo.gif" width="10%" height="10%" alt="OOUTH Logo">
                            <h2>OLABISI ONABANJO UNIVERSITY TEACHING HOSPITAL</h2>
                            <h3>Payroll Report Generator</h3>
                        </div>

                        <!-- Form Section -->
                        <div class="form-section">
                            <form class="form-horizontal" method="GET" action="payroll_report.php" id="payrollForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Period From:</label>
                                            <select name="periodFrom" id="periodFrom" class="form-control" required>
                                                <option value="">Select Pay Period</option>
                                                <?php
                                                try {
                                                    $query = $conn->prepare('
                                                            SELECT payperiods.description, payperiods.periodYear, payperiods.periodId 
                                                            FROM payperiods 
                                                            WHERE payrollRun = ? 
                                                            ORDER BY periodId DESC
                                                        ');
                                                    $query->execute(['1']);
                                                    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                        $selected = ($row['periodId'] == $periodFrom) ? 'selected="selected"' : '';
                                                        echo sprintf(
                                                            '<option value="%s" %s>%s - %s</option>',
                                                            $row['periodId'],
                                                            $selected,
                                                            $row['description'],
                                                            $row['periodYear']
                                                        );
                                                    }
                                                } catch (PDOException $e) {
                                                    echo "Error: " . $e->getMessage();
                                                }
                                                ?>
                                            </select>
                                            <div class="validation-error" id="periodFromError">
                                                Please select a starting period
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="control-label">Period To:</label>
                                            <select name="periodTo" id="periodTo" class="form-control" required>
                                                <option value="">Select Pay Period</option>
                                                <?php
                                                try {
                                                    $query = $conn->prepare('
                                                            SELECT payperiods.description, payperiods.periodYear, payperiods.periodId 
                                                            FROM payperiods 
                                                            WHERE payrollRun = ? 
                                                            ORDER BY periodId DESC
                                                        ');
                                                    $query->execute(['1']);
                                                    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                                        $selected = ($row['periodId'] == $periodTo) ? 'selected="selected"' : '';
                                                        echo sprintf(
                                                            '<option value="%s" %s>%s - %s</option>',
                                                            $row['periodId'],
                                                            $selected,
                                                            $row['description'],
                                                            $row['periodYear']
                                                        );
                                                    }
                                                } catch (PDOException $e) {
                                                    echo "Error: " . $e->getMessage();
                                                }
                                                ?>
                                            </select>
                                            <div class="validation-error" id="periodToError">
                                                Please select an ending period
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Email Section -->
                                <div class="email-section">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="send_email" id="send_email"
                                                           onchange="toggleEmailField()">
                                                    Send Excel file via email
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group" id="email_field" style="display: none;">
                                                <label class="control-label">Email Address:</label>
                                                <input type="email" name="email_address" class="form-control"
                                                       placeholder="Enter email address">
                                                <div class="validation-error" id="emailError">
                                                    Please enter a valid email address
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Button -->
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary btn-large" id="submitButton">
                                        Generate Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div id="footer" class="col-md-12 hidden-print">
        Please visit our <a href="http://www.oouth.com/" target="_blank">website</a>
        to learn more about our organization.
        <span class="text-info"><span class="label label-info">14.1</span></span>
    </div>
</div>

<script type="text/javascript">
    function toggleEmailField() {
        const emailField = document.getElementById('email_field');
        const emailCheckbox = document.getElementById('send_email');
        emailField.style.display = emailCheckbox.checked ? 'block' : 'none';
    }

    document.getElementById('payrollForm').addEventListener('submit', function(e) {
        e.preventDefault();

        let isValid = true;

        // Reset error messages
        document.querySelectorAll('.validation-error').forEach(error => {
            error.style.display = 'none';
        });

        // Validate period selections
        if (!document.getElementById('periodFrom').value) {
            document.getElementById('periodFromError').style.display = 'block';
            isValid = false;
        }

        if (!document.getElementById('periodTo').value) {
            document.getElementById('periodToError').style.display = 'block';
            isValid = false;
        }

        // Validate email if checkbox is checked
        const emailCheckbox = document.getElementById('send_email');
        const emailInput = document.querySelector('input[name="email_address"]');

        if (emailCheckbox.checked) {
            if (!emailInput.value || !emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                document.getElementById('emailError').style.display = 'block';
                isValid = false;
            }
        }

        if (!isValid) {
            return false;
        }

        // Show loading indicator
        document.getElementById('ajax-loader').style.display = 'block';
        document.getElementById('submitButton').disabled = true;

        // If send_email is checked, use AJAX for JSON response
        if (emailCheckbox.checked) {
            const form = this;
            const formData = new FormData(form);
            const url = form.action;
            const queryString = new URLSearchParams(formData).toString();

            fetch(url + '?' + queryString, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(response => response.text()) // Get response as text to handle any Content-Type
                .then(text => {
                    try {
                        // Attempt to parse as JSON
                        const data = JSON.parse(text);
                        // Hide loading indicator
                        document.getElementById('ajax-loader').style.display = 'none';
                        document.getElementById('submitButton').disabled = false;

                        // Display the message in an alert
                        alert(data.message);

                        // Create and append a Back button
                        const formSection = document.querySelector('.form-section');
                        const backButton = document.createElement('button');
                        backButton.type = 'button';
                        backButton.className = 'btn btn-secondary';
                        backButton.innerText = 'Back';
                        backButton.onclick = function() {
                            window.history.back();
                        };
                        formSection.appendChild(backButton);
                    } catch (e) {
                        // If JSON parsing fails, show a generic success message
                        document.getElementById('ajax-loader').style.display = 'none';
                        document.getElementById('submitButton').disabled = false;
                        alert('Report has been sent successfully to ' + emailInput.value);

                        // Append Back button
                        const formSection = document.querySelector('.form-section');
                        const backButton = document.createElement('button');
                        backButton.type = 'button';
                        backButton.className = 'btn btn-secondary';
                        backButton.innerText = 'Back';
                        backButton.onclick = function() {
                            window.history.back();
                        };
                        // formSection.appendChild(backButton);
                    }
                })
                .catch(error => {
                    // Handle network or other errors
                    document.getElementById('ajax-loader').style.display = 'none';
                    document.getElementById('submitButton').disabled = false;
                    alert('An error occurred, but the report may have been sent. Please check your email.');
                });
        } else {
            // For download, submit the form traditionally
            document.getElementById('ajax-loader').style.display = 'none';
            document.getElementById('submitButton').disabled = false;
            this.submit();
        }
    });
</script>
</body>
</html>