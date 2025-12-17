<?php
require 'App.php';

$App = new App;
$employees = $App->getEmployeeDetails();
$users = $App->getUsersDetails();


?>