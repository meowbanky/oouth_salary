<?php include('inc/header.php');?>
<title>webdamn.com : Demo Send Email with Attachment using PHP</title>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/jquery.bootstrapvalidator/0.5.2/js/bootstrapValidator.min.js"></script>
<script src="js/validation.js"></script>
<link rel="stylesheet" href="css/style.css">
<?php include('inc/container.php');?>
<div class="container contact">	
	<div class="row">		
		<div class="col-md-9">
		<h2>Send Email with Attachment using PHP</h2>
		 <form action="mail.php" method="post" id="emailForm" enctype="multipart/form-data">
			<div class="contact-form">
				<?php if(!empty($_GET['success']) && $_GET['success']) { ?>
					<div id="message" class="alert alert-danger alert-dismissible fade show">The message has been sent.</div>
				<?php } ?>
				<div class="form-group">				  
				  <label class="control-label col-sm-2" for="fname">Name*:</label>
				  <div class="col-sm-10">          
					<input type="text" class="form-control" id="uname" name="uname" placeholder="Enter Name" >
				  </div>
				</div>				
				<div class="form-group">
				  <label class="control-label col-sm-2" for="email">Email*:</label>
				  <div class="col-sm-10">
					<input type="email" class="form-control" id="email" name="email" placeholder="Enter email" >
				  </div>
				</div>
				<div class="form-group">
				  <label class="control-label col-sm-2" for="lname">Attach File:</label>
				  <div class="col-sm-10">          
					<input type="file" class="form-control" id="attachFile" name="attachFile">
				  </div>
				</div>
				<div class="form-group">
				  <label class="control-label col-sm-2" for="comment">Message*:</label>
				  <div class="col-sm-10">
					<textarea class="form-control" rows="5" name="message" id="message"></textarea>
				  </div>
				</div>
				<div class="form-group">        
				  <div class="col-sm-offset-2 col-sm-10">
					<button type="submit" class="btn btn-default" name="sendEmail">Send Email</button>
				  </div>
				</div>
			</div>
			</form>
		</div>		
	</div>
</div>	
<?php include('inc/footer.php');?>