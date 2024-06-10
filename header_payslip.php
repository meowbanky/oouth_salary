<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo $_SESSION['BUSINESSNAME']; ?> Salary Manager</title>
		
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
		<!--<base href="http://www.optimumlinkup.com.ng/pos/">--><base href=".">
		<link rel="icon" href="favicon.ico" type="image/x-icon">
		
					<link href="css/bootstrap.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/jquery.gritter.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/jquery-ui.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/unicorn.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
         <link href="css/datepicker.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/bootstrap-select.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/select2.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					
					<link href="css/font-awesome.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
        
					<link href="css/jquery.loadmask.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
					<link href="css/token-input-facebook.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
          <link href="css/dataTables.tableTools.min.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
          <link href="css/components-md.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
                    
<link href="css/dataTables.tableTools.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
				<script type="text/javascript">
			
		</script>
		
					<script src="js/all.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
					<script src="js/select2.js" type="text/javascript" language="javascript" charset="UTF-8"></script>
    			<script src="js/jquery.tabledit.min.js" type="text/javascript" language="javascript" charset="UTF-8"></script>

<script src="js/jquery.dataTables.min.js" type="text/javascript" language="javascript" charset="UTF-8"></script>

<link rel="stylesheet" type="text/css" href="datatable/datatables.min.css"/>
<script type="text/javascript" src="js/home.js"></script> 
<script type="text/javascript" src="datatable/pdfmake.min.js"></script>
<script type="text/javascript" src="datatable/pdfmake-0.1.36/vfs_fonts.js"></script>
<script type="text/javascript" src="datatable/datatables.min.js"></script>
<link href="css/custom.css" rel="stylesheet" rev="stylesheet" type="text/css" media="all">
			
		
		<script type="text/javascript">
			COMMON_SUCCESS = "Success";
			COMMON_ERROR = "Error";
			$.ajaxSetup ({
				cache: false,
				headers: { "cache-control": "no-cache" }
			});
			
			$(document).ready(function()
			{
				//Ajax submit current location
				$("#employee_current_location_id").change(function()
				{
					$("#form_set_employee_current_location_id").ajaxSubmit(function()
					{
						window.location.reload(true);
					});
				});	
				
				var is_session_expired = 'no';
       		 function check_session()
       		 {
       		 	$.ajax({
       		 		url:"check_session.php",
       		 		method: "POST",
       		 		success: function(data){
       		 			if(data=='1'){
       		 				$('#loginModal').modal({
       		 					backdrop: 'static',
       		 					keyboard: false,
       		 				});
       		 				is_session_expired = 'yes';
       		 				
       		 			}
       		 		}
       		 		
       		 	})
       		 }
       		 var count_interval = setInterval(function(){
       		 	check_session();
       		 	if(is_session_expired=='yes'){
       		 		clearInterval(count_interval);
       		 	}
       		 },10000);
       		 
$('#loginform').on('submit',function (event) {
	event.preventDefault();
	doLoginExpire();
		});
		
		
		setTimeout(function(){ $('.alert').animate({height:"hide", opacity:"hide"}); $('.alert').text(''); }, 3000);
            
                //If we have an empty username focus
                if ($("#username").val() == '')
                {
                    $("#username").focus();
                } else if ($("#password").val() == '')
                {
                    $("#password").focus();
                } 
            
		
			});
			
		</script>
	
    <script>

                    var isNS4=(navigator.appName=="Netscape")?1:0;

                    function auto_logout(iSessionTimeout,iSessTimeOut,sessiontimeout)

                    {

                             window.setTimeout('', iSessionTimeout);

                              window.setTimeout('winClose()', iSessTimeOut);

                    }

                    function winClose() {

                        //alert("Your Application session is expired.");

                   if(!isNS4)

	           {

		          window.navigate("logout.php");
        
		         

	           }

                  else

	          {
	          	

		        window.location="logout.php";
	       

	           }

             }

           // auto_logout(1440000,1500000,1500)
       
       
</script>	

	<style>@font-face{font-family:uc-nexus-iconfont;src:url(chrome-extension://pogijhnlcfmcppgimcaccdkmbedjkmhi/res/font_1471832554_080215.woff) format('woff'),url(chrome-extension://pogijhnlcfmcppgimcaccdkmbedjkmhi/res/font_1471832554_080215.ttf) format('truetype')}
    
        .modal .modal-title { font-weight: 400;color: #FFF; text-transform: uppercase;text-align: center; font-size: 100%;}
.modal .modal-header{ background: #6e7dc7;}
#box{
	width:500px;
	background-color:#fffff;
	margin:	0 auto;
	padding:16px;
	text-align:center;
	margin-top:50px;
	border:1px solid #cc;
	border-radius:5px	
	
}
.modal-backdrop{
	opacity: 0.65;
	filter:alpha(opacity=65);
}
    </style>
    
    </head>
    
   															