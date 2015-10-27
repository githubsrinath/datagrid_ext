<?php
// currently WORK-IN-PROGRESS

if(!ob_start('ob_gzhandler')) ob_start();
header('Content-Type: text/html; charset=utf-8');
include('lmx.php');
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset='UTF-8'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="assets/ico/favicon.ico">
	<title>LMX EXAMPLE</title>
<?php echo file_get_contents('cdn.php?css=1'); ?>
	<style>
		/* for style navbar-fixed-top */
		body { padding-top: 50px; }
	</style>
</head>
<body>
<?php
try {
	$dbh = new PDO("mysql:host=localhost;dbname=lmx_test;", 'root', 'Fill In Password');
}
catch(PDOException $e) {
	die('pdo connection error: ' . $e->getMessage());
}
$lm = new LMX($dbh);
$lm->return_to_edit_after_insert = false;
$lm->return_to_edit_after_update = false;
$lm->date_out = 'Y-m-d';
$lm->datetime_out = 'Y-m-d H:i';
//$lm->datetime_in = 'Y-m-d H:i:s';   
$lm->grid_multi_delete = false;
$lm->grid_limit = 50; // 2, 50
$lm->grid_show_search_box = true;
$lm->grid_edit_link = '';
$lm->grid_delete_link = '';
$lm->form_display_identity = false; // do not show Id on form
$lm->form_delete_button = '';
?>
<div class="container">

</div>
<!-- container -->
<?php echo file_get_contents('cdn.php?js=1'); ?>

<script>
$(document).ready(function() {
	$("td").css("font-size", "0.9em"); // adjust font-size in table
<?php 
	echo $lm->bs_js;
	echo $lm->controls_js;
?>
});
</script>
</body></html>
