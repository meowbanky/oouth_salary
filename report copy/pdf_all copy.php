<?php

if (!isset($period)) {
    $period = -1;
}
$db_server = "localhost";
$db_user =     "root";
$db_passwd = "Oluwaseyi";
$dbname = "salary";
$con = mysqli_connect($db_server, $db_user, $db_passwd, $dbname);
try {
    $conn = new PDO("mysql:host=$db_server;dbname=salary", $db_user, $db_passwd, array(PDO::ATTR_PERSISTENT => true));
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Failed Connection: " . $e->getMessage();
}
$begin = 0;
$ofset = 3;
$sql = "SELECT * FROM employee WHERE statuscd = 'A' LIMIT {$begin},{$offset} ";
$result = mysqli_query($con, $sql);

if (mysqli_num_rows($result) > 0) {
    // output data of each row
    $html = '';
    $html .= '<table border = "1">';
    $html .= '<tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        $html .= '<td><table border="1"><tr><td>' . $row["staff_id"] . '</td>
        <td>' . $row["NAME"] . '</td></tr></table> </td>';
   }
} else {
    echo "0 results";
}
$html .= '</tr>';
echo $html;
mysqli_close($con);
