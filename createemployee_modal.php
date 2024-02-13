<?php

ini_set('max_execution_time', '300');
require_once('Connections/paymaster.php');
include_once('classes/model.php');
session_start();

?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="js/all.js"></script>


<div class="modal-header modal-title" style="background: #6e7dc7;">
    <button type=" button" class="close" data-dismiss="modal" aria-hidden="true"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title">create new employee </h4>
</div>
<div class="modal-body">
    <form method="post" action="classes/controller.php?act=addNewEmp" class="horizontal-form" id="employee_form">
        <div class="row">
            <div class="col-md-12">

                <!-- BEGIN New Employee Popup-->
                <?php
                mysqli_select_db($salary, $database_salary);
                $query_bank = 'SELECT tbl_bank.BNAME, tbl_bank.BCODE FROM tbl_bank';
                $bank = mysqli_query($salary, $query_bank) or die(mysqli_error($salary));
                $row_bank = mysqli_fetch_assoc($bank);

                mysqli_select_db($salary, $database_salary);
                $query_dept = 'SELECT tbl_dept.dept_id, tbl_dept.dept FROM tbl_dept';
                $dept = mysqli_query($salary, $query_dept) or die(mysqli_error($salary));
                $row_dept = mysqli_fetch_assoc($dept);

                mysqli_select_db($salary, $database_salary);
                $query_pfa = 'SELECT tbl_pfa.PFACODE, tbl_pfa.PFANAME FROM tbl_pfa';
                $pfa = mysqli_query($salary, $query_pfa) or die(mysqli_error($salary));
                $row_pfa = mysqli_fetch_assoc($pfa);
                ?>
                <div class="form-body">

                    <h4 class="form-section"><b>Personal Details</b></h4>

                    <div class="row">
                        <div class="co-md">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" autocomplete="off" name="namee" class="form-control" required placeholder="Name">
                            </div>
                        </div>

                    </div>





                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Bank</label>
                                <select name="bank" class="form-inps" id="bank" required>
                                    <option value=''>Select Bank</option>
                                    <?php while ($row = mysqli_fetch_array($bank)) {
                                        echo "<option value='" . $row['BCODE'] . "' ";

                                        echo ">" . $row['BNAME'] . "</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Account No.</label>
                                <input type="number" name="acct_no" required class="form-control" placeholder="Account No" pattern="\d{10}" maxlength="10">
                            </div>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Pension FA</label>
                                <select name="pfa" class="form-inps" id="pfa" required>
                                    <option>Select PFA</option>
                                    <?php while ($row = mysqli_fetch_array($pfa)) {
                                        echo "<option value='" . $row['PFACODE'] . "' ";

                                        echo ">" . $row['PFANAME'] . "</option>";
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>RSA PIN.</label>
                                <input type="text" name="rsa_pin" required class="form-control" placeholder="PFA PIN">
                            </div>

                        </div>
                    </div>



                    <h4 class="form-section"><b>Employment Details</b></h4>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Employee No</label>
                                <?php
                                $payp = $conn->prepare('SELECT Max(employee.staff_id) as "nextNo" FROM employee');
                                $myperiod = $payp->execute();
                                $final = $payp->fetch();
                                ?>
                                <input type="text" readonly name="emp_no" class="form-control" required placeholder="Staff No" value="<?php echo intval($final['nextNo']) + 1 ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date of Employment</label>
                                <div class="input-group date" data-provide="datepicker">
                                    <input type="date" required name="doe" class="form-control">
                                    <div class="input-group-addon">
                                        <span class="glyphicon glyphicon-th"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>



                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Department</label>
                                <select name="dept" class="form-control">
                                    <option value="">- - - Select Department - - -</option>
                                    <?php

                                    $query = $conn->prepare('SELECT * FROM tbl_dept');
                                    $res = $query->execute();
                                    $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                    while ($row = array_shift($out)) {
                                        echo ('<option value="' . $row['dept_id'] . '">' .  $row['dept'] . '</option>');
                                    }

                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Designation</label>
                                <input type="text" name="designation" required class="form-control" placeholder="Post">
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group"><br>
                                <label for="callType" class="required  control-label ">Call Duty Type:</label>
                                <label><br>
                                    <input name="callType" type="radio" class="radio-inline" id="payType_01" value="0" checked>
                                    None</label><br>
                                <label>
                                    <input name="callType" type="radio" class="radio-inline" id="payType_0" value="1">
                                    Doctors</label><br>
                                <label>
                                    <input type="radio" name="callType" value="2" id="payType_1" class="radio-inline">
                                    Others</label><br>
                                <label>
                                    <input type="radio" name="callType" value="3" id="payType_2" class="radio-inline">
                                    Nurse</label>
                            </div>
                        </div>
                        <div class="col-md-6">

                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="grade" class="required  control-label ">Grade:</label>
                                <input type="text" name="grade" value="" class="form-inps focus" id="grade" required maxlength="3">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="grade" class="required  control-label ">Step:</label>
                                <input type="text" name="gradestep" value="" class="form-inps focus" id="gradestep" required maxlength="2">
                            </div>
                        </div>

                    </div>


                </div>
                <!-- END New Employee Popup-->


            </div>
        </div>
</div>
<div class="modal-footer">
    <button type="button" data-dismiss="modal" class="btn btn-outline dark">Cancel</button>
    <button type="submit" id="addemp" name=" addemp" class="btn red">Add Employee</button>
</div>

</form>

<script>
    $('#addemp').click(function(e) {
        alert('ok');
        // e.preventDefault();


        $('#employee_form').validate({


            // Specify the validation rules
            rules: {

                namee: "required",
                dept: "required",
                acct_no: {
                    required: {
                        depends: function(element) {
                            if (($("#bank option:selected").text() != 'CHEQUE/CASH') || $("#bank option:selected").text() != 'CHEQUE/CASH') {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    },
                    //"required": false,
                    minlength: 10,
                    maxlength: 10,
                    number: true
                },

                rsa_pin: {
                    required: {
                        depends: function(element) {
                            if ($("#pfa option:selected").text() != 'OTHERS') {
                                return true;
                            } else {
                                return false;
                            }
                        }
                    },
                    number: true
                }


            },

            // Specify the validation error messages
            messages: {
                namee: "The name is a required field.",


            },

            errorClass: "text-danger",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).parents('.form-group').removeClass('has-success').addClass('has-error');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).parents('.form-group').removeClass('has-error').addClass('has-success');
            },

            submitHandler: function(form) {

                form.submit();
                //doEmployeeSubmit(form);
            }
        });
    });
</script>