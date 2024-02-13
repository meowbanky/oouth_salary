<?php 
$i=0;
do{
ob_start();
echo 'a';
echo $i;

ob_end_clean();
echo 'b';

$i++;
}while ($i<10)
 	  // code to execute endwhile;
?>