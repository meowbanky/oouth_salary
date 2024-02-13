<?php require_once('Connections/pos.php'); ?>
<?php

session_start();


function createRandomPassword() {
	$chars = "003232303232023232023456789";
	srand((double)microtime()*1000000);
	$i = 0;
	$pass = '' ;
	while ($i <= 7) {

		$num = rand() % 33;

		$tmp = substr($chars, $num, 1);

		$pass = $pass . $tmp;

		$i++;

	}
	return $pass;
}

if (!function_exists("GetSQLValueString")) {
function GetSQLValueString($theValue, $theType, $theDefinedValue = "", $theNotDefinedValue = "") 
{
  if (PHP_VERSION < 6) {
    $theValue = get_magic_quotes_gpc() ? stripslashes($theValue) : $theValue;
  }

  $theValue = function_exists("mysql_real_escape_string") ? mysql_real_escape_string($theValue) : mysql_escape_string($theValue);

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

mysql_select_db($database_pos, $pos);
if ($_SESSION['role'] == 'Admin'){
    $query_suspended = "SELECT
transfer.invoice_no,
transfer.sale_time,
transfer.sale_id,
outlet.description
FROM
transfer
INNER JOIN transfer_items ON transfer_items.invoice_no = transfer.invoice_no
INNER JOIN outlet ON transfer.requesting_store = outlet.outlet_id
WHERE transfer.suspended = 1 AND deleted  = 0 GROUP BY invoice_no
order by sale_time desc";
}else{
$query_suspended = "SELECT
transfer.invoice_no,
transfer.sale_time,
transfer.sale_id,
outlet.description
FROM
transfer
INNER JOIN transfer_items ON transfer_items.invoice_no = transfer.invoice_no
INNER JOIN outlet ON transfer.requesting_store = outlet.outlet_id
WHERE transfer.suspended = 1 AND deleted  = 0 and requesting_store = '".$_SESSION['location']. "'
GROUP BY invoice_no
order by sale_time desc";
}
$suspended = mysql_query($query_suspended, $pos) or die(mysql_error());
$row_suspended = mysql_fetch_assoc($suspended);
$totalRows_suspended = mysql_num_rows($suspended);

mysql_select_db($database_pos, $pos);
$query_location = "SELECT outlet.outlet_id, outlet.outletName FROM outlet ";
$location = mysql_query($query_location, $pos) or die(mysql_error());
$row_location = mysql_fetch_assoc($location);
$totalRows_location = mysql_num_rows($location);

if (isset($_GET['suspend']) and ($_GET['suspend'] == 'suspend')){
	mysql_select_db($database_pos, $pos);
	
$query_customerCheck = sprintf("SELECT transfer.invoice_no FROM transfer WHERE transfer.invoice_no = %s", GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
$customerCheck = mysql_query($query_customerCheck, $pos) or die(mysql_error());
$row_customerCheck = mysql_fetch_assoc($customerCheck);
$totalRows_customerCheck = mysql_num_rows($customerCheck);
	
	if ($totalRows_customerCheck == 0){
	
	$suspendSQL = sprintf("INSERT INTO transfer (sale_time,employee_id,invoice_no,suspended) VALUES (now(),%s, %s,1)",
					   GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "int"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
	}else {
		
		$suspendSQL = sprintf("UPDATE transfer SET sale_time = NOW(),employee_id = %s,invoice_no = %s,suspended = 1 WHERE  invoice_no = %s",
					   GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "int"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
			}
  mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($suspendSQL, $pos) or die(mysql_error());
	
	unset($_SESSION['SESS_INVOICE']); 
	
	
	if (!isset($_SESSION['SESS_INVOICE']) or ($_SESSION['SESS_INVOICE'] == '')){
$_SESSION['SESS_INVOICE'] = 'TR-'.createRandomPassword();


}
	
	}
	
	if (isset($_GET['pid']) and ($_GET['delete'] == 'delete')){
	
	$suspendSQL = sprintf("DELETE FROM transfer_payments WHERE payment_id = %s ",
					   GetSQLValueString($_GET['pid'], "int"));

  mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($suspendSQL, $pos) or die(mysql_error());
	
		
	}
	
if (isset($_POST['amount_tendered'])){
	
	$query_amountCheck = sprintf("SELECT transfer_payments.invoice_no FROM transfer_payments WHERE transfer_payments.invoice_no = %s", GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
$amountCheck = mysql_query($query_amountCheck, $pos) or die(mysql_error());
$row_amountCheck = mysql_fetch_assoc($amountCheck);
$totalRows_amountCheck = mysql_num_rows($amountCheck);
	
	if ($totalRows_amountCheck == 0){
	
	
	$suspendSQL = sprintf("INSERT INTO transfer_payments (invoice_no,payment_type,payment_amount,amount_tendered,payment_date) VALUES (%s, %s, %s,%s,now())",
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"),
					   GetSQLValueString($_POST['payment_type'], "text"),
					   GetSQLValueString($_POST['amount_due'], "double"),
					   GetSQLValueString($_POST['amount_tendered'], "double"));

 
	} else {
		
		
		$suspendSQL = sprintf("UPDATE transfer_payments SET amount_tendered = %s, payment_type = %s WHERE  invoice_no = %s",
					   GetSQLValueString($_POST['amount_tendered'], "double"),
					   GetSQLValueString($_POST['payment_type'], "text"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
		
		}
		
		 mysql_select_db($database_pos, $pos);
		 $Result1 = mysql_query($suspendSQL, $pos) or die(mysql_error());
		
	}
	

if (isset($_GET['delete_customer']) and ($_GET['delete_customer'] == 'delete_customer')){
	
	           $unattachCustSQL = sprintf("UPDATE transfer SET customer_id = NULL where invoice_no = %s",
			GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));

			mysql_select_db($database_pos, $pos);
			$Result1 = mysql_query($unattachCustSQL, $pos) or die(mysql_error());
}

	
if (isset($_POST['cancel']) and ($_POST['cancel'] == 'cancel')){
	
	$query_customerCheck = sprintf("SELECT transfer.invoice_no FROM transfer WHERE transfer.invoice_no = %s", GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
$customerCheck = mysql_query($query_customerCheck, $pos) or die(mysql_error());
$row_customerCheck = mysql_fetch_assoc($customerCheck);
$totalRows_customerCheck = mysql_num_rows($customerCheck);
	
	if ($totalRows_customerCheck == 0){
	
	$suspendSQL = sprintf("INSERT INTO transfer (sale_time,employee_id,invoice_no,deleted,deleted_by) VALUES (now(),%s, %s,1,%s)",
					   GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "int"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"),
					   GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "int"));
	}else {
		
		$suspendSQL = sprintf("UPDATE transfer SET sale_time = NOW(),employee_id = %s,deleted = 1,deleted_by = %s WHERE invoice_no = %s",
					   GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "int"),
					   GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "int"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
}

  mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($suspendSQL, $pos) or die(mysql_error());
	
	unset($_SESSION['SESS_INVOICE']); 
	
	if (!isset($_SESSION['SESS_INVOICE']) or ($_SESSION['SESS_INVOICE'] == '')){
$_SESSION['SESS_INVOICE'] = 'TR-'.createRandomPassword();


}
	
	}


if (isset($_POST['invoice_no_fromReceipt'])){
  $_SESSION['SESS_INVOICE'] =  $_POST['invoice_no_fromReceipt'];
    
}

$col_invoice = "-1";
if (isset($_SESSION['SESS_INVOICE'])) {
  $col_invoice = $_SESSION['SESS_INVOICE'];
}

mysql_select_db($database_pos, $pos);
$query_requestingOffice = sprintf("SELECT
transfer.invoice_no,
transfer.sale_time,
transfer.sale_id,transfer.requesting_store
FROM
transfer
WHERE transfer.invoice_no = %s", GetSQLValueString($col_invoice, "text"));
$requestingOffice = mysql_query($query_requestingOffice, $pos) or die(mysql_error());
$row_requestingOffice = mysql_fetch_assoc($requestingOffice);
$totalRows_requestingOffice = mysql_num_rows($requestingOffice);

if(isset($row_requestingOffice['requesting_store'])){
//$_POST['customer'] = $row_requestingOffice['requesting_store'] ;
}

$col_customerSearch = "-1";
if (isset($_POST['customer'])) {
  $col_customerSearch = $_POST['customer'];
}
mysql_select_db($database_pos, $pos);
$query_customerSearch = sprintf("SELECT outlet.outlet_id, outlet.outletName, outlet.description FROM outlet WHERE outlet.outlet_id = %s", GetSQLValueString($col_customerSearch, "text"));
$customerSearch = mysql_query($query_customerSearch, $pos) or die(mysql_error());
$row_customerSearch = mysql_fetch_assoc($customerSearch);
$totalRows_customerSearch = mysql_num_rows($customerSearch);



if ($totalRows_customerSearch > 0){
	
mysql_select_db($database_pos, $pos);
$query_customerCheck = sprintf("SELECT transfer.invoice_no FROM transfer WHERE transfer.invoice_no = %s", GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
$customerCheck = mysql_query($query_customerCheck, $pos) or die(mysql_error());
$row_customerCheck = mysql_fetch_assoc($customerCheck);
$totalRows_customerCheck = mysql_num_rows($customerCheck);
	
	if ($totalRows_customerCheck == 0){
	$customerSQL = sprintf("INSERT INTO transfer (customer_id, employee_id,invoice_no) VALUES (%s,%s,%s)",
					   GetSQLValueString($col_customerSearch, "int"),
					   GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "int"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
}else {
	
	$customerSQL = sprintf("UPDATE transfer SET customer_id =%s WHERE invoice_no = %s",
					   GetSQLValueString($col_customerSearch, "int"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
	}
  mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($customerSQL, $pos) or die(mysql_error());
	}

// transfer from to info

if ($totalRows_customerSearch > 0){
	
mysql_select_db($database_pos, $pos);
$query_customerCheck = sprintf("SELECT tbl_request.invoice_no FROM tbl_request WHERE tbl_request.invoice_no = %s", GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
$customerCheck = mysql_query($query_customerCheck, $pos) or die(mysql_error());
$row_customerCheck = mysql_fetch_assoc($customerCheck);
$totalRows_customerCheck = mysql_num_rows($customerCheck);
	
	if ($totalRows_customerCheck == 0){
	$customerSQL = sprintf("INSERT INTO tbl_request (request_to, request_from,invoice_no) VALUES (%s,%s,%s)",
					   GetSQLValueString($col_customerSearch, "int"),
					   GetSQLValueString($_SESSION['location'], "int"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
}else {
	
	$customerSQL = sprintf("UPDATE tbl_request SET request_to =%s WHERE invoice_no = %s",
					   GetSQLValueString($col_customerSearch, "int"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
	}
  mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($customerSQL, $pos) or die(mysql_error());
	}



// Get requesting Outlet

$query_requestButtonSql = sprintf("SELECT tbl_request.trans_to_from_id,tbl_request.invoice_no, tbl_request.request_from, tbl_request.request_to, tbl_request.`status` FROM tbl_request WHERE invoice_no = %s",GetSQLValueString($_SESSION['SESS_INVOICE'],"text"));
$requestButtonSql = mysql_query($query_requestButtonSql,$pos) or die(mysql_error());
$row_requestButtonSql = mysql_fetch_assoc($requestButtonSql);
$total_requestButtonSql = mysql_num_rows($requestButtonSql);

$col_itemSearch = "-1";
if (isset($_POST['item'])) {
  $col_itemSearch = $_POST['item'];
}
mysql_select_db($database_pos, $pos);
$query_itemSearch = sprintf("SELECT items.`name`, items.item_id,  items.isbn, item_price.cost_price, 
item_price.retail_price, item_price.whole_price, item_price.intermediate_price 
FROM items INNER JOIN item_price ON item_price.item_id = items.item_id  WHERE ((items.`name` <> '' and items.deleted <> 1) and (items.item_id = %s or items.isbn = %s))", GetSQLValueString($col_itemSearch, "text"),GetSQLValueString($col_itemSearch, "text"));
$itemSearch = mysql_query($query_itemSearch, $pos) or die(mysql_error());
$row_itemSearch = mysql_fetch_assoc($itemSearch);
$totalRows_itemSearch = mysql_num_rows($itemSearch);

// get stock quantity

$query_stockQty = sprintf("SELECT Sum(inventory.trans_inventory) as stockBalance,trans_items FROM inventory WHERE location_id = %s and trans_items = %s GROUP BY inventory.trans_items", GetSQLValueString($_SESSION['location'], "text"),GetSQLValueString($col_itemSearch, "text"));
$stockQty = mysql_query($query_stockQty, $pos) or die(mysql_error());
$row_stockQty = mysql_fetch_assoc($stockQty);
$totalRows_stockQty = mysql_num_rows($stockQty);



if ($totalRows_itemSearch == 0){
	
$error = '1';
	
	
	}else{

//$inserttransferSQL = "INSERT INTO transfer (employee_id) VALUES (".$_SESSION['SESS_MEMBER_ID'].")";
//mysql_select_db($database_pos, $pos);
//$Result1 = mysql_query($inserttransferSQL, $pos) or die(mysql_error());

//$transfer_id = mysql_insert_id();

if (isset($_POST['item'])) {
	
mysql_select_db($database_pos, $pos);
$query_itemSearchCheck = sprintf("SELECT transfer_items.quantity_purchased FROM transfer_items WHERE transfer_items.invoice_no = %s AND transfer_items.item_id = %s ", GetSQLValueString($_SESSION['SESS_INVOICE'], "text"),GetSQLValueString($row_itemSearch['item_id'], "text"));
$itemSearchCheck = mysql_query($query_itemSearchCheck, $pos) or die(mysql_error());
$row_itemSearchCheck = mysql_fetch_assoc($itemSearchCheck);
$totalRows_itemSearchCheck = mysql_num_rows($itemSearchCheck);	
	
if ($totalRows_itemSearchCheck == 0){

	//echo $row_itemSearch['item_id'] .'  '. $_SESSION['SESS_INVOICE'].'  '.$totalRows_itemSearchCheck;
	//exit;
	//if ($_POST['mode'] == 0){
//		
//		$sellingPrice = $row_itemSearch['retail_price'];
//	
//		}elseif ($_POST['mode'] == 1) {
//			if ($row_itemSearch['whole_price'] == '0.00'){
//				$sellingPrice = $row_itemSearch['retail_price'];
//				}else{
//					$sellingPrice = $row_itemSearch['whole_price'];
//					
//					}
//			}elseif ($_POST['mode'] == 2) {
//			if ($row_itemSearch['intermediate_price'] == '0.00'){
//				$sellingPrice = $row_itemSearch['retail_price'];
//				}else{
//					$sellingPrice = $row_itemSearch['intermediate_price'];
//					
//					}
//			}
$insertSQL = sprintf("INSERT INTO transfer_items (invoice_no,item_id,quantity_purchased,item_cost_price,item_unit_price,stockBalance) VALUES (%s, %s,1,%s,%s,%s)",
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"),
                       GetSQLValueString($row_itemSearch['item_id'], "int"),
					   GetSQLValueString($row_itemSearch['cost_price'], "int"),
					   GetSQLValueString(ceil($row_itemSearch['retail_price']), "double"),
					   GetSQLValueString($row_stockQty['stockBalance'], "double"));

  mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($insertSQL, $pos) or die(mysql_error());
  
  
  $suspendSQL = sprintf("INSERT INTO transfer (sale_time,employee_id,invoice_no,deleted,deleted_by,requesting_store) VALUES (now(),%s, %s,0,%s,%s)",
					   GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "int"),
					   GetSQLValueString($_SESSION['SESS_INVOICE'], "text"),
					   GetSQLValueString($_SESSION['SESS_MEMBER_ID'], "int"),
                       GetSQLValueString($_SESSION['location'], "int"));
					   
					    mysql_select_db($database_pos, $pos);
  $Result1 = mysql_query($suspendSQL, $pos) or die(mysql_error());
  
} else{
	
	
	
	$qtyAdded = ($row_itemSearchCheck['quantity_purchased']+1);
	
	$UpdateSalesQty = sprintf("UPDATE transfer_items SET quantity_purchased = %s WHERE
transfer_items.invoice_no = %s AND transfer_items.item_id = %s ", 
								GetSQLValueString($qtyAdded, "int"),
								GetSQLValueString($_SESSION['SESS_INVOICE'], "text"),
								GetSQLValueString($row_itemSearch['item_id'], "text"));
mysql_select_db($database_pos, $pos);
$Result1 = mysql_query($UpdateSalesQty, $pos) or die(mysql_error());
	
	//echo $qtyAdded ;
	//exit;
	
	}
}

	}
if (isset($_GET['id'])) {
$deleteSales = sprintf("DELETE FROM transfer_items WHERE sales_item_id = %s",GetSQLValueString($_GET['id'], "int"));
mysql_select_db($database_pos, $pos);
$Result1 = mysql_query($deleteSales, $pos) or die(mysql_error());

mysql_select_db($database_pos, $pos);
$query_inventoryCheck = sprintf("SELECT * FROM inventory where inventory.invoice_no = %s", GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
$inventoryCheck = mysql_query($query_inventoryCheck, $pos) or die(mysql_error());
$row_inventoryCheck = mysql_fetch_assoc($inventoryCheck);
$totalRows_inventoryCheck = mysql_num_rows($inventoryCheck);

if ($totalRows_inventoryCheck == 0){
	
	
	}else{
		
$deleteInventory = sprintf("DELETE FROM inventory WHERE invoice_no = %s",GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
mysql_select_db($database_pos, $pos);
$Result1 = mysql_query($deleteSales, $pos) or die(mysql_error());
	}

}  

if (isset($_POST['sales_item_id']) and isset($_POST['quantity'])) {
	
$UpdateSales = sprintf("UPDATE transfer_items SET quantity_purchased = %s WHERE sales_item_id = %s",GetSQLValueString($_POST['quantity'] > 1 ? $_POST['quantity'] : 1, "int"),
					GetSQLValueString($_POST['sales_item_id'], "int"));
mysql_select_db($database_pos, $pos);
$Result1 = mysql_query($UpdateSales, $pos) or die(mysql_error());	
	
	
}


mysql_select_db($database_pos, $pos);
$query_invoice = sprintf("SELECT
transfer_items.sales_item_id,
transfer_items.invoice_no,transfer_items.stockBalance,
transfer_items.sale_id,
transfer_items.item_id,
transfer_items.description,
transfer_items.serialnumber,
transfer_items.quantity_purchased,
transfer_items.item_cost_price,
transfer_items.item_unit_price,
transfer_items.discount_percent,
items.`name`
FROM
transfer_items
INNER JOIN items ON items.item_id = transfer_items.item_id
WHERE invoice_no =  %s order by sales_item_id desc", GetSQLValueString($col_invoice, "text"));
$invoice = mysql_query($query_invoice, $pos) or die(mysql_error());
$row_invoice = mysql_fetch_assoc($invoice);
$totalRows_invoice = mysql_num_rows($invoice);





$query_cartQty = sprintf("SELECT Sum(transfer_items.quantity_purchased) as cartqty FROM transfer_items WHERE invoice_no =  %s", GetSQLValueString($col_invoice, "text"));
$cartQty = mysql_query($query_cartQty, $pos) or die(mysql_error());
$row_cartQty = mysql_fetch_assoc($cartQty);
$totalRows_cartQty = mysql_num_rows($cartQty);

$query_totalInvoice = sprintf("SELECT
Sum(transfer_items.item_unit_price * transfer_items.quantity_purchased) as 'total'
FROM transfer_items WHERE invoice_no =  %s", GetSQLValueString($col_invoice, "text"));
$totalInvoice = mysql_query($query_totalInvoice, $pos) or die(mysql_error());
$row_totalInvoice = mysql_fetch_assoc($totalInvoice);
$totalRows_totalInvoice = mysql_num_rows($totalInvoice);


$query_payment = sprintf("SELECT transfer_payments.amount_tendered, transfer_payments.payment_id,transfer_payments.payment_type FROM transfer_payments WHERE invoice_no = %s", GetSQLValueString($col_invoice, "text"));
$payment = mysql_query($query_payment, $pos) or die(mysql_error());
$row_payment = mysql_fetch_assoc($payment);
$totalRows_payment = mysql_num_rows($payment);

$query_paymentSum = sprintf("SELECT Sum(transfer_payments.amount_tendered) as paymentsum FROM transfer_payments WHERE transfer_payments.invoice_no = %s 
GROUP BY transfer_payments.invoice_no", GetSQLValueString($col_invoice, "text"));
$paymentSum = mysql_query($query_paymentSum, $pos) or die(mysql_error());
$row_paymentSum = mysql_fetch_assoc($paymentSum);
$totalRows_paymentSum = mysql_num_rows($paymentSum);

$query_customerSales = sprintf("SELECT outlet.outletName,transfer.customer_id,outlet.description as 'name' FROM transfer INNER JOIN outlet ON outlet.outlet_id = transfer.customer_id
 WHERE transfer.invoice_no  = %s ", GetSQLValueString($_SESSION['SESS_INVOICE'], "text"));
$customerSales = mysql_query($query_customerSales, $pos) or die(mysql_error());
$row_customerSales = mysql_fetch_assoc($customerSales);
$totalRows_customerSales = mysql_num_rows($customerSales);



if (!isset($_SESSION['SESS_INVOICE']) or ($_SESSION['SESS_INVOICE'] == '')){
$_SESSION['SESS_INVOICE'] = 'TR-'.createRandomPassword();


}

?>

<script language="javascript" type="application/javascript">
    shortcut.add("F4", function() {
       //window.location = "new_employee.php";
	  $("#suspend_sale_button").click(function()
		{
			if (confirm("Are you sure you want to suspend this Request?"))
			{
									$("#register_container").load('requsitAdd.php?suspend=suspend'
);
							}
		});
	      }); 
		    
    shortcut.add("ctrl+d", function() {
        // Do something
		alert("ok");
    }); 
</script>



<div id="content-header" class="hidden-print sales_header_container">
	<h1 class="headigs"> <i class="icon fa fa-shopping-cart"></i>
		Requisition Register &nbsp;<?php echo $_SESSION['SESS_INVOICE'] ?><span id="ajax-loader"><img src="img/ajax-loader.gif" alt=""/></span></h1>
	
    
    
</div>

<div class="clear"></div>
	<!--Left small box-->
	<div class="row">
		<div class="sale_register_leftbox col-md-9">
			<div class="row forms-area">
										<div class="col-md-8 no-padd">
							<div class="input-append">
								<form action="requsitAdd.php" method="post" accept-charset
="utf-8" id="add_item_form" class="form-inline" autocomplete="off">								<input type="text" name="item"
 value="" id="item" class="input-xlarge" accesskey="i" placeholder="Enter item name or scan barcode"
  />								<a href="new_item.php" class="btn btn-primary
 none new_item_btn" title="New Item">New Item</a>								
								  <input name="code" type="hidden" id="code" value="<?php if (isset($error)){echo $error; }else {echo -1;} ?>" />
							  <a href="pendingRequest.php" class="btn btn-primary none suspended_sales_btn" title="Suspended Sales"
>
							  <div class='small_button'>Pending Request</div></a></form>
							</div>
						</div>					
												
				<div class="col-md-4 no-padd">
					
										
			</div>
	
			</div>
		
		<div class="row">
			
						<div class="table-responsive">
				<table id="register" class="table table-bordered">

					<thead>
						<tr>
							<th ></th>
							<th class="item_name_heading" >Item Name</th>
							<th class="sales_item sales_items_number">Item #</th>
							<th class="sales_stock">Stock</th>
							<th class="sales_price">Price</th>
							<th class="sales_quality">Qty.</th>
							<th >Total</th>
						</tr>
					</thead>
					<tbody id="cart_contents" class="sa">
												<?php if ($totalRows_invoice > 0) {	?>	<?php do { ?>
													    <tr id="reg_item_top" bgcolor="#eeeeee" >
														    <td><a href="requsitAdd.php?id=<?php echo $row_invoice['sales_item_id'] ?>" class="delete_item"><?php if((ceil($row_paymentSum['paymentsum'])) < (ceil($row_totalInvoice['total'])) and ($row_totalInvoice['total'] > 0)){ ?><i class="fa fa-trash-o fa fa-2x text-error"></i><?php } ?> </a></td>
														    <td class="text text-success"><?php echo $row_invoice['name']; ?></td>
														    <td class="text text-info sales_item" id="reg_item_number"><?php echo $row_invoice['item_id']; ?></td>
														    <td class="text text-warning sales_stock" id="reg_item_stock" ><?php echo number_format($row_invoice['stockBalance'],2); ?></td>
														    
														    <td>
														      <form action="#" method="post"
 accept-charset="utf-8" class="line_item_form" autocomplete="off"><input type="text" name="price" readonly="readonly" value
="<?php echo number_format(ceil($row_invoice['item_unit_price']),2); ?>" class="input-small" id="price_1"  />										 
													          </form>
														      
													        </td>
														    
		    											  <td id="reg_item_qty">
												<form action="requsitAdd.php" method="post" accept-charset="utf-8" class="line_item_form" autocomplete="off"><input type="text" name="quantity" value="<?php echo number_format($row_invoice['quantity_purchased'],2); ?>" class="input-small" id="quantity" accesskey="q" />	<input name="sales_item_id" type="hidden" id="sales_item_id" value="<?php echo $row_invoice['sales_item_id'] ?>" />												</form>
												</td>
														    
														    <td><span class="text text-main"><?php $total = ceil($row_invoice['item_unit_price'] * $row_invoice['quantity_purchased']); echo number_format($total,2) ?></span></td>
														    
														    
														    
													    </tr>
														  <?php } while ($row_invoice = mysql_fetch_assoc($invoice)); ?><?php } // Show if recordset not empty ?>

								<tr id="reg_item_bottom">
									<td >Desc:</td>
									<td  colspan="4" class="edit_discription">
										<form action="http://www.optimumlinkup.com.ng/pos/index.php/sales/edit_item/1" method="post"
 accept-charset="utf-8" class="line_item_form" autocomplete="off">None
<input type="hidden" name="description" value="" />
										</form>
									</td>
									<td >
										
										Serial:									</td>
									<td colspan="2">
										<form action="#" method="post"
 accept-charset="utf-8" class="line_item_form" autocomplete="off"><input type="text" name="serialnumber"
 value="" class="serial_item" size="20" id="serialnumber_1"  />										</form>
									</td>
								</tr>
                              <?php if ($totalRows_invoice == 0) {	?>  <tbody id="cart_contents" class="sa">
												<tr class="cart_content_area">
							<td colspan="7">
								<div class="text-center text-warning"> <h3>There are no items in the cart</h3></div>
							</td>
						</tr>
										</tbody> <?php } ?>

								
											</tbody>
			</table>
			</div>
						<ul class="list-inline pull-left">
								
										
			</ul>
											
						</div>
						
						
							

					</div>
					<!-- Right small box  -->
				<div class="col-md-3 sale_register_rightbox">
					<ul class="list-group">
						<li class="list-group-item nopadding">
							<!-- Cancel and suspend buttons -->
					<?php if ($totalRows_invoice > 0) {	?>		<div class='sale_form_main'>
																<input type
="button" class="btn btn-danger button_dangers" id="suspend_reset" value="Reset Request" accesskey="s" /> 
                            <form action="requsitAdd.php" method
="post" accept-charset="utf-8" id="cancel_sale_form" autocomplete="off">																	<?php if($row_requestButtonSql['request_from'] == $_SESSION['location']) { ?><input type
="button" class="btn btn-warning warning-buttons" id="suspend_sale_button" value="Request Issue" accesskey="s" /><?php }?>
																<input type="button" class="btn btn-danger button_dangers" id="cancel_sale_button" value
="Cancel Issue" accesskey="c" />
							                                    <input name="suspend" type="hidden" id="suspend" value="suspend" />
							                                    <input name="cancel" type="hidden" id="cancel" value="cancel" />
							  </form>
													</div> <?php } ?>
					</li>
					<li class="list-group-item item_tier">
						<!-- Customer info starts here-->
						<?php if ($totalRows_customerSales == 0) { // Show if recordset not empty ?>		<h5 class="customer-basic-information">Select Office</h5><?php }?>
						<div class="row nomargin">
						<div class="clearfix" id="customer_info_shell">
										 <?php if ($totalRows_customerSales == 0) { // Show if recordset not empty ?>			
                            <form action="requsitAdd.php" method
="post" accept-charset="utf-8" id="select_customer_form" autocomplete="off">						<input type="text"
 name="customer" value="Type outlet name..." id="customer" size="30" placeholder="Type outlet name
..." accesskey="c"  />					</form>
					<div id="add_customer_info">
							<div id="common_or" class="common_or">
								OR								<a href="new_customer.php" class="btn
 btn-primary none" title="New Customer" id="new-customer">
								<div class='small_button'> <span>New Office
</span> </div></a>							</div>
					</div> <?php } ?>
                   <input type="hidden" name="customername" id="customername" value="<?php if ($totalRows_customerSales > 0 ){echo $row_customerSales['name'];} ?>" /> 
                    <?php if ($totalRows_customerSales > 0 ){$_SESSION['request_to'] = $row_customerSales['customer_id'];} ?>        
				   <?php if ($totalRows_customerSales > 0) { // Show if recordset not empty ?>
  <div id="customer-info" class=" full_width_imporant">
    <div class="clear">
      <!-- Customer info starts here--><ul class="list-unstyled">
        <li><strong><?php echo $row_customerSales['name']; ?></strong>
          
        </li>									
        </ul>
      </div>
    <a href="requsitAdd.php?delete_customer=delete_customer" id="delete_customer" class="btn-sm btn-warning">Detach</a>							</div>
  <?php } // Show if recordset not empty ?>
<div class="tiers_main clear"><h3 class="items_tiers"></h3><div class="clear"></div></div>					</div>
				</div>
				</li>
				<li class="list-group-item spacing">
				</li>
				<li class="list-group-item nopadding">

					<div id='sale_details'>
						<table id="sales_items" class="table">
							<tr class="warning">
								<td class="left">Items In Cart:</td>
								<td class="right"><?php echo number_format($row_cartQty['cartqty'],2) ;//$totalRows_invoice ; ?></td>
							</tr>
														<tr class="success">
								<td ><h3 class="sales_totals">Total:</h3></td>
								<td ><h3 class="currency_totals"><?php echo number_format(ceil($row_totalInvoice['total']),2); ?></h3></td>
							</tr>
						</table>
					</div>
				</li>
				<?php if ($row_cartQty['cartqty'] > 0){?><li class="list-group-item spacing">
				</li>

				<li class="list-group-item nopadding">
					
					<div id="Payment_Types">

						
							<table id="amount_due" class="table">
								<tr class="error">
									<td>
										<h4 class="sales_amount_due">Amount Due:</h4>
									</td>
									<td>
										<h3 class="amount_dues"><span class="currency_totals">
										  <?php echo number_format(ceil($row_totalInvoice['total']),2); ?>
										</span></h3>
									</td>
								</tr>
							</table>

							<div id="make_payment">
								<form action="requsitAdd.php" method="post"
 accept-charset="utf-8" id="add_payment_form" autocomplete="off">								
							<?php if ($totalRows_payment > 0) {	?>  <table class="table" id="register2">
								    <thead>
								      <tr>
								        <th id="pt_delete"></th>
								        <th id="pt_type" align="left">Type</th>
								        <th id="pt_amount" align="left">Amount</th>
							          </tr>
							        </thead>
								    <tbody id="payment_contents">
								      <?php do { ?><tr class="warning">
								        <td id="pt_delete"><a href="requsitAdd.php?pid=<?php echo $row_payment['payment_id']?>&delete=delete" class="delete_payment">[Delete]</a></td>
								        <td id="pt_type"><?php echo $row_payment['payment_type'];?> </td>
								        <td id="pt_amount"><?php echo number_format($row_payment['amount_tendered'],2);?> </td>
							          </tr><?php } while ($row_payment = mysql_fetch_assoc($payment)); ?>
							        </tbody>
							      </table><?php } ?>
								  
								  <table id="make_payment_table"
 class="table">
									<tr id="mpt_top">
										<td id="add_payment_text">
											Add Payment:
										</td>
										<td>
											<select name="payment_type" id="payment_types" class="input-medium">
<option value="Cash" selected="selected">Cash</option>
<option value="Check">Check</option>
<option value="Credit Card">Credit Card</option>
<option value="Cheque">Cheque</option>
<option value="Transfer">Transfer</option>
                                      </select>										</td>
									</tr>
									<tr id="mpt_bottom" >
										<td id="tender" colspan="2">
											<div class="input-append">
												<input type="text" name="amount_tendered" value="<?php echo number_format(ceil($row_totalInvoice['total']),2,'.',''); ?>" id="amount_tendered" class="input-medium
 input_mediums" accesskey="p"  />
												<input name="amount_due" type="hidden" id="amount_due" value="<?php echo number_format(ceil($row_totalInvoice['total']),2,'.',''); ?>" />
<input type="button" class="btn btn-primary" id="add_payment_button"
 value="Add Payment" accesskey="a" /><?php }?>
											</div>

										</td>
									</tr>
									 
								</table>

							</form>
						</div>
					</div>
				</li>
				<li class="list-group-item">
		<?php if ($row_totalInvoice['total'] != 0) {	?>	  
 <form action="transferReceipt.php" method="post" accept-charset
="utf-8" id="finish_sale_form" autocomplete="off">						<?php if($row_customerSales['customer_id'] == $_SESSION['location']) {?><input type='button' class='btn btn-success btn-large btn-block' id='finish_sale_button' value='Finish' /><?php }?>					</div>
   <input name="invoice" type="hidden" id="invoice" value="<?php echo $_SESSION['SESS_INVOICE']?>" />
				</form> <?php }?>
 					
 </li>
		</ul>

		</div>
</div>
<script type="text/javascript">
	// gritter("Warning","Warning, Desired Quantity is Insufficient. You can still process the sale, but check
// your inventory",'gritter-item-warning',false,false);</script>

<script type="text/javascript" language="javascript">

    var submitting = false;
	$(document).ready(function()
	{
		//Here just in case the loader doesn't go away for some reason
        
       
		$("#ajax-loader").hide();
		
		if (last_focused_id && last_focused_id != 'item' && $('#'+last_focused_id).is('input[type=text]'))

		{
 			$('#'+last_focused_id).focus();
			$('#'+last_focused_id).select();
		}
		
		$(document).focusin(function(event) 
		{
			last_focused_id = $(event.target).attr('id');
		});

		$('#mode_form, #select_customer_form, #add_payment_form, .line_item_form, #discount_all_form').ajaxForm
({target: "#register_container", beforeSubmit: salesBeforeSubmit});
		$('#add_item_form').ajaxForm({target: "#register_container", beforeSubmit: salesBeforeSubmit, success
: itemScannedSuccess});
		$("#cart_contents input").change(function()
		{
			$(this.form).ajaxSubmit({target: "#register_container", beforeSubmit: salesBeforeSubmit});
		});

		$( "#item" ).autocomplete({
			source: 'requisiteSearch.php',
			type: 'GET',
			delay: 10,
			autoFocus: false,
			minLength: 1,
			select: function(event, ui)
			{
				event.preventDefault();
				$( "#item" ).val(ui.item.value);
				$('#add_item_form').ajaxSubmit({target: "#register_container", beforeSubmit: salesBeforeSubmit, success
: itemScannedSuccess});
			}
		});

		$('#item,#customer').click(function()
		{
			$(this).attr('value','');
		});

		$( "#customer" ).autocomplete({
			source: 'outletSearch.php',
			delay: 10,
			autoFocus: false,
			minLength: 1,
			select: function(event, ui)
			{
				$("#customer").val(ui.item.value);
				$('#select_customer_form').ajaxSubmit({target: "#register_container", beforeSubmit: salesBeforeSubmit
});
			}
		});

		$('#customer').blur(function()
		{
			$(this).attr('value',"Type Office name...");
		});
		
		$('#item').blur(function()
		{
			$(this).attr('value',"Enter item name or scan barcode");
		});
		
		//Datepicker change
		$('#change_sale_date_picker').datepicker().on('changeDate', function(ev) {
			$.post('http://www.optimumlinkup.com.ng/pos/index.php/sales/set_change_sale_date', {change_sale_date
: $('#change_sale_date').val()});			
		});
		
		//Input change
		$("#change_sale_date").change(function(){
			$.post('http://www.optimumlinkup.com.ng/pos/index.php/sales/set_change_sale_date', {change_sale_date
: $('#change_sale_date').val()});			
		});

		$('#change_sale_date_enable').change(function() 
		{
			$.post('http://www.optimumlinkup.com.ng/pos/index.php/sales/set_change_sale_date_enable', {change_sale_date_enable
: $('#change_sale_date_enable').is(':checked') ? '1' : '0'});
		});

		$('#comment').change(function() 
		{
			$.post('#', {comment: $('#comment')
.val()});
		});
						
		$('#show_comment_on_receipt').change(function() 
		{
			$.post('#', {show_comment_on_receipt
:$('#show_comment_on_receipt').is(':checked') ? '1' : '0'});
		});

		$('#email_receipt').change(function() 
		{	
			$.post('#', {email_receipt: $
('#email_receipt').is(':checked') ? '1' : '0'});
		});

		$('#save_credit_card_info').change(function() 
		{
			$.post('#', {save_credit_card_info
:$('#save_credit_card_info').is(':checked') ? '1' : '0'});
		});

		$('#change_sale_date_enable').is(':checked') ? $("#change_sale_input").show() : $("#change_sale_input"
).hide(); 

		$('#change_sale_date_enable').click(function() {
			if( $(this).is(':checked')) {
				$("#change_sale_input").show();
			} else {
				$("#change_sale_input").hide();
			}
		});

		$('#use_saved_cc_info').change(function() 
		{
			$.post('#', {use_saved_cc_info
:$('#use_saved_cc_info').is(':checked') ? '1' : '0'});
		});

		$("#finish_sale_button").click(function()
		{
			//Prevent double submission of form
				if($("#customername").val()== ''){
						gritter("Error",'Attache Office','gritter-item-error', false,true);
						
						setTimeout(function(){
				
							$.gritter.removeAll();
							return false;
				
						},1000);
						return false;
						$("#customername").focus();
						
														}		
			
			
			$("#finish_sale_button").hide();
			$("#register_container").mask("Please wait...");
			
							
				if (!confirm("Are you sure you want to confirm Issue?"))
				{
					//Bring back submit and unmask if fail to confirm
					
					
					$("#finish_sale_button").show();
					$("#register_container").unmask();
					
					return;
				}
				
																		
					if ($("#comment").val())
					{
						$.post('#', {comment: $('#comment'
).val()}, function()
						{
							//return false;
							$('#finish_sale_form').submit();						
					
					});						
					}
					else
					{
						//return false;
						$('#finish_sale_form').submit();						
					}
					
									
				
				
				});

		$("#suspend_sale_button").click(function()
		{
            if ($("#customername").val() == ''){
                
                alert('Please make your requistion to Store')
            }else{
			if (confirm("Are you sure you want to Requisite for these items?"))
			{
				
                $("#register_container").load('requsitAdd.php?suspend=suspend'
);
							}
            }
		});

		$("#cancel_sale_button").click(function()
		{
			if (confirm("Are you sure you want to clear this sale? All items will cleared."))
			{
				$('#cancel_sale_form').ajaxSubmit({target: "#register_container", beforeSubmit: salesBeforeSubmit
});
			}
		});

		$("#add_payment_button").click(function()
		{
			$('#add_payment_form').ajaxSubmit({target: "#register_container", beforeSubmit: salesBeforeSubmit
});
		});

		$("#payment_types").change(checkPaymentTypeGiftcard).ready(checkPaymentTypeGiftcard);
		$('#mode').change(function()
		{
			if ($(this).val() == "store_account_payment") { // Hiding the category grid
				$('#show_hide_grid_wrapper, #category_item_selection_wrapper').fadeOut();
			}else { // otherwise, show the categories grid
				$('#show_hide_grid_wrapper, #show_grid').fadeIn();
				$('#hide_grid').fadeOut();
			}
			$('#mode_form').ajaxSubmit({target: "#register_container", beforeSubmit: salesBeforeSubmit});
		});

		$('.delete_item, .delete_payment, #delete_customer').click(function(event)
		{
			event.preventDefault();
			$("#register_container").load($(this).attr('href'));	
		});

		$("#tier_id").change(function()
		{
			$.post('http://www.optimumlinkup.com.ng/pos/index.php/sales/set_tier_id', {tier_id: $(this).val()
}, function()
			{
				$("#register_container").load('http://www.optimumlinkup.com.ng/pos/index.php/sales/reload');
			});
		});

		$("input[type=text]").not(".description").click(function() {
			$(this).select();
		});
		
		//alert(screen.width);
		if(screen.width <= 768) //set the colspan on page load
		{ 
			jQuery('td.edit_discription').attr('colspan', '2');
		}
		
		 $(window).resize(function() {
			var wi = $(window).width();
	 
			if (wi <= 768){
				jQuery('td.edit_discription').attr('colspan', '2');
			}
			else {
				jQuery('td.edit_discription').attr('colspan', '4');
			}
		});     
			
		$("#new-customer").click(function()
		{
			$("body").mask("Please wait...");			
		});
	});
 
function checkPaymentTypeGiftcard()
{
	if ($("#payment_types").val() == "Gift Card")
	{
		$("#amount_tendered").val('');
		$("#amount_tendered").focus();
		giftcard_swipe_field($("#amount_tendered"));
	}
}

function salesBeforeSubmit(formData, jqForm, options)
{
	if (submitting)
	{
		return false;
	}
	submitting = true;
	$("#ajax-loader").show();
	$("#add_payment_button").hide();
	$("#finish_sale_button").hide();
}

function itemScannedSuccess(responseText, statusText, xhr, $form)
{
	
	if(($('#code').val())== 1){
		gritter("Error",'Item not Found','gritter-item-error', false,true);
		
		}else{
		gritter("Success","Item Addedd Successfully",'gritter-item-success',false,true)
		}
	setTimeout(function(){$('#item').focus();}, 10);
	
	setTimeout(function(){

			$.gritter.removeAll();
			return false;

		},1000);
	
}



</script>
<?php
mysql_free_result($itemSearch);

mysql_free_result($invoice);
?>
