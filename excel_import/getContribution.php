<?php 
require_once('../Connections/hms.php');
$period_id = -1;
if (isset($_GET['period_id'])){
$period_id = $_GET['period_id'];	
	}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Untitled Document</title>
</head>

<body>
<table border="1" class="table table-bordered">
			<thead>
				  	<tr>
				  	  <th>Staff No</th>
				  		<th>Contribution</th>
				  		<th>Normal Loan</th>
				  		<th>Mortgage Loan</th>
				  		<th>Vehicle</th>
				  		<th>Electronic Loan</th>
				  		<th>Soft Loan</th>
				  		<th>Ileya Loan</th>
				  		<th>Total</th>
 
 
				  	</tr>	
				  </thead>
			<?php
							
				mysql_select_db($database_hms, $hms);
				$SQLSELECT = "SELECT * FROM tbl_contributions where period_id = '".$period_id."'";
				$result_set =  mysql_query($SQLSELECT, $hms);
				$grantotal = 0;
				while($row = mysql_fetch_array($result_set))
				{
				?>
 
					<tr align="right">
					  <td><?php echo $row['passbook_no']; ?></td>
						<td><?php echo number_format($row['contribution'],2); ?></td>
						<td><?php echo number_format($row['normal_loan'],2); ?></td>
						<td><?php echo number_format($row['mortgage_loan'],2); ?></td>
						<td><?php echo number_format($row['car_loan'],2); ?></td>
						<td><?php echo number_format($row['electronic_loan'],2); ?></td>
						<td><?php echo number_format($row['soft_loan'],2); ?></td>
						<td><?php echo number_format($row['ileya_loan'],2); ?></td>
						<td align="right"><?php echo number_format($row['contribution'] + $row['normal_loan']+$row['mortgage_loan']+$row['car_loan']+$row['electronic_loan']+$row['soft_loan']+$row['ileya_loan'],2); ?></td>
 
 
					</tr>
                    <?php
					$sum = $row['contribution'] + $row['normal_loan']+$row['mortgage_loan']+$row['car_loan']+$row['electronic_loan']+$row['soft_loan']+$row['ileya_loan'];
				$grantotal = $sum+$grantotal;}
			?>
					<tr>
					  <td colspan="8" align="right"><strong>GRAND TOTAL</strong></td>
					  <td align="right"><strong><?php echo number_format($grantotal,2);?></strong></td>
		  </tr>
				
		</table>
</body>
</html>