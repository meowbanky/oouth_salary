<!-- Display submission status -->
<?php if(!empty($statusMsg)){ ?>
    <p class="statusMsg <?php echo !empty($msgClass)?$msgClass:''; ?>"><?php echo $statusMsg; ?></p>
<?php } ?>

<!-- Display contact form -->
<form method="post" action="sendmail.php" enctype="multipart/form-data">
    <div class="form-group">
        <input type="text" name="name" class="form-control" value="<?php echo !empty($postData['name'])?$postData['name']:''; ?>" placeholder="Name" required="">
    </div>
    <div class="form-group">
        <input type="email" name="email" class="form-control" value="<?php echo !empty($postData['email'])?$postData['email']:''; ?>" placeholder="Email address" required="">
    </div>
    <div class="form-group">
        <input type="text" name="subject" class="form-control" value="<?php echo !empty($postData['subject'])?$postData['subject']:''; ?>" placeholder="Subject" required="">
    </div>
    <div class="form-group">
        <textarea name="message" class="form-control" placeholder="Write your message here" required=""><?php echo !empty($postData['message'])?$postData['message']:''; ?></textarea>
    </div>
    <div class="form-group">
        <input type="file" name="attachment" class="form-control">
    </div>
    <div class="submit">
        <input type="submit" name="submit" class="btn" value="SUBMIT">
    </div>
</form>