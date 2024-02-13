<!DOCTYPE html>
<?php 
require_once('../Connections/hms.php');
 
?>	
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title>Import Excel To Mysql Database Using PHP </title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="Import Excel File To MySql Database Using php">
 
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/bootstrap-responsive.min.css">
		<link rel="stylesheet" href="css/bootstrap-custom.css">
 
 
	</head>
	<body>    
 
	<!-- Navbar
    ================================================== -->
 
	<div class="navbar navbar-inverse navbar-fixed-top">
		<div class="navbar-inner">
			<div class="container"> 
				<strong>
					<a class="brand" href="#">Import Excel To Contribution</a></strong>
 
			</div>
		</div>
	</div>
 
	<div id="wrap">
	<div class="container">
	  <div class="row">
			<div class="span3 hidden-phone">Done Uploading? <a href="../editContributions.php">Go Back</a></div>
			<div class="span6" id="form-login">
				<form class="form-horizontal well" action="import_number.php" method="post" name="upload_excel" enctype="multipart/form-data">
					<fieldset>
						<legend>Import CSV/Excel file</legend>
						<div class="control-group">
							<div class="control-label">
								<label>CSV/Excel File:</label>
							</div>
							<div class="controls">
								<input type="file" name="file" id="file" class="input-large">
							</div>
						</div>
 
						<div class="control-group">
							<div class="controls">
							<button type="submit" id="submit" name="Import" class="btn btn-primary button-loading" data-loading-text="Loading...">Upload</button>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<div class="span3 hidden-phone"></div>
		</div>
 
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
				  		<th>Total</th>
 
 
				  	</tr>	
				  </thead>
			<?php
				mysql_select_db($database_hms, $hms);
				$SQLSELECT = "SELECT * FROM tbl_contributions";
				$result_set =  mysql_query($SQLSELECT, $hms);
				$grantotal = 0;
				while($row = mysql_fetch_array($result_set))
				{
				?>
 
					<tr align="right">
					  <td><?php echo $row['passbook_no']; ?></td>
						<td><?php echo $row['contribution']; ?></td>
						<td><?php echo $row['normal_loan']; ?></td>
						<td><?php echo $row['mortgage_loan']; ?></td>
						<td><?php echo $row['car_loan']; ?></td>
						<td><?php echo $row['electronic_loan']; ?></td>
						<td><?php echo $row['soft_loan']; ?></td>
						<td align="right"><?php echo number_format($row['contribution'] + $row['normal_loan']+$row['mortgage_loan']+$row['car_loan']+$row['electronic_loan']+$row['soft_loan'],2); ?></td>
 
 
					</tr>
                    <?php
					$sum = $row['contribution'] + $row['normal_loan']+$row['mortgage_loan']+$row['car_loan']+$row['electronic_loan']+$row['soft_loan'];
				$grantotal = $sum+$grantotal;}
			?>
					<tr>
					  <td colspan="7" align="right"><strong>GRAND TOTAL</strong></td>
					  <td align="right"><strong><?php echo number_format($grantotal,2);?></strong></td>
		  </tr>
				
		</table>
	</div>
 
	</div>
 
	</body>
</html>