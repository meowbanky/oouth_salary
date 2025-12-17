<?php ini_set('max_execution_time', '300');
require_once('Connections/paymaster.php');
include_once('classes/model.php'); ?>
<?php

//Start session
session_start();

//Check whether the session variable SESS_MEMBER_ID is present or not
if (!isset($_SESSION['SESS_MEMBER_ID']) || (trim($_SESSION['SESS_MEMBER_ID']) == '') || $_SESSION['role'] != 'Admin') {
    header("location: index.php");
    exit();
}
?>

<div class="modal-body">
    <form method="post" action="classes/controller.php?act=updateEmp" class="horizontal-form">
        <div class="row">
            <div class="col-md-12">
                <div class="form-body">


                    <h4 class="form-section"><b>Personal Details</b></h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Employee No:</label>
                                <input type="text" name="emp_no" value="<?php echo $empd['staff_id'] ?>" class="form-inps focus" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Employee Name:</label>
                                <input name="namee" type="text" class="form-control" value="<?php echo $empd['NAME']; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="dept" class="required  control-label ">Department:</label>
                                <select name="dept" class="form-inps" required>
                                    <option>Select Department</option>
                                    <?php $query = $conn->prepare('SELECT * FROM tbl_dept');
                                    $res = $query->execute();
                                    $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                    while ($row = array_shift($out)) {
                                        echo ('<option value="' . $row['dept_id'] . '"');
                                        if ($row['dept_id'] == $empd['DEPTCD']) {
                                            echo 'SELECTED';
                                        }
                                        echo ('>' .  $row['dept'] . '</option>');
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group" class="required  control-label "><label for="designation">Designation:</label>
                                <input name="post" type="text" class="form-inps" value="<?php echo $empd['POST'] ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-">
                                <div class="form-group"><br>
                                    <label for="payType" class="required  control-label ">Call Duty Type:</label>
                                    <label><br>
                                        <input name="callType" type="radio" class="radio-inline" value="0" checked>
                                        None</label><br>

                                    <label>
                                        <input name="callType" type="radio" class="radio-inline" value="1" <?php if ($empd['CALLTYPE'] == 1) {
                                                                                                                echo 'checked';
                                                                                                            } ?>>
                                        Doctors</label><br>

                                    <label>
                                        <input type="radio" name="callType" value="2" class="radio-inline" <?php if ($empd['CALLTYPE'] == 2) {
                                                                                                                echo 'checked';
                                                                                                            } ?>>
                                        Others</label><br>

                                    <label>
                                        <input type="radio" name="callType" value="3" class="radio-inline" <?php if ($empd['CALLTYPE'] == 3) {
                                                                                                                echo 'checked';
                                                                                                            } ?>>
                                        Nurse</label>

                                </div>

                            </div>
                            <div class="col-md-">
                                <div class="form-group">


                                </div>

                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="grade" class="required  control-label ">Grade:</label>
                                    <input type="text" name="grade" value="<?php echo $empd['GRADE']; ?>" class="form-inps focus" required maxlength="3">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="grade" class="required  control-label ">Step:</label>
                                    <input type="text" name="gradestep" value="<?PHP echo $empd['STEP'] ?>" class="form-inps focus" required maxlength="2">
                                </div>
                            </div>

                        </div>



                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="doe" class="required  control-label ">Date of Employment:</label>
                                    <input name="doe" type="date" required="required" value="<?php echo $empd['EMPDATE']; ?>" class="form-inps" max="<?php echo $today; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" <label for="dob" class="required  control-label ">Date of Birth:</label>
                                    <input name="dob" type="date" class="form-inps" max="<?php echo $today; ?>">
                                </div>
                            </div>
                        </div>



                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">

                                    <label for="bank" class="required  control-label ">Bank:</label>
                                    <select name="bank" class="form-inps">
                                        <option>Select Bank</option>
                                        <?php $query = $conn->prepare('SELECT * FROM tbl_bank');
                                        $res = $query->execute();
                                        $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                        while ($row = array_shift($out)) {
                                            echo ('<option value="' . $row['BCODE'] . '"');
                                            if ($row['BCODE'] == $empd['BCODE']) {
                                                echo 'SELECTED';
                                            }
                                            echo ('>' .  $row['BNAME'] . '</option>');
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="acct_no" class="required  control-label ">Account No:</label>
                                    <input name="acct_no" type="text" class="form-inps" autocomplete="off" value="<?PHP echo $empd['ACCTNO'] ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">

                                    <label for="pfa" class="required  control-label ">PFA:</label>
                                    <select name="pfa" class="form-inps">
                                        <option value="">Select PFA</option>
                                        <?php $query = $conn->prepare('SELECT * FROM tbl_pfa');
                                        $res = $query->execute();
                                        $out = $query->fetchAll(PDO::FETCH_ASSOC);

                                        while ($row = array_shift($out)) {
                                            echo ('<option value="' . $row['PFACODE'] . '"');
                                            if ($row['PFACODE'] == $empd['PFACODE']) {
                                                echo 'SELECTED';
                                            }
                                            echo ('>' .  $row['PFANAME'] . '</option>');
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rsa_pin" class="required control-label ">PFA PIN:</label>
                                    <input name="rsa_pin" type="text" class="form-inps" autocomplete="off" value="<?PHP echo $empd['PFAACCTNO'] ?>">
                                </div>
                            </div>
                        </div>






                    </div>
                </div>
            </div>

        </div>
        <div class="modal-footer">
            <button type="submit" class="btn red">Save Details</button>
            <button type="button" data-dismiss="modal" class="btn btn-primary dark">Close</button>
        </div>
    </form>
</div>