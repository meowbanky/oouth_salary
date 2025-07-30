<?php
//session_start();
/*if (!defined('DIRECTACC')) {
        header('Status: 200');
        header('Location: ../../index.php');
	}*/

include_once('../Connections/paymaster.php');
//$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function retrieveDescSingleFilter($table, $basevar, $filter1, $val1)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
		$res = $query->execute(array($val1));
		if ($row = $query->fetch()) {
			echo ($row['' . $basevar . '']);
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function &returnDescSingleFilter($table, $basevar, $filter1, $val1)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ?');
		$res = $query->execute(array($val1));
		if ($row = $query->fetch()) {
			return $row['' . $basevar . ''];
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrieveCompanyDepartment($table, $basevar, $val1, $filter1)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 .  ' = ?');
		$res = $query->execute(array($val1));
		if ($row = $query->fetch()) {
			echo ($row['' . $basevar . '']);
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrieveDescDualFilter($table, $basevar, $val1, $filter1, $filter2, $val2)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 .  ' = ? AND ' . $filter2 . ' = ?');
		$res = $query->execute(array($val1, $val2));
		if ($row = $query->fetch()) {
			echo ($row['' . $basevar . '']);
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}


function retrieveDescQuadFilter($table, $basevar, $val1, $filter1, $filter2, $val2, $filter3, $val3, $filter4, $val4)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 .  ' = ? AND ' . $filter2 . ' = ? AND ' . $filter3 . ' = ? AND ' . $filter4 . ' = ?');
		$res = $query->execute(array($val1, $val2, $val3, $val4));
		if ($row = $query->fetch()) {
			echo (number_format($row['' . $basevar . '']));
		} else {
			echo '0';
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}


function retrieveDescPentaFilter($table, $basevar, $val1, $filter1, $filter2, $val2, $filter3, $val3, $filter4, $val4, $filter5, $val5)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 .  ' = ? AND ' . $filter2 . ' = ? AND ' . $filter3 . ' = ? AND ' . $filter4 . ' = ? AND ' . $filter5 . ' = ?');
		$res = $query->execute(array($val1, $val2, $val3, $val4, $val5));
		if ($row = $query->fetch()) {
			echo (number_format($row['' . $basevar . '']));
		} else {
			echo '0';
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}


function &returnDescPentaFilter($table, $basevar, $val1, $filter1, $filter2, $val2, $filter3, $val3, $filter4, $val4, $filter5, $val5)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 .  ' = ? AND ' . $filter2 . ' = ? AND ' . $filter3 . ' = ? AND ' . $filter4 . ' = ? AND ' . $filter5 . ' = ?');
		$res = $query->execute(array($val1, $val2, $val3, $val4, $val5));
		if ($row = $query->fetch()) {
			return $row['' . $basevar . ''];
		} else {
			echo '0';
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}


function styleLabelColor($labelType)
{
	global $conn;

	try {
		if ($labelType == 'Earning') {
			return "success";
		} elseif ($labelType == 'Deduction') {
			return "danger";
		} elseif ($labelType == 'Union Deduction') {
			return "warning";
		} elseif ($labelType == 'Loan') {
			return "info";
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}


function retrieveSelect($table, $filter1, $filter2, $basevar, $sortvar)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ? AND status = ? ORDER BY ' . $sortvar . '');
		$res = $query->execute(array($basevar, 'Active'));
		$out = $query->fetchAll(PDO::FETCH_ASSOC);

		while ($row = array_shift($out)) {
			echo ('<option value="' . $row['ed_id'] . '">' . $row['edDesc'] . ' - ' . $row['ed_id'] . '</option>');
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrieveSelectwithoutFilter($table, $filter1, $filter2, $basevar, $sortvar)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ? AND status = ? ORDER BY ' . $sortvar . '');
		$res = $query->execute(array($basevar, 'Active'));
		$out = $query->fetchAll(PDO::FETCH_ASSOC);

		while ($row = array_shift($out)) {
			echo ('<option value="' . $row['ed_id'] . '">' . $row['edDesc'] . ' - ' . $row['ed_id'] . '</option>');
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrieveSelectwithoutWhere($table, $filter1,  $sortvar, $value1, $value2)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' ORDER BY ' . $sortvar . '');
		$res = $query->execute(array());
		$out = $query->fetchAll(PDO::FETCH_ASSOC);

		while ($row = array_shift($out)) {
			echo ('<option value="' . $row[$value1] . '">' . $row[$value2] . ' - ' . $row[$value1] . '</option>');
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrievePayrollSubTotal($basevar, $table, $filter1, $filter2, $filter3, $filter4, $var1, $var2)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $basevar . ' FROM ' . $table . ' WHERE ' . $filter1 . ' = ? AND ' . $filter2 . ' = ? AND ' . $filter3 . ' = ? AND ' . $filter4 . ' = ?');
		$ans = $query->execute(array($var1, $var2, $_SESSION['currentactiveperiod'], '1'));

		if ($row = $query->fetch()) {
			echo number_format($row['' . $basevar . '']);
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrieveEmployees($table, $filter1, $filter2, $basevar, $sortvar)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ? order by Name');
		$res = $query->execute(array($basevar));
		$out = $query->fetchAll(PDO::FETCH_ASSOC);

		while ($row = array_shift($out)) {
			echo ('<option value="' . $row['staff_id'] . '">' . $row['NAME'] . ' - ' . $row['staff_id']  . '</option>');
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrieveLeaveStatus($table, $filter1, $filter2, $basevar)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ?');
		$res = $query->execute(array($basevar));
		$out = $query->fetchAll(PDO::FETCH_ASSOC);

		while ($row = array_shift($out)) {
			echo ('<option value="' . $row['id'] . '">' . $row['statusDescription'] . '</option>');
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrieveLeaveTypes($table, $filter1, $filter2, $basevar)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT ' . $filter1 . ' FROM ' . $table . ' WHERE ' . $filter2 . ' = ?');
		$res = $query->execute(array($basevar));
		$out = $query->fetchAll(PDO::FETCH_ASSOC);

		while ($row = array_shift($out)) {
			echo ('<option value="' . $row['id'] . '">' . $row['Leave_type'] . ' Leave </option>');
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function returnNumberOfEmployees()
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT empNumber FROM employees WHERE companyId = ? AND active =? ORDER BY id ASC');
		$query->execute(array($_SESSION['companyid'], '1'));
		$ftres = $query->fetchAll(PDO::FETCH_COLUMN);
		$count = $query->rowCount();
		echo $count;
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrievePayroll($val1, $val2, $val3, $val4)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT tbl_master.period,
															CASE ANY_VALUE(tbl_master.type) 
															WHEN 1 THEN sum(tbl_master.allow)
															WHEN 2 THEN sum(tbl_master.deduc)
															END as amount
															FROM
															tbl_master
															INNER JOIN employee ON employee.staff_id = tbl_master.staff_id
															right JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id
															INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
															INNER JOIN payperiods ON payperiods.periodId = tbl_master.period
															WHERE tbl_master.period BETWEEN ? and ? and employee.staff_id = ? and allow_id = ?
															GROUP BY employee.staff_id');
		$res = $query->execute(array($val1, $val2, $val3, $val4));
		if ($row = $query->fetch()) {
			return $row['amount'];
		} else {
			return '0';
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function variance($val1, $val2)
{
    global $conn;

    try {
        $query = $conn->prepare('SELECT tbl_master.period,
       SUM(tbl_master.allow) as amount
FROM tbl_master
INNER JOIN employee ON employee.staff_id = tbl_master.staff_id
RIGHT JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id
INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
INNER JOIN payperiods ON payperiods.periodId = tbl_master.period
WHERE tbl_master.period = ? 
AND employee.staff_id = ?
GROUP BY employee.staff_id');
        $res = $query->execute(array($val1, $val2));
        if ($row = $query->fetch()) {
            return $row['amount'];
        } else {
            return '0';
        }
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}
function retrievegross($val1, $val2)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT tbl_master.period,
															CASE ANY_VALUE(type) 
															WHEN 1 THEN sum(tbl_master.allow)
															WHEN 2 THEN sum(tbl_master.deduc)
															END as amount
															FROM
															tbl_master
															INNER JOIN employee ON employee.staff_id = tbl_master.staff_id
															right JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id
															INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
															INNER JOIN payperiods ON payperiods.periodId = tbl_master.period
															WHERE tbl_master.period = ? and employee.staff_id = ? 
															GROUP BY employee.staff_id');
		$res = $query->execute(array($val1, $val2));
		if ($row = $query->fetch()) {
			return $row['amount'];
		} else {
			return '0';
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function exportTax($val1, $val2, $val3)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT tbl_master.period,
															CASE type 
															WHEN 1 THEN sum(tbl_master.allow)
															WHEN 2 THEN sum(tbl_master.deduc)
															END as amount,employee.GRADE
															FROM
															tbl_master
															INNER JOIN employee ON employee.staff_id = tbl_master.staff_id
															right JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id
															INNER JOIN tbl_dept ON tbl_dept.dept_id = employee.DEPTCD
															INNER JOIN payperiods ON payperiods.periodId = tbl_master.period
															WHERE tbl_master.period = ? and employee.staff_id = ? and allow_id = ?
															GROUP BY employee.staff_id');
		$res = $query->execute(array($val1, $val2, $val3));
		if ($row = $query->fetch()) {
			return $row['amount'];
		} else {
			return '0';
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrievePayrollRunStatus($val1, $val2)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT master_staff.staff_id, master_staff.period FROM master_staff WHERE staff_id = ? and period = ?');
		$res = $query->execute(array($val1, $val2));
		if ($row = $query->fetch()) {
			return 1;
		} else {
			return 0;
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrieveLoanStatus($val1, $val2)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT sum(tbl_debt.principal)+ sum(tbl_debt.interest) as loan FROM tbl_debt WHERE staff_id = ? and allow_id = ? GROUP BY staff_id, allow_id');
		$res = $query->execute(array($val1, $val2));
		if ($row = $query->fetch()) {
			return $row['loan'];
		} else {
			return 0;
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}

function retrieveLoanBalanceStatus($val1, $val2, $val3)
{
	global $conn;

	try {
		$query = $conn->prepare('SELECT sum(tbl_repayment.`value`) as repayment FROM tbl_repayment WHERE staff_id = ? and allow_id = ? and period <= ? GROUP BY staff_id,allow_id');
		$res = $query->execute(array($val1, $val2, $val3));
		if ($row = $query->fetch()) {
			return $row['repayment'];
		} else {
			return 0;
		}
	} catch (PDOException $e) {
		echo $e->getMessage();
	}
}