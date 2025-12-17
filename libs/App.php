<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$file =  __DIR__ . '/../config/config.php';

require_once $file;


class App
{
    public $host = HOST;
    public $dbname = DBNAME;
    public $pass = PASS;
    public $user = USER;

    public $link;

    public $businessName;

    public $town;
    public $state;
    public $tel;

    public $logged_in ;
    public $loggeduser;
    public $SESS_MEMBER_ID;
    public $email ;
    public $SESS_FIRST_NAME;
    public $SESS_LAST_NAME;

    public $role;
    public $emptrack;
    public $empDataTrack;

    public function __construct()
    {
        $this->construct();

    }

    function sendPasswordEmail($to, $username, $password) {
        $subject = "Your New Account Details";
        $message = "Hello,\n\nYour account has been created. Here are your login details:\nUsername: $username\nPassword: $password\n\nPlease change your password after your first login.";
        $headers = "From: no-reply@example.com";

        mail($to, $subject, $message, $headers);
    }

    function getPeriodToRun(){
        $query = 'SELECT periodId FROM payperiods WHERE completed = 1 ORDER BY periodId DESC LIMIT 1';
        return $this->selectOne($query,[]);
    }

    public function insertProrateAllowance($staff_id, $allow_id, $value, $type, $inserted_by, $date_insert) {
        $query = 'INSERT INTO prorate_allow_deduc (staff_id, allow_id, value, type, inserted_by, date_insert, counter) 
              VALUES (:staff_id, :allow_id, :value, :type, :inserted_by, :date_insert, 1)';
        $array = [':staff_id' => $staff_id, ':allow_id' => $allow_id,
            ':value' => $value, 'type'=>$type, 'inserted_by'=>$inserted_by, 'date_insert' => $date_insert];

        $this->log('INSERT PRORATE','prorate_allow_deduc',$array,$_SESSION['SESS_MEMBER_ID']);

        return $this->executeNonSelect($query,$array);

    }

    public function deleteProrateAllowance($staff_id, $allow_id) {
        $query = 'DELETE FROM prorate_allow_deduc WHERE staff_id = :staff_id AND allow_id = :allow_id';
        $params = [':staff_id' => $staff_id, ':allow_id' => $allow_id];
        $this->executeNonSelect($query, $params);
    }

    public function updateRunningCounter($running_counter,$staff_id,$allow_id){
        $query = 'update allow_deduc set running_counter = :running_counter
                   WHERE staff_id = :staff_id AND allow_id = :allow_id';
        $array = [
            ':running_counter' => $running_counter,
            ':staff_id' => $staff_id,
            ':allow_id' => $allow_id
        ];
        return $this->executeNonSelect($query, $array);
    }

    public function deleteMasterStaff($period){
        $query = 'DELETE FROM master_staff WHERE period = :period';
        $array = [
            ':period' => $period
        ];
        return $this->executeNonSelect($query, $array);
    }

    public function deleteMaster($period){
        $query = 'DELETE FROM tbl_master WHERE period = :period';
        $array = [
            ':period' => $period
        ];
        return $this->executeNonSelect($query, $array);
    }
    public function checkCompleted($staff_id,$period,$user_id){
        $query = "SELECT * FROM completedloan WHERE staff_id = :staff_id AND period = :period";
        $array = [
           ':staff_id'=> $staff_id,
           ':period'=> $period
        ];
        $selects = $this->selectAll($query,$array);
        if($selects){
            foreach ($selects as $select){
                //restore selection
                $this->insertAllowances($select['value'],$user_id,$staff_id,$select['allow_id'],$counter=1,$running_counter=0);
            }
            $query = "DELETE FROM completedloan where period = :period AND staff_id = :staff_id";
            $this->executeNonSelect($query,$array);
        }

    }

    function deleteAllowance($staff_id, $allow_id) {
        $query = 'DELETE FROM allow_deduc WHERE staff_id = :staff_id AND allow_id = :allow_id';
        $array = [
            ':allow_id'=> $allow_id,
            ':staff_id'=> $staff_id
        ];
        return $this->executeNonSelect($query,$array);
    }

    function deleteProrate($staff_id) {
        $query = 'DELETE FROM prorate_allow_deduc WHERE staff_id = :staff_id';
        $array = [
            ':staff_id'=> $staff_id
        ];
        return $this->executeNonSelect($query,$array);
    }

    function insertProratedAllow($staff_id, $allow_id,$value) {
        $query = 'INSERT INTO allow_deduc (staff_id,allow_id,value) VALUES (:staff_id,:allow_id,:value)';
        $array = [
            ':allow_id'=> $allow_id,
            ':staff_id'=> $staff_id,
            ':value'=> $value
        ];

        $this->log('INSERT','allow_deduc',$array,$_SESSION['SESS_MEMBER_ID']);

        return $this->executeNonSelect($query,$array);

    }
    public function getPeriodDescription($period){
        $sql = "SELECT concat(payperiods.description,'-', payperiods.periodYear) as period FROM payperiods 
                WHERE periodid = :periodid";
        $param = [':periodid' => $period];
        return $this->selectOne($sql,$param);
    }

    public function getBankSummary($period,$bankid=-1){

        $sql = "SELECT
    ANY_VALUE(master_staff.STATUSCD) AS STATUSCD,
	ANY_VALUE(tbl_master.staff_id) AS staff_id, 
	sum(tbl_master.allow) AS allow, 
	(sum( tbl_master.allow ) - sum( tbl_master.deduc ) ) AS net, 
	sum(tbl_master.deduc) AS deduc, 
	any_value ( master_staff.`NAME` ) AS `NAME`, 
	ANY_value ( tbl_dept.dept ) AS dept, 
	any_value ( master_staff.ACCTNO ) AS acctno, 
	any_value ( master_staff.GRADE ) AS grade, 
	any_value ( master_staff.STEP ) AS step, 
	any_value ( master_staff.OGNO ) AS OGNO, 
	any_value ( tbl_bank.BNAME ) AS bankname, 
	any_value ( tbl_bank.BCODE ) AS bankcode, 
	COUNT(DISTINCT (tbl_master.staff_id)) AS staff_count, 
	ANY_VALUE(tbl_salaryType.SalaryType) AS SalaryType
FROM
	tbl_master
	LEFT JOIN
	master_staff
	ON 
		tbl_master.staff_id = master_staff.staff_id
	LEFT JOIN
	tbl_dept
	ON 
		master_staff.DEPTCD = tbl_dept.dept_id
	LEFT JOIN
	tbl_bank
	ON 
		master_staff.BCODE = tbl_bank.bank_ID
	LEFT JOIN
	tbl_salaryType
	ON 
		master_staff.SALARY_TYPE = tbl_salaryType.salaryType_id";
        if($bankid == -1){
            $sql.=" where tbl_master.period  = :period AND master_staff.period = :period2 GROUP BY master_staff.staff_id,tbl_bank.BCODE ORDER BY tbl_bank.BCODE";
            $param = [':period' => $period,
                ':period2' => $period];
        }else{
            $sql.=" where tbl_master.period  = :period AND master_staff.period = :period2 AND master_staff.BCODE = :bank_ID
             GROUP BY master_staff.staff_id";
            $param = [':period' => $period,
                ':bank_ID' => $bankid,
                ':period2' => $period];
        }
        return $this->selectAll($sql,$param);
    }
    public function getPfa($period,$pfacode=-1){

        $sql = "SELECT
	sum(tbl_master.deduc) as deduc, 
	ANY_VALUE(tbl_earning_deduction.ed) AS ed, 
	ANY_VALUE(tbl_master.staff_id) as staff_id, 
	ANY_VALUE(master_staff.PFAACCTNO) AS PFAACCTNO, 
	ANY_VALUE(master_staff.OGNO) AS OGNO, 
	ANY_VALUE(tbl_pfa.PFANAME) AS PFANAME, 
	ANY_VALUE(master_staff.`NAME`) AS NAME
FROM
	tbl_master
	INNER JOIN
	tbl_earning_deduction
	ON 
		tbl_master.allow_id = tbl_earning_deduction.ed_id
	INNER JOIN
	master_staff
	ON 
		tbl_master.staff_id = master_staff.staff_id
	LEFT JOIN
	tbl_pfa
	ON 
		master_staff.PFACODE = tbl_pfa.PFACODE";
        if($pfacode == -1){
            $sql.=" where tbl_master.period  = :period AND master_staff.period = :period2 AND tbl_master.allow_id= 25 GROUP BY master_staff.PFACODE ORDER BY tbl_pfa.PFACODE";
            $param = [':period' => $period,
                ':period2' => $period];
        }else{
            $sql.=" where tbl_master.period  = :period AND master_staff.period = :period2 AND tbl_master.allow_id= 25 AND master_staff.PFACODE = :pfacode
             GROUP BY master_staff.staff_id";
            $param = [':period' => $period,
                ':period2' => $period,
                ':pfacode' => $pfacode];
        }
        return $this->selectAll($sql,$param);
    }
    public function getBankSummaryGroupBy($period,$grouby='master_staff.BCODE',$deptcd = null){
        $param = [':period' => $period,
            ':period2' => $period];

        $sql = "SELECT
	ANY_VALUE(tbl_master.staff_id) AS staff_id, 
	sum(tbl_master.allow) AS allow, 
	(sum( tbl_master.allow ) - sum( tbl_master.deduc ) ) AS net, 
	sum(tbl_master.deduc) AS deduc, 
	any_value ( master_staff.`NAME` ) AS `NAME`, 
	ANY_value ( tbl_dept.dept ) AS dept, 
	any_value ( master_staff.ACCTNO ) AS acctno, 
	any_value ( master_staff.GRADE ) AS grade, 
	any_value ( master_staff.STEP ) AS step, 
	any_value ( tbl_bank.BNAME ) AS BNAME, 
	any_value ( tbl_bank.BCODE ) AS bankcode, 
	COUNT(DISTINCT (tbl_master.staff_id)) AS staff_count, 
	ANY_VALUE(tbl_salaryType.SalaryType) AS SalaryType
FROM
	tbl_master
	LEFT JOIN
	master_staff
	ON 
		tbl_master.staff_id = master_staff.staff_id
	LEFT JOIN
	tbl_dept
	ON 
		master_staff.DEPTCD = tbl_dept.dept_id
	INNER JOIN
	tbl_bank
	ON 
		master_staff.BCODE = tbl_bank.bank_ID
	LEFT JOIN
	tbl_salaryType
	ON 
		master_staff.SALARY_TYPE = tbl_salaryType.salaryType_id
    WHERE tbl_master.period  = :period AND master_staff.period = :period2";
        if($deptcd != NULL){
        $sql .= " AND master_staff.DEPTCD  = :DEPTCD ";
            $sql .=" GROUP BY master_staff.staff_id";
            $additionalParam = [':DEPTCD' => $deptcd];
            $param = array_merge($param, $additionalParam);
        }else{
            $sql .=" GROUP BY {$grouby}";
        }


        return $this->selectAll($sql,$param);
    }

    public function getGrossPay($period){
        $sql = "SELECT tbl_bank.BNAME, COUNT(DISTINCT tbl_master.staff_id) AS staff_count, (SUM(tbl_master.allow) - SUM(tbl_master.deduc)) AS net
                FROM tbl_master INNER JOIN master_staff ON tbl_master.staff_id = master_staff.staff_id INNER JOIN tbl_bank 
                    ON master_staff.BCODE = tbl_bank.bank_ID WHERE tbl_master.period = :period GROUP BY master_staff.BCODE";
        $param = [':period' => $period];
        return $this->selectAll($sql,$param);
    }

    public  function getallowType($allow_id) {
        $sql = "SELECT edType FROM tbl_earning_deduction WHERE ed_id = :ed_id";
        $param = [':ed_id' => $allow_id];
        return $this->selectOne($sql,$param);
    }

    public  function getStaffNet($staff_id) {
        $sql = "SELECT
            (SELECT ifnull(SUM(`value`),0)
             FROM allow_deduc 
             INNER JOIN tbl_earning_deduction ON allow_deduc.allow_id = tbl_earning_deduction.ed_id
             WHERE allow_deduc.staff_id = :staff_id1 AND tbl_earning_deduction.type = 1) -
        
            (SELECT ifnull(SUM(`value`),0)
             FROM allow_deduc 
             INNER JOIN tbl_earning_deduction ON allow_deduc.allow_id = tbl_earning_deduction.ed_id
             WHERE allow_deduc.staff_id = :staff_id2 AND tbl_earning_deduction.type = 2)  as net";
   $param = [':staff_id1' => $staff_id,
            ':staff_id2' =>$staff_id];
        return $this->selectOne($sql,$param);
    }

    public function getAutoAllowance(){
        $sql = "SELECT * FROM tbl_autoallowance";
        return $this->selectAll($sql,[]);
    }

    public function getSalaryTable($salarytype,$allowcode,$grade){
        $sql = "SELECT
	allowancetable.allowcode, allow_id,
	allowancetable.grade, 
	allowancetable.step, 
	allowancetable.`value`, 
	allowancetable.SALARY_TYPE, 
	tbl_earning_deduction.ed
FROM
	allowancetable
	INNER JOIN
	tbl_earning_deduction
	ON 
		allowancetable.allowcode = tbl_earning_deduction.ed_id
            WHERE SALARY_TYPE = :SALARY_TYPE AND allowcode = :allowcode AND grade = :grade";
        $param = [':SALARY_TYPE' => $salarytype,
        ':allowcode'=>$allowcode,
            ':grade'=>$grade];
        return $this->selectAll($sql,$param);
    }
    public function getReportSummary($period, $columnName)
    {
        // Define allowed column names to prevent SQL injection
        $allowedColumns = ['allow', 'deduc'];

        // Check if the provided column name is allowed
        if (!in_array($columnName, $allowedColumns)) {
            throw new InvalidArgumentException('Invalid column name provided.');
        }

        // Initialize the query and parameters
        $query = "SELECT SUM($columnName) AS value, tbl_earning_deduction.edDesc
              FROM tbl_master
              INNER JOIN tbl_earning_deduction ON tbl_master.allow_id = tbl_earning_deduction.ed_id
              WHERE $columnName <> 0 AND period = :period
              GROUP BY tbl_master.allow_id";
        $params = [':period' => $period];

        return $this->selectAll($query, $params);
    }

    public function getReportDeductionList($period, $type,$allow_id)
    {
        // Define allowed column names to prevent SQL injection
        if($type == 1){
            $columnName = 'allow';
        }else{
            $columnName = 'deduc';
        }

        // Initialize the query and parameters
        $query = "SELECT $columnName AS value,employee.OGNO, tbl_master.staff_id,NAME,tbl_earning_deduction.edDesc
              FROM tbl_master
              INNER JOIN tbl_earning_deduction ON tbl_master.allow_id = tbl_earning_deduction.ed_id
              INNER JOIN employee ON tbl_master.staff_id = employee.staff_id
              WHERE $columnName <> 0 AND period = :period AND allow_id = :allow_id";
        $params = [':period' => $period,
                    ':allow_id' => $allow_id];

        return $this->selectAll($query, $params);
    }

    public function getEmployeeDetails($staff_id = null)
    {
        // Initialize the query and parameters
        $query = 'SELECT
    ifnull(employee.SALARY_TYPE,0) AS SALARY_TYPE,
	employee.staff_id, 
	`NAME`, 
	DEPTCD, 
	employee.EMAIL, 
	employee.OGNO, 
	employee.TIN, 
	employee.GENDER, 
	employee.EMPDATE, 
	employee.DOB, 
	employee.DEPTCD, 
	employee.POST, 
	employee.GRADE, 
	employee.STEP, 
	employee.PFACODE, 
	employee.PFAACCTNO, 
	employee.SALARY_TYPE, 
	employee.ACCTNO, 
	employee.BANK_ID, 
	dept, 
	employee.STATUSCD, 
	staff_status.`STATUS`, 
	tbl_salaryType.SalaryType, 
	tbl_bank.BNAME, 
	tbl_pfa.PFANAME,tbl_employmenttype.id AS employment_typeid,tbl_employmenttype.employment_type
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
	LEFT JOIN
	tbl_bank
	ON 
		employee.BANK_ID = tbl_bank.bank_ID
	LEFT JOIN
	tbl_pfa
	ON 
		employee.PFACODE = tbl_pfa.PFACODE
	LEFT JOIN tbl_employmenttype 
	ON
		employee.employment_type = tbl_employmenttype.id';

        $params = [];

        if ($staff_id !== null) {
            $query .= ' WHERE staff_id = :staff_id';
            $params = [':staff_id' => $staff_id];
            return $this->selectOne($query, $params);
        } else {
            return $this->selectAll($query, $params);
        }
    }

    public function getActiveEmployees()
    {
        // Initialize the query and parameters
        $query = 'SELECT
	employee.staff_id, 
	`NAME`, 
	DEPTCD, 
	employee.EMAIL, 
	employee.OGNO, 
	employee.TIN, 
	employee.`NAME`, 
	employee.GENDER, 
	employee.EMPDATE, 
	employee.DOB, 
	employee.DEPTCD, 
	employee.POST, 
	employee.GRADE, 
	employee.STEP, 
	employee.PFACODE, 
	employee.PFAACCTNO, 
	employee.SALARY_TYPE, 
	employee.ACCTNO, 
	employee.BANK_ID, 
	dept, 
	employee.STATUSCD, 
	staff_status.`STATUS`, 
	tbl_salaryType.SalaryType, 
	tbl_bank.BNAME, 
	tbl_pfa.PFANAME
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
	LEFT JOIN
	tbl_bank
	ON 
		employee.BANK_ID = tbl_bank.bank_ID
	LEFT JOIN
	tbl_pfa
	ON 
		employee.PFACODE = tbl_pfa.PFACODE WHERE employee.STATUSCD = :STATUSCD OR employee.STATUSCD = :STATUSCD2';

        $params = [];

            $params = ['STATUSCD' => 'A',
                'STATUSCD2' => 'S' ];
            return $this->selectAll($query, $params);

    }
public function create_user($staff_id, $username, $password, $role_id, $deleted = 0) {
        try {
             $query = "INSERT INTO username (staff_id, username, password, role_id, deleted) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                username = VALUES(username),
                role_id = VALUES(role_id),
                deleted = VALUES(deleted)";
            $params = [$staff_id, $username, $password, $role_id, $deleted];
            return $this->executeNonSelect($query, $params);
        } catch (Exception $e) {
            error_log("Error in create_user: " . $e->getMessage());
            throw $e; // Let add_user.php handle the error
        }
    }

    public function create_bank($bankcode, $bankname)
    {
        $query = "INSERT INTO tbl_bank (bcode, bname) 
              VALUES (?, ?)
              ON DUPLICATE KEY UPDATE 
              bcode = VALUES(bcode), 
              bname = VALUES(bname)";
        $params = [$bankcode, $bankname];
        $result = $this->executeNonSelect($query, $params);

        $this->log('INSERT','tbl_bank',$params,$_SESSION['SESS_MEMBER_ID']);
        return $result;

    }
    public function create_pfa($pfacode, $pfaname)
    {
        $query = "INSERT INTO tbl_pfa (PFACODE, PFANAME) 
              VALUES (?, ?)
              ON DUPLICATE KEY UPDATE 
              PFACODE = VALUES(PFACODE), 
              PFANAME = VALUES(PFANAME)";
        $params = [$pfacode, $pfaname];
        $result = $this->executeNonSelect($query, $params);

        $this->log('INSERT','tbl_pfa',$params,$_SESSION['SESS_MEMBER_ID']);
        return $result;

    }

    public function create_dept($dept, $dept_auto=null)
    {
        if ($dept_auto==null){
            $query = "INSERT INTO tbl_dept (dept) 
              VALUES (:dept)";
            $params = [':dept' => $dept];
        }else{
        $query = "UPDATE tbl_dept SET dept = :dept WHERE dept_auto = :dept_auto";

        $params = [':dept'=> $dept, ':dept_auto'=>$dept_auto];
        }

        $result = $this->executeNonSelect($query, $params);

        $this->log('INSERT','tbl_dept',$params,$_SESSION['SESS_MEMBER_ID']);

        return $result;
          }

 public function generateStrongPassword($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $randomPassword = '';

        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomPassword;
    }

    public function getUsersDetails($staff_id = null)
    {
        // Initialize the query and parameters
        $query = 'SELECT
            username.staff_id,roles.role_id,
            employee.`NAME`,
            username.deleted,
            roles.role_name,EMAIL 
            FROM
            username
            LEFT JOIN roles ON username.role_id = roles.role_id
            LEFT JOIN employee ON username.staff_id = employee.staff_id ';

        $params = [];

        if ($staff_id !== null) {
            $query .= ' WHERE staff_id = :staff_id';
            $params = [':staff_id' => $staff_id];
            return $this->selectOne($query, $params);
        } else {
            return $this->selectAll($query, $params);
        }
    }

    public function getUsersPermission()
    {
        // Initialize the query and parameters
        $query = "SELECT
	permissions.permission_id, 
	permissions.role_id, 
	permissions.page, 
	roles.role_name
FROM
	permissions
	INNER JOIN
	roles
	ON 
	permissions.role_id = roles.role_id";

        return $this->selectAll($query, []);

    }

    public function getPages()
    {
        // Initialize the query and parameters
        $query = "SELECT url, `name` FROM pages";

        return $this->selectAll($query, []);

    }

    public function getPfaDetails($PFACODE = null)
    {
        // Initialize the query and parameters
        $query = 'SELECT
                    tbl_pfa.PFACODE, 
                    tbl_pfa.PFANAME
                    FROM
                    tbl_pfa ORDER BY PFACODE';
        $params = [];
        if ($PFACODE !== null) {
            $query .= ' WHERE PFACODE = :PFACODE';
            $params = [':PFACODE' => $PFACODE];
            return $this->selectOne($query, $params);
        } else {
            return $this->selectAll($query, $params);
        }
    }

    public function getBanksDetails($bank_ID = null)
    {
        // Initialize the query and parameters
        $query = 'SELECT
                    tbl_bank.bank_ID, 
                    tbl_bank.BCODE, 
                    tbl_bank.BNAME
                    FROM
                    tbl_bank';
        $params = [];
        if ($bank_ID !== null) {
            $query .= ' WHERE bank_ID = :bank_ID';
            $params = [':bank_ID' => $bank_ID];
            return $this->selectOne($query, $params);
        } else {
            return $this->selectAll($query, $params);
        }
    }

    public function getDeptDetails($deptid = null)
    {
        // Initialize the query and parameters
        $query = 'SELECT
	        tbl_dept.dept, 
	        tbl_dept.dept_auto
            FROM tbl_dept';
        $params = [];
        if ($deptid !== null) {
            $query .= ' WHERE dept_auto = :dept_auto';
            $params = [':dept_auto' => $deptid];
            return $this->selectOne($query, $params);
        } else {
            return $this->selectAll($query, $params);
        }
    }
public function insertPeriod($description,$periodYear){
    $querycheck = "SELECT * FROM payperiods WHERE description = :description AND periodYear = :periodYear";
	$params = [
        ':description' => $description,
        ':periodYear' => $periodYear
    ];
    $check = $this->selectAll($querycheck,$params);
    if($check){
        return false;
    }else{

        $query_update = 'UPDATE payperiods SET completed = 1';
        $this->executeNonSelect($query_update,[]);
        
        $query = "INSERT INTO payperiods (description, periodYear, active, payrollRun) VALUES (:description, :periodYear, 1, 0)";

        $this->log("INSERT","payperiods",$params,$_SESSION['SESS_MEMBER_ID']);


        return $this->executeNonSelect($query,$params);
          }
}
    public function getPayPeriod()
    {

        $query = "SELECT periodId, concat(description,'-',periodYear) AS description
                    FROM payperiods ORDER BY periodId DESC";
            return $this->selectAll($query, []);

    }

    public function log($operation, $table, $data, $userId) {
        $query = "INSERT INTO operation_logs (operation, table_name, data, user_id) VALUES (:operation, :table_name, :data, :user_id)";
        $stmt = $this->link->prepare($query);

        $operationVar = $operation;
        $tableVar = $table;
        $dataVar = json_encode($data);
        $userIdVar = $userId;

        $stmt->bindParam(':operation', $operationVar);
        $stmt->bindParam(':table_name', $tableVar);
        $stmt->bindParam(':data', $dataVar);
        $stmt->bindParam(':user_id', $userIdVar);

        $stmt->execute();
    }

    public function getEmployeeDetailsPayslip($staff_id,$period)
    {
        $array = [
            ':staff_id' => $staff_id,
            ':period' => $period
        ];

        $query = "SELECT
	master_staff.staff_id, 
	master_staff.`NAME`, 
	master_staff.OGNO, 
	tbl_dept.dept, 
	tbl_bank.BNAME, 
	master_staff.ACCTNO, 
	master_staff.GRADE, 
	master_staff.STEP, 
	IFNULL(tbl_salaryType.SalaryType,'') AS SalaryType
FROM
	master_staff
	LEFT JOIN
	tbl_dept
	ON 
		master_staff.DEPTCD = tbl_dept.dept_id
	LEFT JOIN
	tbl_bank
	ON 
		master_staff.BCODE = tbl_bank.BANK_ID
	LEFT JOIN
	tbl_salaryType
	ON 
		master_staff.SALARY_TYPE = tbl_salaryType.salaryType_id
        WHERE staff_id = :staff_id and period =:period";


        return $this->selectOne($query, $array);
    }
    public function insertMasterRecordAllow($staff_id, $allow_id, $value, $type, $period, $editTime, $userID) {
        $query = "INSERT INTO tbl_master (staff_id, allow_id, allow, type, period, editTime, userID) 
                  VALUES (:staff_id, :allow_id, :value, :type, :period, :editTime, :userID)";
        $params = [
            ':staff_id' => $staff_id,
            ':allow_id' => $allow_id,
            ':value' => $value,
            ':type' => $type,
            ':period' => $period,
            ':editTime' => $editTime,
            ':userID' => $userID
        ];

        $log = [
            ':staff_id' => $staff_id,
            ':allow_id' => $allow_id,
            ':value' => $value,
            ':type' => $type,
            ':period' => $period,
            ':editTime' => $editTime,
            ':userID' => $userID
        ];

        $this->log('INSERT', 'tbl_master',$log, $_SESSION['SESS_MEMBER_ID']);

        return $this->executeNonSelect($query, $params);
    }


    public function insertMasterRecordDeduc($staff_id, $allow_id, $value, $type, $period, $editTime, $userID) {
        $query = "INSERT INTO tbl_master (staff_id, allow_id, deduc, type, period, editTime, userID) 
                  VALUES (:staff_id, :allow_id, :value, :type, :period, :editTime, :userID)";
        $params = [
            ':staff_id' => $staff_id,
            ':allow_id' => $allow_id,
            ':value' => $value,
            ':type' => $type,
            ':period' => $period,
            ':editTime' => $editTime,
            ':userID' => $userID
        ];

        $this->log('INSERT', 'tbl_master',$params, $_SESSION['SESS_MEMBER_ID']);

        return $this->executeNonSelect($query, $params);

          }

    public function completedEarnings($staff_id,$allow_id,$period,$value,$type){
        $query = 'INSERT INTO completedLoan (staff_id,allow_id,period,value,type)VALUES (:staff_id,:allow_id,:period,:value,:type)';
        $array = [  ':staff_id'=> $staff_id,
                    ':allow_id'=> $allow_id,
                    ':period'=> $period,
                    ':value'=> $value,
                    ':type'=> $type
        ];

       return  $this->executeNonSelect($query,$array);
    }
    public function getEmployeesEarnings($staff_id,$edType) {
        $earning_array = [
            ':staff_id' => $staff_id,
            ':edType' => $edType
        ];
    $query =    "SELECT
	tbl_earning_deduction.ed,
	IFNULL(allow_deduc.`value`,0) AS value,
	allow_deduc.allow_id,allow_deduc.temp_id,
	tbl_earning_deduction.edType,
	allow_deduc.staff_id,allow_deduc.running_counter,allow_deduc.counter 
    FROM
	allow_deduc
	INNER JOIN tbl_earning_deduction ON allow_deduc.allow_id = tbl_earning_deduction.ed_id 
    WHERE staff_id = :staff_id AND edType = :edType ORDER BY allow_deduc.allow_id";

      return  $this->selectAll($query, $earning_array);

    }

    public function getGender(){
        $query = "SELECT
	employee.GENDER AS labels, 
	count(employee.staff_id) as series
FROM
	employee
	WHERE STATUSCD = 'A' and ISNULL(GENDER)=FALSE
	GROUP BY GENDER";
        return $this->selectAll($query,[]);
    }

    public function getDept(){
        $query = "SELECT
	tbl_dept.dept as labels, 
	count(employee.staff_id) as series
FROM
	employee
	INNER JOIN
	tbl_dept
	ON 
		employee.DEPTCD = tbl_dept.dept_id
		WHERE
	STATUSCD = 'A' AND
	ISNULL(DEPTCD) = FALSE
GROUP BY
	employee.DEPTCD";
        return $this->selectAll($query,[]);
    }

    public function getFinance(){
        $query = "SELECT
	sum(tbl_master.allow) as total_allowance, 
	sum(tbl_master.deduc) as total_deduction, 
	concat(payperiods.description, '-',payperiods.periodYear)  as month
    FROM
	tbl_master
	INNER JOIN
	payperiods
	ON 
		tbl_master.period = payperiods.periodId
		GROUP BY tbl_master.period";
        return $this->selectAll($query,[]);
    }


    public function getPaySlip($staff_id,$period) {
        $array = [
            ':staff_id' => $staff_id,
            ':period' => $period
        ];
        $query =    "SELECT
                tbl_master.allow_id,
                tbl_master.allow,
                tbl_master.deduc,
                tbl_earning_deduction.ed 
            FROM
                tbl_master
                INNER JOIN tbl_earning_deduction ON tbl_master.allow_id = tbl_earning_deduction.ed_id  
            WHERE staff_id = :staff_id and period = :period ORDER BY allow_id";

        return  $this->selectAll($query, $array);

    }

    public function getStaffList($batchSize, $offset) {
        $batchSize = (int)$batchSize;
        $offset = (int)$offset;
        $query = "SELECT email, staff_id FROM employee WHERE email IS NOT NULL AND STATUSCD = 'A' LIMIT $batchSize OFFSET $offset";
        return $this->selectAll($query);
    }



    public function lastActivePeriod() {
        $query = "SELECT max(periodid) as period FROM payperiods WHERE payrollrun = 1 AND active = 1 LIMIT 1";
        return $this->selectOne($query, []);
    }

    public function getOffset() {
        $query = "SELECT last_offset FROM email_offset ORDER BY id DESC LIMIT 1";
        return $this->selectOne($query, []);
    }
    public function updatePayPeriodFlag($period){
        $sql = "UPDATE payperiods SET payrollRun = :payrollRun WHERE periodId = :periodId";
        $params = [':payrollRun' => 1,
                    ':periodId' => $period
        ];
        return $this->executeNonSelect($sql,$params);

    }

    public function checkAuthentication() {
        if (!isset($_SESSION['SESS_MEMBER_ID'])) {
            header('Location: index.php');
            exit;
        }
    }
    public function getBusinessName() {
        $query = "SELECT
                    tbl_business.business_name,
                    tbl_business.town,
                    tbl_business.state,
                    tbl_business.tel 
                FROM
                    tbl_business";
        return $this->selectOne($query, []);
    }
    public function updateOffset($newOffset) {
        $query = "UPDATE email_offset SET last_offset = :newOffset ORDER BY id DESC LIMIT 1";
        $params = [':newOffset' => (int) $newOffset];
        $this->executeNonSelect($query, $params);
        file_put_contents('last_offset.txt', $newOffset);
    }
    public function initializeOffset() {
        $query = "INSERT INTO email_offset (last_offset) VALUES (0)";
        $this->executeNonSelect($query, []);
    }

    public function insertStaffMaster($STATUSCD,$SALARY_TYPE,$OGNO,$staff_id, $name, $deptcd, $bcode, $acctno, $grade, $step, $period, $pfacode, $pfaacctno) {

        $query = "INSERT INTO master_staff (STATUSCD,SALARY_TYPE,OGNO,staff_id, NAME, DEPTCD, BCODE, ACCTNO, GRADE, STEP, period, PFACODE, PFAACCTNO) 
              VALUES (:STATUSCD,:SALARY_TYPE,:OGNO,:staff_id, :NAME, :DEPTCD, :BCODE, :ACCTNO, :GRADE, :STEP, :period, :PFACODE, :PFAACCTNO)";
        $params = [
            ':STATUSCD' => $STATUSCD,
            ':SALARY_TYPE' => $SALARY_TYPE,
            ':staff_id' => $staff_id,
            ':NAME' => $name,
            ':DEPTCD' => $deptcd,
            ':BCODE' => $bcode,
            ':ACCTNO' => $acctno,
            ':GRADE' => $grade,
            ':STEP' => $step,
            ':period' => $period,
            ':PFACODE' => $pfacode,
            ':PFAACCTNO' => $pfaacctno,
            ':OGNO'=>$OGNO
        ];

        $this->log('INSERT', 'master_staff',$params, $_SESSION['SESS_MEMBER_ID']);

        return $this->executeNonSelect($query, $params);
       }


    public function updateAllowances($value,$user_id,$staff_id,$allow_id,$counter=0,$running_counter=0)
    {
        $query = "UPDATE allow_deduc SET value = :value, running_counter = :running_counter,counter = :counter, inserted_by = :inserted_by, date_insert = NOW() 
                        WHERE staff_id = :staff_id AND allow_id = :allow_id";
        $params = [
            ':value' => $value,
            ':counter' => $counter,
            ':inserted_by' => $user_id,
            ':staff_id' => $staff_id,
            ':allow_id' => $allow_id,
            'running_counter' => $running_counter,
        ];
        $this->log('UPDATE','allow_deduc',$params,$_SESSION['SESS_MEMBER_ID']);
        return $this->executeNonSelect($query, $params);

    }
    public function insertAllowances($value,$user_id,$staff_id,$allow_id,$counter=0,$running_counter=0){
        $params = [
            ':value' => $value,
            ':counter' => $counter,
            ':inserted_by' => $user_id,
            ':staff_id' => $staff_id,
            ':allow_id' => $allow_id,
            'running_counter' => $running_counter,
        ];
        $query = 'INSERT INTO allow_deduc(staff_id,allow_id,value,counter,inserted_by,date_insert,running_counter)
                                            VALUES (:staff_id,:allow_id,:value,:counter,:inserted_by,NOW(),:running_counter)';
        $this->log('INSERT','allow_deduc',$params,$_SESSION['SESS_MEMBER_ID']);
        return $this->executeNonSelect($query, $params);


    }


    public function updateEmployeeProrate($staff_id,$step){
        $query = 'update employee set STEP = :step WHERE staff_id = :staff_id';
        $array = [
            ':staff_id' => $staff_id,
            ':step' => $step,
        ];
      return  $this->executeNonSelect($query, $array);
    }
    function getProratedAllowances($staff_id) {
        $query = 'SELECT prorate_allow_deduc.`value`, prorate_allow_deduc.allow_id, tbl_earning_deduction.edDesc 
              FROM tbl_earning_deduction 
              INNER JOIN prorate_allow_deduc ON tbl_earning_deduction.ed_id = prorate_allow_deduc.allow_id 
              WHERE staff_id = :staff_id 
              ORDER BY allow_id ASC';

        $array = [
            ':staff_id'=> $staff_id
        ];

        return $this->selectAll($query,$array);


    }
    public function construct()
    {
        try {
            $this->link = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->dbname,
                $this->user, $this->pass,
                array(PDO::ATTR_PERSISTENT => true));
            $this->link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

           $this->startingSession();

        } catch (PDOException $e) {
            echo "Failed Connection: " . $e->getMessage();
        }

    }

    public function isPayslipAvailable($staff_id, $period){
        $array = [':staff_id' => $staff_id, ':period' => $period];

        $query = 'SELECT master_staff.staff_id, master_staff.period FROM master_staff 
                    WHERE staff_id = :staff_id and period = :period';
        return $this->selectOne($query,$array);

    }
    public function calculateProratedValue($value, $DaysToCal, $no_days) {
        $value = intval($value);
        $DaysToCal = intval($DaysToCal);
        $no_days = intval($no_days);
        return ($DaysToCal * $value) / $no_days;
    }

    public function calculateSuspendedValue($value, $suspensionfactor) {
        $value = intval($value);
        $suspensionfactor = floatval($suspensionfactor);
        return ($value*$suspensionfactor);
    }

    public function getDaysOfMonth($date){
        $split = explode(' ',$date, 2);
        $mon = date('m', strtotime($split[0]));
        $yr = $split[1];
       return $daysInCurrentMonth = cal_days_in_month(CAL_GREGORIAN, $mon, $yr);

    }

    function displayAllowances($allowances) {
        echo '<table class="table table-bordered table-hover">';
        echo '<thead>';
        echo '<tr class="earnings-ded-header">';
        echo '<th> Code </th>';
        echo '<th> Description </th>';
        echo '<th> Amount </th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        foreach ($allowances as $allowance) {
            echo '<tr class="odd gradeX">';
            echo '<td>' . $allowance['allow_id'] . '</td>';
            echo '<td>' . $allowance['edDesc'] . '</td>';
            echo '<td class="align-right">' . number_format($allowance['value']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
    }
    public function startingSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function validateSession($path){
        if(!$_SESSION['$SESS_MEMBER_ID']){
        header('location:'.$path);
        }
    }
    public function businessInfo()
    {
     $query_bus = $this->link->prepare('SELECT * FROM tbl_business');
        $fin_bus = $query_bus->execute(array());
        $row_bus = $query_bus->fetch();

        $_SESSION['businessname'] = $this->businessName = $row_bus['business_name'];
        $this->town = $row_bus['town'];
        $this->state = $row_bus['state'];
        $this->tel = $row_bus['tel'];

        $data['success'] = 'true';
        $data['message'] = 'Successfully Login';
    }
public function updateGradeStep($grade,$step,$staff_id)
{
    $query = 'UPDATE employee SET grade = :grade , step = :step, userID = :userID, editTime = :editTime WHERE staff_id = :staff_id';
    $recordtime = date('Y-m-d H:i:s');
    $array = [
        ':grade' => $grade,
        ':step' => $step,
        ':staff_id' => $staff_id,
        'userID' => $_SESSION['SESS_MEMBER_ID'],
        'editTime' => $recordtime
    ];
    return $this->executeNonSelect($query,$array);
}
    public function getSalaryValue($grade, $step, $salaryType, $allow_id) {
        // Array for binding parameters
        $array = [
            ':grade' => $grade,
            ':step' => $step,
            ':salaryType' => $salaryType,
            ':allow_id' => $allow_id
        ];
            return $this->selectOne("SELECT allowancetable.value FROM allowancetable WHERE
                    grade = :grade AND step = :step AND SALARY_TYPE = :salaryType AND allowcode = :allow_id", $array);

    }

    public function getExtra($dept_id, $allow_id) {
        // Array for binding parameters
        $array = [
            ':dept_id' => $dept_id,
            ':allow_id' => $allow_id
        ];
        return $this->selectOne("SELECT amount FROM tbl_extrapayment WHERE allow_id = :allow_id and dept_id = :dept_id", $array);

    }

    public function selectAll($query,$array=[]){
        $rows = $this->link->prepare($query);
        $rows->execute($array);
        $allRows = $rows->fetchAll(PDO::FETCH_ASSOC);
        if($allRows){
            return $allRows;
        }else{
            return false;
        }
    }

    public function executeNonSelect($query, $array = []) {
        $stmt = $this->link->prepare($query);
        return $stmt->execute($array);  // Returns true on success or false on failure
    }

  public function getRoles() {
    $query = "SELECT role_id, role_name FROM roles";
    $stmt = $this->link->prepare($query);
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC); // Use PDO fetchAll
    return $roles;
}

    public function insertWithLastInsertID($query, $array = []) {
        $stmt = $this->link->prepare($query);
        $result = $stmt->execute($array);
        if ($result) {
            return $this->link->lastInsertId();  // Returns the ID of the inserted row
        } else {
            return false;
        }
    }


    public function selectOne($query,$array=[]){
        $row = $this->link->prepare($query);
        $row->execute($array);

        $singleRow = $row->fetch(PDO::FETCH_ASSOC);
        if($singleRow){
            return $singleRow;
        }else{
            return false;
        }
    }
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    public function getAllowanceDescription($allow_id){
        $query = 'select ed from tbl_earning_deduction where ed_id = :allow_id';
        $param = [':allow_id' => $allow_id];
        return $this->selectOne($query,$param);
    }
    public function getStaffName($staff_id){
        $query = 'select NAME from employee where staff_id = :staff_id';
        $param = [':staff_id' => $staff_id];
        return $this->selectOne($query,$param);
    }

    public function selectDrop($query = "SELECT
	tbl_earning_deduction.ed, 
	tbl_earning_deduction.ed_id
    FROM
	tbl_earning_deduction",$array = []){
        $rows = $this->link->prepare($query);
        $rows->execute($array);
        $allRows = $rows->fetchAll(PDO::FETCH_ASSOC);
        if($allRows){
            return $allRows;
        }else{
            return false;
        }
    }
    public function googlelogin($email)
    {
        $_SESSION['google_login'] = false;
        try {
            $query = $this->link->prepare('SELECT employee.name, role_id,employee.EMAIL,username.username,username.profile_picture, username.`password`, username.role, username.staff_id FROM username
                    INNER JOIN employee ON employee.staff_id = username.staff_id WHERE EMAIL = ? AND deleted = ?');
            $fin = $query->execute(array($email, '0'));


            if (isset($_SESSION['periodstatuschange'])) {
                unset($_SESSION['periodstatuschange']);
            }
            // password_verify(
            if (($row = $query->fetch())) {

                $_SESSION['logged_in'] = '1';
                $_SESSION['google_login'] = true;
                $_SESSION['user'] = $row['username'];
                $_SESSION['SESS_MEMBER_ID'] = $row['staff_id'];
                $_SESSION['profilePicture'] = $row['profile_picture'];
                $_SESSION['email'] = $row['EMAIL'];
                $_SESSION['SESS_FIRST_NAME'] = $row['name'];
                $_SESSION['SESS_LAST_NAME'] = $row['name'];
                $_SESSION['role_id'] = $row['role_id'];
                $_SESSION['emptrack'] = 0;
                $_SESSION['empDataTrack'] = 'next';
                $_SESSION['staff'] = 1;

                $this->businessInfo();
                //Get current active period for the organization
                $payp = $this->link->prepare('SELECT periodId, description, periodYear FROM payperiods WHERE active = ? ORDER BY periodId DESC LIMIT 1');
                $myperiod = $payp->execute(array(1));
                $final = $payp->fetch();
                $_SESSION['currentactiveperiod'] = $final['periodId'];
                $_SESSION['activeperiodDescription'] = $final['description'] . " " . $final['periodYear'];

                //If temp period change, reset session
                if (isset($_SESSION['periodstatuschange'])) {
                    unset($_SESSION['periodstatuschange']);
                }

                header('Location: home.php');
                exit();

            } else {
                header('Location: index.php?error=Email not Found');
                exit();
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        return json_encode([]);

    }

    public function checkToken($token)
    {
        $response = [];
        $check = isset($_COOKIE[$token]) ? true : false;
        $response = ["token" => $check];
        echo json_encode($response);
    }

    public function tokenVerify(){

          $token = $_COOKIE['token'];

        $stmt = $this->link->prepare("SELECT
                employee.`name`, 
                username.profile_picture, 
                role_id, 
                employee.EMAIL, 
                username.username, 
                username.`password`, 
                username.role, 
                username.staff_id
            FROM
                username
                INNER JOIN
                employee
                ON 
                    employee.staff_id = username.staff_id
                INNER JOIN
                user_tokens
                ON user_tokens.user_id = username.username WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {

            $_SESSION['logged_in'] = '1';
            $_SESSION['user'] = $user['username'];
            $_SESSION['SESS_MEMBER_ID'] = $user['staff_id'];
            $_SESSION['profilePicture'] = $user['profile_picture'];
            $_SESSION['email'] = $user['EMAIL'];
            $_SESSION['SESS_FIRST_NAME'] = $user['name'];
            $_SESSION['SESS_LAST_NAME'] = $user['name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['emptrack'] = 0;
            $_SESSION['empDataTrack'] = 'next';
            $_SESSION['staff'] = 1;

            $this->businessInfo();
            //Get current active period for the organization
            $payp = $this->link->prepare('SELECT periodId, description, periodYear FROM payperiods WHERE active = ? ORDER BY periodId DESC LIMIT 1');
            $myperiod = $payp->execute(array(1));
            $final = $payp->fetch();
            $_SESSION['currentactiveperiod'] = $final['periodId'];
            $_SESSION['activeperiodDescription'] = $final['description'] . " " . $final['periodYear'];

            //If temp period change, reset session
            if (isset($_SESSION['periodstatuschange'])) {
                unset($_SESSION['periodstatuschange']);
            }

            $data['success'] = 'true';
            $data['message'] = 'Login successful';

            echo  json_encode($data);

        }
    }
    public function setCookie($userId){

        $token = bin2hex(random_bytes(16));

        // Store the token in the database with user ID and expiration time
        $expiryTime = time() + (15 * 24 * 60 * 60); // 30 days
        $stmt = $this->link->prepare("INSERT INTO user_tokens (user_id, token, expires_at)
                                        VALUES (?, ?, ?)
                                        ON DUPLICATE KEY UPDATE
                                            token = VALUES(token),
                                            expires_at = VALUES(expires_at);
                                        ");
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';


        $cookieOptions = [
            'expires' => $expiryTime,
            'path' => '/',
            'secure' => $isSecure,  // Set to true if HTTPS
            'httponly' => true
        ];
        $stmt->execute([$userId, $token, date('Y-m-d H:i:s', $expiryTime)]);
        setcookie('token', $token, $cookieOptions);


    }
    public function login($username, $password,$rememberMeCheckbox)
    {

		$errmsg_arr = array();
		$errflag = false;

		$errors = array();
		$data = array();

		$uname = filter_var((filter_var($username)));
		$pass = filter_var($password);

		if ($uname == '') {
            $errmsg_arr[] = 'Username missing';
            $errflag = true;
        }
        if ($pass == '') {
            $errmsg_arr[] = 'Password missing';
            $errflag = true;
        }

        try {
            $query = $this->link->prepare('SELECT employee.name, username.profile_picture, role_id,employee.EMAIL,username.username, username.`password`, username.role, username.staff_id FROM username
                    INNER JOIN employee ON employee.staff_id = username.staff_id WHERE username = ? AND deleted = ?');
            $fin = $query->execute(array($uname, '0'));


            if (isset($_SESSION['periodstatuschange'])) {
                unset($_SESSION['periodstatuschange']);
            }
            // password_verify(
            if (($row = $query->fetch()) and (password_verify($pass, $row['password']))) {

                $_SESSION['logged_in'] = '1';
                $_SESSION['user'] = $row['username'];
                $_SESSION['SESS_MEMBER_ID'] = $row['staff_id'];
                $_SESSION['profilePicture'] = $row['profile_picture'];
                $_SESSION['email'] = $row['EMAIL'];
                $_SESSION['SESS_FIRST_NAME'] = $row['name'];
                $_SESSION['SESS_LAST_NAME'] = $row['name'];
                $_SESSION['role_id'] = $row['role_id'];
                $_SESSION['emptrack'] = 0;
                $_SESSION['empDataTrack'] = 'next';
                $_SESSION['staff'] = 1;

                $this->businessInfo();
                //Get current active period for the organization
                $payp = $this->link->prepare('SELECT periodId, description, periodYear FROM payperiods WHERE active = ? ORDER BY periodId DESC LIMIT 1');
                $myperiod = $payp->execute(array(1));
                $final = $payp->fetch();
                $_SESSION['currentactiveperiod'] = $final['periodId'];
                $_SESSION['activeperiodDescription'] = $final['description'] . " " . $final['periodYear'];

                //If temp period change, reset session
                if (isset($_SESSION['periodstatuschange'])) {
                    unset($_SESSION['periodstatuschange']);
                }

                $data['success'] = 'true';
                $data['message'] = 'Login successful';

                if($rememberMeCheckbox){
                    $this->setCookie($_SESSION['SESS_MEMBER_ID']);
                }

            } else {

                $data['success'] = 'false';
                $data['message'] = 'Invalid Username and Password';

            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        return json_encode([]);

    }
}