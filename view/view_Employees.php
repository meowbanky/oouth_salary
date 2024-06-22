<?php
include '../libs/getEmpEarnings.php';
?>
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #payslipModal, #payslipModal * {
            visibility: visible;
        }
        #payslipModal {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }

</style>
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
                <div class="flex items-center justify-between p-4 border-b bg-blue-600 text-white font-bold">
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
                <div class="flex items-center justify-between p-4 border-b bg-red-600 font-bold text-white">
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
            <button class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 focus:outline-none w-full" id="openModalButton">ADD EARNING/DEDUCTION</button>
            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none w-full" id="openModalProrate">PRO-RATE ALLOW</button>
<!--            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none w-full" id="updategrade" >UPDATE GRADE/STEP</button>-->
<!--            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none w-full">ADD TEMP. DEDUCTION/ALLOWANCE</button>-->
<!--            <button class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 focus:outline-none w-full">ADD LOAN/CORPORATE</button>-->
            <button id="emailButton" type="button" class="<?php if(!$payslipStatus){echo 'bg-gray-500'; }else{ echo 'bg-red-600';} ?> text-white px-4 py-2 rounded-lg hover:bg-black-600 focus:outline-none w-full" <?php if(!$payslipStatus){echo 'disabled="disabled"'; } ?>>Email</button>
            <button class="<?php if(!$payslipStatus){echo 'bg-gray-500'; }else{ echo 'bg-black';} ?> text-white px-4 py-2 rounded-lg hover:bg-black-600 focus:outline-none w-full" <?php if(!$payslipStatus){echo 'disabled="disabled"'; } ?> id="deletepayslip">DELETE THIS EMP PAYSLIP</button>
            <button class="<?php if(!$payslipStatus){echo 'bg-gray-500'; }else{ echo 'bg-purple-500';} ?> text-white px-4 py-2 rounded-lg hover:purple-blue-600 focus:outline-none w-full" <?php if(!$payslipStatus){echo 'disabled="disabled"'; } ?> id="viewpayslipModal">VIEW EMPLOYEE PAYSLIP</button>
            <button class="<?php if(!$payslipStatus){echo 'bg-purple-500'; }else{ echo 'bg-gray-500';} ?> text-white px-4 py-2 rounded-lg hover:bg-purple-600 focus:outline-none w-full" <?php if($payslipStatus){echo 'disabled="disabled"'; } ?> id="runpayslip">RUN THIS EMPLOYEE'S PAYROLL</button>
        </div>

    </div>
</div>
    </form>
</div>
<!--Add allowance/deduction modal-->
<div id="modal_addAllowDed" class="closemodal fixed inset-0 flex items-center justify-center z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity"></div>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden transform transition-all sm:w-full sm:max-w-lg">
        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
            <div class="text-center">
                <h3 class="font-semibold leading-6 text-gray-900" id="modal-title">Add Allowance/Deduction</h3>
            </div>
            <form>
                <input type="hidden" id="staff_id" name="staff_id" value="<?php echo $employeeDetails['staff_id']?>">
                <input type="hidden" id="grade" name="grade" value="<?php echo $employeeDetails['GRADE']?>">
                <input type="hidden" id="step" name="step" value="<?php echo $employeeDetails['STEP']?>">
                <input type="hidden" id="salaryType" name="salaryType" value="<?php echo $employeeDetails['SALARY_TYPE']?>">
                <div class="grid grid-cols-1 sm:grid-cols-1 gap-1">

                    <!-- Right Column for Form Elements -->
                    <div>
                        <div class="bg-white p-2 rounded-lg">
                            <select id="allowDedSelect" name="allowDedSelect" class="employee-select w-full mt-1 block rounded-md border-gray-300">
                                <option value="">Select Item</option>
                                <?php
                                foreach ($selectDrops as $selectDrop) {
                                    echo '<option value="' . $selectDrop['ed_id'] . '">' . $selectDrop['ed'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="bg-white p-2 rounded-lg">
                            <input placeholder="amount"  disabled type="number" pattern="[0-9]*" name="amount" id="amount" class="w-full mt-1 block rounded-md border-gray-300">
                        </div>
                        <div class="bg-white p-2 rounded-lg">
                            <input placeholder="Run time"  type="number" pattern="[0-9]*" name="runtime" id="runtime" value="0" class="w-full mt-1 block rounded-md border-gray-300">
                              </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-1">
            <button type="button" id="saveButton" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Save</button>
            <button type="button" id="cancelButton" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto closebutton">Cancel</button>
        </div>
    </div>
</div>

<!--Prorate Modal-->
<div id="modal_prorate" class="closemodal fixed inset-0 flex items-center justify-center z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity"></div>
    <div class="scrollable-content bg-white rounded-lg shadow-lg overflow-y-scroll transform transition-all sm:w-full sm:max-w-lg max-h-screen flex flex-col">
        <div class="bg-blue-600 px-4 py-3">
            <h3 class="text-lg font-semibold text-white text-center" id="modal-title">PRO-RATE ALLOWANCES FOR <br><?php echo $employeeDetails['NAME'] ?></h3>
        </div>
        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 overflow-y-auto flex-1">
            <div class="grid grid-cols-1 gap-4">
                <!-- Hidden Fields -->
                <input type="hidden" id="staff_id" name="staff_id" value="<?php echo $employeeDetails['staff_id']?>">
                <input type="hidden" id="grade" name="grade" value="<?php echo $employeeDetails['GRADE']?>">
                <input type="hidden" id="step" name="step" value="<?php echo $employeeDetails['STEP']?>">
                <input type="hidden" id="salaryType" name="salaryType" value="<?php echo $employeeDetails['SALARY_TYPE']?>">

                <!-- No. of Days in Current Period -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">No. of Days in Current Period:</label>
                    <input type="number" pattern="[0-9]*" id="currentDays" name="currentDays" value="<?php if (!empty($noOfDays)) { echo $noOfDays; } ?>" disabled class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <!-- No of Days to Calculate -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">No of Days to Calculate:</label>
                    <input type="number" pattern="[0-9]*" id="daysToCal" name="daysToCal" value="0" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                <!-- Allowance Table -->
                <div class="px-1 py-1 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Allowance</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $gross = 0;
                        if ($empAllowances) {
                            foreach ($empAllowances as $empAllowance){
                                ?>
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap"><?php echo $empAllowance['allow_id'];?></td>
                                    <td class="px-3 py-2 whitespace-nowrap"><?php echo $empAllowance['ed'];?></td>
                                    <td class="px-3 py-2 whitespace-nowrap"><?php echo number_format($empAllowance['value']);?></td>
                                </tr>
                            <?php }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <!-- Calculated Value -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Calculated Value:</label>
                    <div class="mt-1 p-2 border border-gray-300 rounded-md bg-gray-50">
                        <div id="getProrateValue"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse gap-2">
            <button type="button" id="calculateButton" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">Calculate</button>
            <button type="button" id="closeButton" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm closebutton">Close</button>
        </div>
    </div>
</div>

<!--Update Grade/Step-->
<div id="modal_updategrade" class="closemodal fixed inset-0 flex items-center justify-center z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity"></div>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden transform transition-all sm:w-full sm:max-w-lg max-h-screen flex flex-col">
        <div class="bg-blue-600 px-4 py-3">
            <h3 class="text-lg font-semibold text-white text-center" id="modal-title">UPDATE GRADE/STEP FOR <br><?php echo $employeeDetails['NAME'] ?></h3>
        </div>
        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 overflow-y-auto flex-1">
            <div class="grid grid-cols-1 gap-4">
                <!-- Hidden Fields -->
                <input type="hidden" id="staff_id" name="staff_id" value="<?php echo $employeeDetails['staff_id']?>">
                <input type="hidden" id="grade" name="grade" value="<?php echo $employeeDetails['GRADE']?>">
                <input type="hidden" id="step" name="step" value="<?php echo $employeeDetails['STEP']?>">
                <input type="hidden" id="salarytype" name="salarytype" value="<?php echo $employeeDetails['SALARY_TYPE']?>">


                <!-- No. of Days in Current Period -->
                <div class="space-y-4">
                    <!-- Current Grade -->
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Current Grade:</label>
                        <input type="text" value="<?php echo $employeeDetails['GRADE']?>" readonly class="w-full px-3 py-2 bg-gray-200 text-gray-700 border rounded-md focus:outline-none">
                    </div>
                    <!-- Current Step -->
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Current Step:</label>
                        <input type="text" value="<?php echo $employeeDetails['STEP']?>" readonly class="w-full px-3 py-2 bg-gray-200 text-gray-700 border rounded-md focus:outline-none">
                    </div>
                    <!-- New Grade -->
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">New Grade:</label>
                        <input id="newgrade" name="newgrade"  pattern="[0-9]*" type="number" placeholder="Enter new grade" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600">
                    </div>
                    <!-- New Step -->
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">New Step:</label>
                        <input id="newstep" name="newstep" pattern="[0-9]*" type="number" placeholder="Enter new step" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600">
                    </div>
                </div>
        </div>
        <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse gap-2">
            <button type="button" id="saveupdategrade" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">Save</button>
            <button type="button" id="closeButton" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm closebutton">Close</button>
        </div>
    </div>
</div>
</div>
<!-- Payslip Modal Structure -->
<div id="modal_updategrade" class="closemodal fixed inset-0 flex items-center justify-center z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity"></div>
    <div class="bg-white rounded-lg shadow-lg overflow-hidden transform transition-all sm:w-full sm:max-w-lg max-h-screen flex flex-col">
        <div class="bg-blue-600 px-4 py-3">
            <h3 class="text-lg font-semibold text-white text-center" id="modal-title">UPDATE GRADE/STEP FOR <br><?php echo $employeeDetails['NAME'] ?></h3>
        </div>
        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 overflow-y-auto flex-1">
            <div class="grid grid-cols-1 gap-4">
                <!-- Hidden Fields -->
                <input type="hidden" id="staff_id" name="staff_id" value="<?php echo $employeeDetails['staff_id']?>">
                <input type="hidden" id="grade" name="grade" value="<?php echo $employeeDetails['GRADE']?>">
                <input type="hidden" id="step" name="step" value="<?php echo $employeeDetails['STEP']?>">
                <input type="hidden" id="salarytype" name="salarytype" value="<?php echo $employeeDetails['SALARY_TYPE']?>">


                <!-- No. of Days in Current Period -->
                <div class="space-y-4">
                    <!-- Current Grade -->
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Current Grade:</label>
                        <input type="text" value="<?php echo $employeeDetails['GRADE']?>" readonly class="w-full px-3 py-2 bg-gray-200 text-gray-700 border rounded-md focus:outline-none">
                    </div>
                    <!-- Current Step -->
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Current Step:</label>
                        <input type="text" value="<?php echo $employeeDetails['STEP']?>" readonly class="w-full px-3 py-2 bg-gray-200 text-gray-700 border rounded-md focus:outline-none">
                    </div>
                    <!-- New Grade -->
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">New Grade:</label>
                        <input id="newgrade" name="newgrade"  pattern="[0-9]*" type="number" placeholder="Enter new grade" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600">
                    </div>
                    <!-- New Step -->
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">New Step:</label>
                        <input id="newstep" name="newstep" pattern="[0-9]*" type="number" placeholder="Enter new step" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600">
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse gap-2">
                <button type="button" id="saveupdategrade" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">Save</button>
                <button type="button" id="closeButton" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm closebutton">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Payslip Modal Structure -->
<div id="payslipModal" class="closemodal fixed inset-0 flex items-center justify-center bg-gray-500 bg-opacity-50 hidden">
    <div class="modal-content rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-sm md:max-w-md lg:max-w-lg xl:max-w-xl w-full flex flex-col bg-white">

        <!-- Modal Header -->
        <div class="px-6 py-4">
            <h2 class="text-xl font-bold text-center"><?php echo $_SESSION['businessname'];?></h2>
        </div>

        <!-- Scrollable Content -->
        <div class="scrollable-content px-6 py-4 flex-1 overflow-y-auto">
            <div class="px-6 py-4">
                <h2 class="text-xl font-bold">Payslip for <?php echo $_SESSION['activeperiodDescription']; ?></h2>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-semibold">Name:</span> <span><?php echo $employeePayslip['NAME'] ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-semibold">Staff No.:</span> <span><?php echo $employeePayslip['staff_id'] ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-semibold">Dept:</span> <span><?php echo $employeePayslip['dept'] ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-semibold">Bank:</span> <span><?php echo $employeePayslip['BNAME'] ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-semibold">Acct No.:</span> <span><?php echo $employeePayslip['ACCTNO'] ?></span>
            </div>
            <div class="flex justify-between mb-2">
                <span class="font-semibold">GRADE/STEP:</span> <span><?php echo $employeePayslip['GRADE'] ?>/<?php echo $employeePayslip['STEP'] ?></span>
            </div>
            <div class="mt-4">
                <h3 class="text-lg font-semibold">Allowances</h3>
                <?php $gross = 0;
                        $Totaldeductions = 0;
                        foreach($paySlips as $paySlip) {
                    if($paySlip['allow'] !=0){
                    ?>
                <div class="flex justify-between mb-2">
                    <span><?php echo $paySlip['ed']; ?>:</span> <span><?php echo number_format($paySlip['allow']); ?></span>
                </div>
                <?php $gross = $gross +$paySlip['allow'];
                    }
                    }
                    ?>
                <div class="flex justify-between mb-2 font-bold">
                    <span>Total Allowance:</span> <span><?php echo number_format($gross);?></span>
                </div>
                <h3 class="text-lg font-semibold">Deductions</h3>
                <?php
                foreach($paySlips as $paySlip) {
                    if($paySlip['deduc'] !=0){
                        ?>
                        <div class="flex justify-between mb-2">
                            <span><?php echo $paySlip['ed']; ?>:</span> <span><?php echo number_format($paySlip['deduc']); ?></span>
                        </div>
                        <?php $Totaldeductions = $Totaldeductions +$paySlip['deduc'];
                    }
                }
                ?>
                <div class="flex justify-between mb-2 font-bold border-t-2">
                    <span>Total Deductions:</span> <span><?php echo number_format($Totaldeductions);?></span>
                </div>
                <div class="flex justify-between mb-2 font-bold">
                    <span>NET PAY:</span> <span><?php echo number_format($gross - $totalDeductions)?></span>
                </div>
            </div>
        </div>

        <!-- Fixed Footer with Buttons -->
        <div class="bg-gray-50 px-4 py-3 flex flex-row-reverse gap-2">
            <button id="printButton" class="px-4 py-2 bg-red-600 text-white rounded" onclick="window.print()">Print</button>
            <button id="closeButton" type="button" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm closebutton">Close</button>
        </div>
    </div>
</div>
    <script>
        $(document).ready(function() {

            document.querySelectorAll('input[type=number]').forEach(input => {
                input.addEventListener('input', function (e) {
                    this.value = this.value.replace(/[^0-9]/g, '');
                });
            })

            $('#emailButton').on('click', function() {
                const payslipData = $('#payslipModal .modal-content').html();
                const email = prompt("Please enter the employee's email address:");
                if (email) {
                    $.ajax({
                        url: 'libs/send_payslip.php',
                        type: 'POST',
                        data: { payslip: payslipData,
                            email: email ,
                        staff_id: $('#staff_id').val(),}
                        ,
                        success: function(response) {
                            alert('Payslip emailed successfully.');
                        },
                        error: function(xhr, status, error) {
                            alert('Error sending email: ' + error);
                        }
                    });
                }
            });


            $('#deletepayslip').click(function(event) {
                event.preventDefault();
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

                        $.ajax({
                            url: 'libs/deletePayslip.php',
                            method: 'POST',
                            dataType: 'json',
                            data: {
                                staff_id: $('#staff_id').val(),
                            },
                            success: function(response) {
                                if(response.status === 'success'){
                                    displayAlert(response.message, 'center', 'success');
                                    $('#loadContent', window.parent.document).load('view/view_Empearning.php');
                                }else{
                                    displayAlert(response.message, 'center', 'error');
                                    $('#loadContent', window.parent.document).load('view/view_Empearning.php');
                                }
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error('AJAX Error: ', textStatus, errorThrown);
                            }
                        });
                    }
                })
            })

            $('#saveupdategrade').click(function(event) {
                $.ajax({
                    url: 'libs/updategradestep.php',
                    method: 'POST',
                    data: {
                        newgrade: $('#newgrade').val(),
                        newstep: $('#newstep').val(),
                        staff_id: $('#staff_id').val(),
                        salarytype: $('#salarytype').val(),
                    },
                    success: function(response) {
                        if(response === 'success'){
                            displayAlert('Upgraded successfully', 'center', 'success');
                            $('#loadContent', window.parent.document).load('view/view_Empearning.php');
                        }else{
                            displayAlert(response, 'center', 'error');
                            $('#loadContent', window.parent.document).load('view/view_Empearning.php');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Error: ', textStatus, errorThrown);
                    }
                });
            });

            $('#daysToCal').change(function(event) {
                $.ajax({
                    url: 'libs/getProrate.php',
                    method: 'POST',
                    data: {
                        daysToCal: $('#daysToCal').val(),
                        currentDays: $('#currentDays').val(),
                        staff_id: $('#staff_id').val(),
                        step:$('#step').val(),
                        calculate: 'yes',
                    },
                    success: function(response) {
                        if(response !== 'No Record Found'){
                        $('#getProrateValue').html(response);
                    }else{
                            displayAlert(response, 'center', 'error');
                            $('#getProrateValue').html('');
                        }
                        },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Error: ', textStatus, errorThrown);
                    }
                });
            });

            $('#calculateButton').click(function(event) {
                $.ajax({
                    url: 'libs/getProrate.php',
                    method: 'POST',
                    data: {
                        daysToCal: $('#daysToCal').val(),
                        currentDays: $('#currentDays').val(),
                        staff_id: $('#staff_id').val(),
                        step:$('#step').val(),
                        finalise: 'yes',
                    },
                    success: function(response) {
                        if(response === 'success'){
                            displayAlert('Pro-rate calculated successfully','center','success');
                            $('#loadContent', window.parent.document).load('view/view_Empearning.php');
                        }else{
                            displayAlert(response, 'center', 'error');
                            $('#getProrateValue').html('');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Error: ', textStatus, errorThrown);
                    }
                });
            });


            $('#allowDedSelect').change(function(event) {
                $.ajax({
                    url: 'libs/getSalary.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        grade: $('#grade').val(),
                        step: $('#step').val(),
                        salaryType: $('#salaryType').val(),
                        allow_id:$('#allowDedSelect').val()
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                             if (response.salaryValue > 0) {
                                $('#amount').val(response.salaryValue)
                                $('#amount').prop('disabled', true);
                            } else {
                                $('#amount').prop('disabled', false);
                                 $('#amount').val(response.salaryValue)
                            }
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Error: ', textStatus, errorThrown);
                    }
                });
            });

            // Show the modal-allowance/deduction
            $('#openModalButton').click(function(event) {
                event.preventDefault();
                $('#modal_addAllowDed').removeClass('hidden');
            });

            // Show the modal-update grade
            $('#updategrade').click(function(event) {
                event.preventDefault();
                $('#modal_updategrade').removeClass('hidden');
            });



            $('#viewpayslipModal').click(function(event) {
                event.preventDefault();
                $('#payslipModal').removeClass('hidden');
            });

            // Hide the modal-allowance/deduction buttons
            $('#cancelButton').click(function() {
                $('#modal_addAllowDed').addClass('hidden');
            });

            // Show the modal-prorate
            $('#openModalProrate').click(function(event) {
                event.preventDefault();
                 $('#modal_prorate').removeClass('hidden');
            });


            // Hide the modal-prorate
            $('.closebutton').click(function() {
                $('.closemodal').addClass('hidden');
            });


            $('#runpayslip').click(function(event) {
                event.preventDefault();
                Swal.fire({
                    title: "Are you sure you want to process this employee payslip?",
                    text: "",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, run it!"
                }).then((result) => {
                    if (result.isConfirmed) {

                        $.ajax({
                            url: 'libs/runPayslipOne.php',
                            method: 'POST',
                            dataType: 'json',
                            data: {
                                staff_id: $('#staff_id').val()
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    displayAlert(response.message, 'center', 'success');
                                    $('#loadContent', window.parent.document).load('view/view_Empearning.php');
                                } else {
                                    displayAlert(response.message, 'center', 'error');
                                    $('#loadContent', window.parent.document).load('view/view_Empearning.php');

                                }

                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                console.error('AJAX Error: ', textStatus, errorThrown);
                            }
                        });

                    }
                })
            })

            // Handle deactivate button click
            $('#saveButton').click(function() {
                $.ajax({
                    url: 'libs/insertAllow.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        staff_id: $('#staff_id').val(),
                        value: $('#amount').val(),
                        allow_id:$('#allowDedSelect').val(),
                        counter : $('#runtime').val(),
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            displayAlert(response.message,'center','success');
                            $('#loadContent', window.parent.document).load('view/view_Empearning.php');
                        } else {
                            displayAlert(response.message,'center','error');
                        }

                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Error: ', textStatus, errorThrown);
                    }
                });
            });

        $('#deleteSelected').click(function(event) {
            // Prevent the default form submission
            event.preventDefault();

            if ($('input[type="checkbox"]:checked').length === 0) {
                displayAlert('Please select at least one checkbox','center','error')
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
                            url: 'libs/getEmpearnings.php',
                            type: 'POST',
                            data: formData,
                            success: function (response) {
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Selected Items has been deleted.",
                                    icon: "success"
                                });
                                $('#loadContent', window.parent.document).load('view/view_Empearning.php');

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

