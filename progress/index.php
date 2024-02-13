<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8" />
	<title>sleepyTime</title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge;chrome=1" />
	<script src="http://code.jquery.com/jquery-2.1.0.js"></script>
	<script>
		$.ajax({
			url: "sleepy.php",
			xhrFields: {
				onprogress: function(e) {
					console.log(e.target.responseText)
					$("#information").html(e.target.responseText)
					if (e.lengthComputable) {
						console.log(e.loaded / e.total * 100 + '%');
					}
				}
			},
			success: function(text) {
					console.log(text)
					$("body").html("<h1>done!</h1>")
			}
		});
	</script>
</head>
<body>
<div id="progress" style="width:500px;border:1px solid #ccc;"></div> 
<div id="information" style="width" ><p align="center"></p> </div>
</body>
</html>
