<?php 
session_start();
$deductoncode = $_POST['deductoncode'];

$_SESSION['deductoncode'] = $deductoncode;
