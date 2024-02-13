<?php
//index.php

$error = '';
$name = '';
$from = '';
$subject = '';
$message = '';
$to ='';

function clean_text($string)
{
	$string = trim($string);
	$string = stripslashes($string);
	$string = htmlspecialchars($string);
	return $string;
}

if(isset($_POST["submit"]))
{
	if(empty($_POST["name"]))
	{
		$error .= '<p><label class="text-danger">Please Enter your Name</label></p>';
	}
	else
	{
		$name = clean_text($_POST["name"]);
		if(!preg_match("/^[a-zA-Z ]*$/",$name))
		{
			$error .= '<p><label class="text-danger">Only letters and white space allowed</label></p>';
		}
	}
	if(empty($_POST["from"]))
	{
		$error .= '<p><label class="text-danger">Please Enter your Email</label></p>';
	}
	else
	{
		$from = clean_text($_POST["from"]);
		if(!filter_var($from, FILTER_VALIDATE_EMAIL))
		{
			$error .= '<p><label class="text-danger">Invalid email format</label></p>';
		}
	}
	if(empty($_POST["to"]))
	{
		$error .= '<p><label class="text-danger">Please Enter To Email</label></p>';
	}
	else
	{
		$to = clean_text($_POST["to"]);
		if(!filter_var($to, FILTER_VALIDATE_EMAIL))
		{
			$error .= '<p><label class="text-danger">Invalid email format</label></p>';
		}
	}
	if(empty($_POST["subject"]))
	{
		$error .= '<p><label class="text-danger">Subject is required</label></p>';
	}
	else
	{
		$subject = clean_text($_POST["subject"]);
	}
	if(empty($_POST["message"]))
	{
		$error .= '<p><label class="text-danger">Message is required</label></p>';
	}
	else
	{
		$message = clean_text($_POST["message"]);
	}
	if($error == '')
	{
		require 'class/class.phpmailer.php';
		$mail = new PHPMailer;
		$mail->IsSMTP();								//Sets Mailer to send message using SMTP
		$mail->Host = 'smtp.gmail.com';		//Sets the SMTP hosts of your Email hosting, this for Godaddy
		$mail->Port = '587';								//Sets the default SMTP server port
		$mail->SMTPAuth = true;							//Sets SMTP authentication. Utilizes the Username and Password variables
		$mail->Username = 'bankole.adesoji@gmail.com';					//Sets SMTP username
		$mail->Password = 'Banzoo@7980';					//Sets SMTP password
		$mail->SMTPSecure = 'tls';							//Sets connection prefix. Options are "", "ssl" or "tls"
		$mail->From = $_POST["from"];					//Sets the From email address for the message
		$mail->FromName = $_POST["name"];				//Sets the From name of the message
		$mail->AddAddress($_POST["to"], 'Bankole');		//Adds a "To" address
		$mail->AddCC($_POST["from"], $_POST["name"]);	//Adds a "Cc" address
		$mail->WordWrap = 50;							//Sets word wrapping on the body of the message to a given number of characters
		$mail->AddAttachment($_FILES['attachFile']['tmp_name'], $_FILES['attachFile']['name']);
		$mail->IsHTML(true);							//Sets message type to HTML				
		$mail->Subject = $_POST["subject"];				//Sets the Subject of the message
		$mail->Body = $_POST["message"];				//An HTML or plain text message body
		if($mail->Send())								//Send an Email. Return true on success or false on error
		{
			$error = '<label class="text-success">Thank you for contacting us</label>';
		}
		else
		{
			$error = '<label class="text-danger">There is an Error</label>';
		}
		$name = '';
		$from = '';
		$subject = '';
		$message = '';
		$to ='';
		
		
	}
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Send an Email on Form Submission using PHP with PHPMailer</title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
	</head>
	<body>
		<br />
		<div class="container">
			<div class="row">
				<div class="col-md-8" style="margin:0 auto; float:none;">
					<h3 align="center">Send an Email on Form Submission using PHP with PHPMailer</h3>
					<br />
					<?php echo $error; ?>
					<form method="post" enctype="multipart/form-data">
						<div class="form-group">
							<label>Enter Name</label>
							<input type="text" name="name" placeholder="Enter Name" class="form-control" value="<?php echo $name; ?>" />
						</div>
						<div class="form-group">
							<label>To</label>
							<input type="text" name="to" class="form-control" placeholder="Enter Email To" value="<?php echo $to; ?>" />
						</div>
						<div class="form-group">
							<label>From Mail</label>
							<input type="text" name="from" class="form-control" placeholder="Enter Email" value="<?php echo $from; ?>" />
						</div>
						<div class="form-group">
							<label>Enter Subject</label>
							<input type="text" name="subject" class="form-control" placeholder="Enter Subject" value="<?php echo $subject; ?>" />
						</div>
						<div class="form-group">
							<label>Enter Message</label>
							<textarea name="message" class="form-control" placeholder="Enter Message"><?php echo $message; ?></textarea>
						</div>
						<div class="form-group">
				  <label class="control-label col-sm-2" for="lname">Attach File:</label>
				            
					<input type="file" class="form-control" id="attachFile" name="attachFile">
				  
				</div>
						<div class="form-group" align="center">
							<input type="submit" name="submit" value="Submit" class="btn btn-info" />
						</div>
					</form>
				</div>
			</div>
		</div>
	</body>
</html>





