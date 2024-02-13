<?php 
session_start();
if(isset($_SESSION['SESS_MEMBER_ID']))
{
	echo '0';
	}
	else
	{
		echo '1';
	}
?>