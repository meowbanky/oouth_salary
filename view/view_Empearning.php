<?php
session_start();
require 'App.php';

$App = new App;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['delete'])) {
        $deletions = $_POST['delete'];
        $deleteSuccessfully = '';
        // Process each deletion
        foreach ($deletions as $delete) {
            list($temp_id, $staff_id) = explode('/', $delete);
            $array_deletions = [
                    ':temp_id' => $temp_id
            ];
            $deleteSuccessfully = $App->selectOne("DELETE FROM allow_deduc WHERE temp_id = :temp_id",$array_deletions);

        }
        echo "Selected allowances have been deleted.";
    }
}

if(isset($_POST['search'])) {
    $staff_id = $_POST['search'];
    $_SESSION['staff'] = $staff_id;
}else {
    $staff_id = $_SESSION['staff'];
}

    $queryEmployeeDetails = 'SELECT
	employee.staff_id, 
	employee.EMAIL, 
	employee.`NAME`, 
	tbl_dept.dept, 
	staff_status.`STATUS`, 
	employee.GRADE, 
	employee.STEP, 
	tbl_salaryType.SalaryType
    FROM
	employee
	LEFT JOIN
	tbl_dept
	ON 
		employee.DEPTCD = tbl_dept.dept_id
	LEFT JOIN
	staff_status
	ON 
		employee.STATUSCD = staff_status.STATUSCD
	LEFT JOIN
	tbl_salaryType
	ON 
		employee.SALARY_TYPE = tbl_salaryType.salaryType_id
	WHERE staff_id = :staff_id';

        $array = [
            ':staff_id' => $staff_id
        ];
        $employeeDetails = $App->selectOne($queryEmployeeDetails, $array);

        $queryEmpEarnings = "SELECT
	tbl_earning_deduction.ed,
	allow_deduc.`value`,
	allow_deduc.allow_id,allow_deduc.temp_id,
	tbl_earning_deduction.edType,
	allow_deduc.staff_id 
    FROM
	allow_deduc
	INNER JOIN tbl_earning_deduction ON allow_deduc.allow_id = tbl_earning_deduction.ed_id 
    WHERE staff_id = :staff_id AND edType = :edType";

        $earning_array = [
            ':staff_id' => $staff_id,
            ':edType' => 1
        ];
        $empAllowances = $App->selectAll($queryEmpEarnings, $earning_array);

        $queryEmpDeductions = "SELECT
	tbl_earning_deduction.ed,
	allow_deduc.`value`,
	allow_deduc.allow_id,allow_deduc.temp_id,
	tbl_earning_deduction.edType,
	allow_deduc.staff_id 
    FROM
	allow_deduc
	INNER JOIN tbl_earning_deduction ON allow_deduc.allow_id = tbl_earning_deduction.ed_id 
    WHERE staff_id = :staff_id AND edType = :edType";

        $deduction_array = [
            ':staff_id' => $staff_id,
            ':edType' => 2
        ];
        $empDeductions = $App->selectAll($queryEmpDeductions, $deduction_array);



?>

<div class="bg-white p-6 rounded-lg shadow-md">
<!-- Payroll Period -->
<div class="flex justify-between items-center mb-4">
    <div>
        <span class="font-bold">Current Payroll Period:</span> <?php echo $_SESSION['activeperiodDescription']; ?>
        <span class="inline-block ml-2 px-2 py-1 text-xs font-semibold text-green-800 bg-green-200 rounded-full">OPEN</span>
    </div>
    <button class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 focus:outline-none">
        Enter Staff Name or Staff ID
    </button>
</div>

<!-- Employee Information -->
<div class="mb-4">
    <p><span class="font-bold text-red-600">Emp # <?php echo $employeeDetails['staff_id'] ?>:</span> <?php echo $employeeDetails['NAME'] ?></p>
    <p><span class="font-bold">Grade Level:</span> <?php echo $employeeDetails['GRADE'] ?>/<?php echo $employeeDetails['STEP'] ?></p>
    <p><span class="font-bold">Dept:</span> <?php echo $employeeDetails['dept'] ?></p>
    <p><span class="font-bold">Status:</span>  <?php echo $employeeDetails['STATUS'] ?></p>
    <p><span class="font-bold">Salary Type:</span>  <?php echo $employeeDetails['SalaryType'] ?></p>
</div>

<!-- Earnings and Action Buttons Container -->
    <form id="deletionsForm" method="POST">
<div class="container mx-auto">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Earnings List -->
        <div class="md:col-span-2">
            <div class="p-4 bg-gray-200 rounded-t-lg">
                <div class="flex justify-between items-center">
                    <div class="flex space-x-4 w-full">
                        <span class="font-bold w-1/12">Code</span>
                        <span class="font-bold w-6/12">Description</span>
                        <span class="font-bold w-3/12">Amount</span>
                        <button id="deleteSelected" class="w-2/12 bg-red-500 text-white px-2 py-1 rounded-lg hover:bg-red-600 focus:outline-none">Delete</button>
                    </div>
                </div>
            </div>
            <div class="space-y-2">
                <div class="text-left pt-5">
                    <span class="font-bold">Earnings</span>
                </div>
                <?php
                $gross = 0;
                if ($empAllowances) {
                foreach ($empAllowances as $empAllowance){
                ?>
                <div class="flex items-center justify-between p-4 border-b">
                    <div class="flex items-center space-x-4 w-full">
                        <span class="w-1/12"><?php echo $empAllowance['allow_id'];?></span>
                        <span class="w-6/12"><?php echo $empAllowance['ed'];?></span>
                        <span class="w-3/12"><?php echo number_format($empAllowance['value']);?></span>
                        <div>
                            <input class="form-checkbox rounded text-danger text-right" type="checkbox" name="delete[]" value="<?php echo $empAllowance['temp_id'].'/'.$empAllowance['staff_id'] ?>">

                        </div>
                    </div>
                </div>
             <?php $gross = $gross+$empAllowance['value'];
                }
                }?>
                <div class="flex items-center justify-between p-4 border-b bg-gray-200">
                    <div class="flex items-center space-x-4 w-full">
                        <span class="w-1/12"></span>
                        <span class="w-6/12 font-bold">Gross Salary</span>
                        <span class="w-3/12"><?php echo number_format($gross);?></span>
                    </div>
                </div>
                <!-- Deductions Header -->
                <div class="text-left pt-5">
                    <span class="font-bold">Deductions</span>
                </div>
                <?php $totalDeductions = 0;
                if ($empDeductions ) {
                foreach($empDeductions as $empDeduction){ ?>
                <div class="flex items-center justify-between p-4 border-b">
                    <div class="flex items-center space-x-4 w-full">
                        <span class="w-1/12"><?php echo $empDeduction['allow_id'];?></span>
                        <span class="w-6/12"><?php echo $empDeduction['ed'];?></span>
                        <span class="w-3/12"><?php echo number_format($empDeduction['value']);?></span>
                        <div>
                            <input class="form-checkbox rounded text-danger text-right" type="checkbox" name="delete[]" value="<?php echo $empDeduction['temp_id'].'/'.$empDeduction['staff_id'] ?>">
                        </div>
                    </div>
                </div>
                <?php $totalDeductions = $totalDeductions+$empDeduction['value'];
                }
                } ?>
                <div class="flex items-center justify-between p-4 border-b bg-gray-200">
                    <div class="flex items-center space-x-4 w-full">
                        <span class="w-1/12"></span>
                        <span class="w-6/12 font-bold">Total Deductions</span>
                        <span class="w-3/12"><?php echo number_format($totalDeductions);?></span>
                    </div>
                </div>
                <div class="flex items-center justify-between p-4 border-b bg-gray-200">
                    <div class="flex items-center space-x-4 w-full">
                        <span class="w-1/3 font-bold">NET PAY</span>
                        <span class="w-2/3 text-right font-bold"><?php echo number_format(intval($gross) - intval($totalDeductions)) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons Column -->
        <div class="md:col-span-1 space-y-4">
            <button class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 focus:outline-none w-full">ADD EARNING/DEDUCTION</button>
            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none w-full">ADD TEMP. DEDUCTION/ALLOWANCE</button>
            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none w-full">ADD LOAN/CORPORATE</button>
            <button class="bg-black text-white px-4 py-2 rounded-lg hover:bg-black-600 focus:outline-none w-full">DELETE THIS EMP PAYSLIP</button>
            <button class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:purple-blue-600 focus:outline-none w-full">VIEW EMPLOYEE PAYSLIP</button>
            <button class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 focus:outline-none w-full">RUN THIS EMPLOYEE'S PAYROLL</button>
            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none w-full">PRO-RATE ALLOW</button>
            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none w-full">UPDATE GRADE/STEP</button>
        </div>

    </div>
</div>
    </form>
</div>




    <script>
        $(document).ready(function() {
        $('#deleteSelected').click(function(event) {
            // Prevent the default form submission
            event.preventDefault();
            // Check if at least one checkbox is checked
            if ($('input[type="checkbox"]:checked').length === 0) {
                alert('Please select at least one Transactions// to delete.');
                return; // Stop the function if no checkboxes are checked
            }

            Swal.fire({
                title: "Are you sure you want to delete these Items?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {

                    // Get the values of checked checkboxes
                    var checkedValues = [];
                    $('#deletionsForm').find('input[name="delete[]"]:checked').each(function () {
                        checkedValues.push($(this).val());
                    });

                    // Display the values of checked checkboxes
                    if (checkedValues.length > 0) {

                        // Proceed with AJAX submission
                        var formData = $('#deletionsForm').find('input[name="delete[]"]:checked').serialize();

                        $.ajax({
                            url: 'libs/getEmpearning.php',
                            type: 'POST',
                            data: formData,
                            success: function (response) {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Selected Items has been deleted.",
                                    icon: "success"
                                });
                                $('#loadContent', window.parent.document).load('libs/getEmpearning.php');

                                // Optionally, you can refresh the page or update the DOM to reflect the changes
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                // Handle errors here
                                alert('Error: ' + textStatus + ' - ' + errorThrown);
                            }
                        });
                    } else {
                        alert('No checkboxes selected.');
                    }
                }
        });

    });
        });
</script>

