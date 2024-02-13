<?php
require 'PHPMailer/PHPMailerAutoload.php';
if($_POST['email']) {
	$name = $_POST['uname'];
	$toEmail = $_POST['email'];
	$message = $_POST['message'];
	try {
		$mail = new PHPMailer;
		$mail->AddAddress($toEmail);
		$mail->From = "bankole.abiodun@oouth.com";
		$mail->Subject = "Test Email with attachment";
		$mail->isSMTP();
		$mail->Host = 'smtp.gmail.com';
		$mail->SMTPAuth = true;
		$mail->Username = 'bankole.adesoji@gmail.com';
		$mail->Password = "Banzoo@7980" ;
		$mail->SMTPSecure = "tls";
		$mail->Port = '587';
		$body = "<table>
			<tr>
			<th colspan='2'>This is a test email with attachment</th>
			</tr>
			<tr>
			<td>Name :</td>
			<td>".$name."</td>
			</tr>			
			<tr>
			<td>Message : </td>
			<td>".$message."</td>
			</tr>
			<table>";
			$body = preg_replace('/\\\\/','', $body);
			$mail->MsgHTML($body);
			$mail->IsSendmail();
			$mail->AddReplyTo("admin@webdamn.com");
			$mail->AltBody = "To view the message, please use an HTML compatible email viewer!";
			$mail->WordWrap = 80;
			$mail->AddAttachment($_FILES['attachFile']['tmp_name'], $_FILES['attachFile']['name']);
			$mail->IsHTML(true);
			$mail->Send();
			header("Location: index.php?success=1");
	} catch (Exception $e) {
		echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
	}
}
?>