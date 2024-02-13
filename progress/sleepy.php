<?php
ini_set('max_execution_time','0');
function time_elapsed_A($secs){
    $bit = array(
        'y' => $secs / 31556926 % 12,
        'w' => $secs / 604800 % 52,
        'd' => $secs / 86400 % 7,
        'h' => $secs / 3600 % 24,
        'm' => $secs / 60 % 60,
        's' => $secs % 60
        );
     $ret="";
    foreach($bit as $k => $v)
        if($v > 0)$ret = $ret. $v . $k." ";
        
    return   $ret;
}
    
$oldtime = time();

	sleep(5);
	$nowtime = time();
	//echo "<p>".time_elapsed_A($nowtime-$oldtime)."</p>";
	echo '<div style="width:20%;background-color:#ddd; background-image:url(pbar-ani.gif)" align="center">20%</div>';
	
	flush();
	ob_flush();

	sleep(5);
	$nowtime = time();
	echo "<p>".time_elapsed_A($nowtime-$oldtime)."</p>";
	//echo '<div style="width:50%;background-color:#ddd; background-image:url(pbar-ani.gif)" align="center">50%</div>';
	
	flush();
	ob_flush();

	sleep(5);
	$nowtime = time();
	echo "<p>".time_elapsed_A($nowtime-$oldtime)."</p>";
	//echo '<div style="width:70%;background-color:#ddd; background-image:url(pbar-ani.gif)" align="center">70%</div>';
	
	flush();
	ob_flush();

	sleep(5);
	$nowtime = time();
	echo "<p>".time_elapsed_A($nowtime-$oldtime)."</p>";
	//echo '<div style="width:90%;background-color:#ddd; background-image:url(pbar-ani.gif)" align="center">90%</div>';
	
	flush();
	ob_end_flush();

	sleep(5);
	$nowtime = time();
	echo "<p>".time_elapsed_A($nowtime-$oldtime)."</p>";
	//echo '<div style="width:100%;background-color:#ddd; background-image:url(pbar-ani.gif)" align="center">100%</div>';
	
	flush();
	ob_flush();
?>
