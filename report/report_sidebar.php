 <div id="sidebar" class="hidden-print minibar sales_minibar">

 	<ul style="display: block;"><?php $currentPage = (substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1));
									?>

 		<li <?php if ($currentPage == 'home.php') { ?> class="active" <?php } ?>><a href="../home.php"><i class="icon fa fa-dashboard"></i><span class="hidden-minibar">Dashboard</span></a></li>
 		<?php if ($_SESSION['role'] == 'Admin') { ?>

 			<li <?php if ($currentPage == 'employee.php') { ?> class="active" <?php } ?>><a href="../employee.php"><i class="fa fa-user"></i><span class="hidden-minibar">Employees</span></a></li>
 			<li <?php if ($currentPage == 'empearnings.php') { ?> class="active" <?php } ?>><a href="../empearnings.php"><i class="fa fa-credit-card"></i><span class="hidden-minibar">Earning/Deduction</span></a></li>

 			<li <?php if ($currentPage == 'tax.php') { ?> class="active" <?php } ?>><a href="../tax.php"><i class="fa fa-money"></i><span class="hidden-minibar">Update Tax<i class="fa fa-tax" aria-hidden="true"></i></span></a></li>
 			<li <?php if ($currentPage == 'payperiods.php') { ?> class="active" <?php } ?>><a href="../payperiods.php"><i class="fa fa-calendar"></i><span class="hidden-minibar">Pay Period</span></a></li>
 			<li <?php if ($currentPage == 'edit_conhess_conmess.php') { ?> class="active" <?php } ?>><a href="../edit_conhess_conmess.php"><i class="fa fa-table"></i><span class="hidden-minibar">Salary Table</span></a></li>
 			<li <?php if ($currentPage == 'users.php') { ?> class="active" <?php } ?>><a href="../users.php"><i class="fa fa-users"></i><span class="hidden-minibar">Users</span></a></li>
 			<li <?php if ($currentPage == 'payprocess.php') { ?> class="active" <?php } ?>><a href="../payprocess.php"><i class="fa fa-cog"></i><span class="hidden-minibar">Process Payroll</span></a></li>
 			<li <?php if ($currentPage == 'multiAdjustment.php') { ?> class="active" <?php } ?>><a href="../multiAdjustment.php"><i class="fa fa-shopping-cart"></i><span class="hidden-minibar">Periodic Data</span></a></li>
 			<li <?php if ($currentPage == 'groupAdjustment.php') { ?> class="active" <?php } ?>><a href="../groupAdjustment.php"><i class="fa fa-group"></i><span class="hidden-minibar">Group Adjustment</span></a></li>
 			<li <?php if ($currentPage == 'departments.php') { ?> class="active" <?php } ?>><a href="../departments.php"><i class="fa fa-home"></i><span class="hidden-minibar">Department</span></a></li>
 			<li <?php if ($currentPage == 'bank.php') { ?> class="active" <?php } ?>><a href="../bank.php"><i class="fa fa-money"></i><span class="hidden-minibar">Bank</span></a></li>
 			<li <?php if ($currentPage == 'editpfa.php') { ?> class="active" <?php } ?>><a href="../editpfa.php"><i class="fa fa-money"></i><span class="hidden-minibar">PFA</span></a></li>
 			<li <?php if ($currentPage == 'email_deduction.php') { ?> class="active" <?php } ?>><a href="../email_deduction.php"><i class="fa fa-envelope"></i><span class="hidden-minibar">email Ded List</span></a></li>

 		<?php
			} ?>
 		<li <?php if ($currentPage == 'multiAdjustment.php') { ?> class="active" <?php } ?>><a href="../multiAdjustment.php"><i class="fa fa-shopping-cart"></i><span class="hidden-minibar">Periodic Data</span></a></li>
 		<li <?php if ($currentPage == 'report/index.php') { ?> class="active" <?php } ?>><a href="index.php"><i class="fa fa-bar-chart-o"></i><span class="hidden-minibar">Reports</span></a></li>
 		<li>
 			<a href="index.php"><i class="fa fa-power-off"></i><span class="hidden-minibar">Logout</span></a>
 		</li>
 	</ul>
 </div>