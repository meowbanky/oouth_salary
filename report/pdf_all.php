<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


function generatePdf($item, $period)
{


    //============================================================+
    // File name   : example_001.php
    // Begin       : 2008-03-04
    // Last Update : 2013-05-14
    //
    // Description : Example 001 for TCPDF class
    //               Default Header and Footer
    //
    // Author: Nicola Asuni
    //
    // (c) Copyright:
    //               Nicola Asuni
    //               Tecnick.com LTD
    //               www.tecnick.com
    //               info@tecnick.com
    //============================================================+

    /**
     * Creates an example PDF TEST document using TCPDF
     * @package com.tecnick.tcpdf
     * @abstract TCPDF - Example: Default Header and Footer
     * @author Nicola Asuni
     * @since 2008-03-04
     */

    // Include the main TCPDF library (search for installation path).
    require_once('tcpdf.php');

    // create new PDF document
    $pdf = new TCPDF("L", PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SALARY UNIT');
    $pdf->SetTitle('OOUTH PAYSLIP');
    $pdf->SetSubject('Pay Slip');
    $pdf->SetKeywords('OOUTH, payslip, sagamu');


    // // set default header data
    // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 001', PDF_HEADER_STRING, array(0, 64, 255), array(0, 64, 128));
    // $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));


    $pdf->setHeaderData('oouth_logo.png', 10, 'Olabisi Onabanjo University Teaching Hospital', 'Generated on ' . date('d-m-Y:H:s'), array(0, 64, 255), array(0, 64, 128));
    $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));

    // // set default header data
    // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH);
    // $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));

    // set header and footer fonts
    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {
        require_once(dirname(__FILE__) . '/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    // ---------------------------------------------------------

    // set default font subsetting mode
    $pdf->setFontSubsetting(true);

    // Set font
    // dejavusans is a UTF-8 Unicode font, if you only need to
    // print standard ASCII chars, you can use core fonts like
    // helvetica or times to reduce file size.
    $pdf->SetFont('dejavusans', '', 8, '', true);

    // Add a page
    // This method has several options, check the source code documentation for more information.
    $pdf->AddPage();


    // Set some content to print



    ini_set('max_execution_time', '0');


    include_once('../classes/model.php');
    require_once('../Connections/paymaster.php');
    //include('../header1.php');

    if (!isset($period)) {
        $period = -1;
    }
    $db_server = "localhost";
    $db_user =     "root";
    $db_passwd = "Oluwaseyi";

    try {
        $conn = new PDO("mysql:host=$db_server;dbname=salary", $db_user, $db_passwd, array(PDO::ATTR_PERSISTENT => true));
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo "Failed Connection: " . $e->getMessage();
    }


    //global $conn;


    if (!isset($item)) {
        $item = -1;
    }
$offset = 0;


    $query = $conn->prepare("SELECT staff_id FROM master_staff WHERE period = ? ");
    $query->execute(array($period));
    $ftres = $query->fetchAll(PDO::FETCH_ASSOC);
    $count = $query->rowCount();
    $counter = 1;
    //print($count . "<br />");
    //print_r($ftres);
    $counter = 0;


    while ($row_all = array_shift($ftres)) {

        //echo $row_all['staff_id'];
        try {
            $query = $conn->prepare('SELECT payperiods.description, payperiods.periodYear, payperiods.periodId FROM payperiods WHERE periodId = ?');
            $res = $query->execute(array($period));
            $out = $query->fetchAll(PDO::FETCH_ASSOC);

            while ($row = array_shift($out)) {
                $fullPeriod =  $row['description'] . '-' . $row['periodYear'];
                //($fullPeriod);
            }
        } catch (PDOException $e) {
            $e->getMessage();
        }

        $html = <<<EOF
<!-- EXAMPLE OF CSS STYLE -->
<style>
   .right {
  text-align: right;
  margin-right: 1em;
}

.left {
  text-align: left;
  margin-left: 1em;
}
</style>
EOF;



        $html = '
<table cellspacing="0" cellpadding="1" border="1" style="border-color:gray;width:50%;background-image: url(images/oouth_logo: .png);">
            <colgroup>
                <col>
                <col>
            </colgroup>
            <tbody>
                <tr style="background-color:green;color:white;">
                    <td colspan="2">
                         OOUTH, SAGAMU 

                         PAYSLIP FOR ' . $fullPeriod;


        //while ($counter > $count) {
        //$html .= "<td>";
        //Print employee payslips
        //$thisemployee = $ftres['' . $counter . ''];
        //print_r($thisemployee);





        try {
            $query = $conn->prepare('SELECT
	tbl_bank.BNAME, 
	tbl_dept.dept, 
	master_staff.STEP, 
	master_staff.GRADE, 
	master_staff.staff_id, 
	master_staff.`NAME`, 
	master_staff.ACCTNO, 
	employee.EMAIL
FROM
	master_staff
	INNER JOIN
	tbl_dept
	ON 
		tbl_dept.dept_id = master_staff.DEPTCD
	INNER JOIN
	tbl_bank
	ON 
		tbl_bank.BCODE = master_staff.BCODE
	INNER JOIN
	employee
	ON 
		master_staff.staff_id = employee.staff_id WHERE master_staff.staff_id = ? and period = ?');
            $res = $query->execute(array($row_all['staff_id'], $period));
            $row_staff = $query->fetch();

            $html .= '
                    </td>
                </tr>
                <tr>
                    <td>
                         Name: 
                    </td>
                    <td>' . $row_staff['NAME'] . '</td>
                </tr>
                <tr>
                    <td>
                         Staff No.: 
                    </td>
                    <td>' . $row_staff['staff_id'] . '</td>
                </tr>
                <tr>
                    <td>
                         Dept: 
                    </td>
                    <td>' . $row_staff['dept'] . '</td>
                </tr>
                <tr>
                    <td>
                         Bank: 
                    </td>
                    <td>' . $row_staff['BNAME'] . '</td>
                </tr>
                <tr>
                    <td>
                         Acct No.: 
                    </td>
                    <td>' . $row_staff['ACCTNO'] . '</td>
                </tr>
                <tr>
                    <td>
                         GRADE LEVEL: 
                    </td>
                    <td>' . $row_staff['GRADE'] . '/' . $row_staff['STEP'] . '</td>
                </tr>';
        } catch (PDOException $e) {
            echo $e->getMessage();
        }



        $consolidated = 0;

        try {
            $query = $conn->prepare('SELECT tbl_master.staff_id,tbl_master.allow FROM tbl_master WHERE allow_id = ? and staff_id = ? and period = ?');
            $fin = $query->execute(array('1', $row_all['staff_id'], $period));
            //$res = $query->fetchAll(PDO::FETCH_ASSOC);
            $res_consolidated = $query->fetch();
            //print_r($res);
            $consolidated = $res_consolidated['allow'];



            $conso = number_format($res_consolidated['allow']);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }

        $totalAllow = 0;

        $html .= '         <tr>
                    <td colspan="2">
                        <strong> CONSOLIDATED SALARY </strong>
                    </td>
                </tr>
                <tr>
                    <td>
                         CONSOLIDATED SALARY: 
                    </td>
                    <td align="right">' . $conso . '</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <strong> ALLOWANCES </strong>
                    </td>
                    
                </tr>';
        try {

            $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.allow, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE allow_id <> ? and staff_id = ? and period = ? and type = ?');
            $fin = $query->execute(array('1', $row_all['staff_id'], $period, '1'));
            $res = $query->fetchAll(PDO::FETCH_ASSOC);
            //print_r($res);

            foreach ($res as $row => $link) {
                $totalAllow = $totalAllow + floatval($link['allow']);
                $html .= '       <tr>
                    <td>' . $link['ed'] . '</td>
                     <td align="right">' . number_format($link['allow']) . '</td>
                </tr>';
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        $html .= '        <tr>
                    <td>
                        <strong> Gross Salary </strong>
                    </td>
                    <td align="right">' . number_format(floatval($totalAllow) + floatval($consolidated)) . '</td>
                </tr>
                <tr>
                    <td colspan="2">
                        <strong> Deductions </strong>
                    </td>
                </tr>';
        $totalDeduction = 0;
        try {
            $query = $conn->prepare('SELECT tbl_master.staff_id, tbl_master.deduc, tbl_earning_deduction.ed FROM tbl_master INNER JOIN tbl_earning_deduction ON tbl_earning_deduction.ed_id = tbl_master.allow_id WHERE staff_id = ? and period = ? and type = ?');
            $fin = $query->execute(array($row_all['staff_id'], $period, '2'));
            $res = $query->fetchAll(PDO::FETCH_ASSOC);


            foreach ($res as $row => $link) {

                //Get ED description
                $totalDeduction = $totalDeduction + floatval($link['deduc']);

                $html .= '      <tr>
                    <td>' . $link['ed'] . '</td>
                    <td align="right">' . number_format($link['deduc']) . '</td>
                </tr>';
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        $html .= '           <tr>
                    <td>
                        <strong>Total Deductions </strong>
                    </td>
                   <td align="right">' . number_format($totalDeduction) . '</td>
                </tr>
                <tr>
                    <td>
                        <strong><em> Net Pay </em></strong>
                    </td>
                    <td align="right">' . number_format((floatval($totalAllow) + floatval($consolidated)) - floatval($totalDeduction)) . '</td>
                </tr>
            </tbody>
        </table>';

        $offset=$offset+3;
        $counter++;
        //end employee payslips
        //}

        // Print text using writeHTMLCell()


        // $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);
        $pdf->writeHTML($html);
        // ---------------------------------------------------------
       // $pdf->AddPage();
        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.
       
    }

    // $pdf->writeHTML($html);
    // $pdf->AddPage();


    $filename = $row_staff['NAME'] . '-' . $fullPeriod . '.pdf';

    $pdf->Output($filename, 'I');

    //echo $html;
    //}
}
generatePdf('1140', 15);
