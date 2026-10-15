<!DOCTYPE html> 
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no">
    <?php echo get_favicon_tags(true); ?>
    <meta name="description" content="">

	<!-- <base href="/manager/"> -->
	
    <meta name="msapplication-tap-highlight" content="no">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/v4-shims.min.css" integrity="sha512-fHavkBby/gcFEB2taaBfG0DLdHRGrnvkWQNXVZ5Yb/Fj6LkogecQUd6oyvBVsrWPaHSxs5tNza6LUW/Y6Az9lQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	<link href="assets/css/main.php" rel="stylesheet">
	<link href="res/css/venobox.css" rel="stylesheet">
	<link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/custom-overrides.css" rel="stylesheet">
    <link href="assets/css/custom-badges.css" rel="stylesheet">
	
	<!-- Summernote Editor CSS -->
	<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-bs4.min.css" rel="stylesheet" integrity="sha384-hqv27sxmxAI2L4eughLkUpjS75/Z3/hg9DOWIl0PJWE4B6GJqM2Kx72ZPoQzsUpF" crossorigin="anonymous">
	<?php echo function_exists('get_custom_fonts_style_block') ? get_custom_fonts_style_block(true) : ''; ?>
	
</head>

<body>
	
<div class="dashboard-layout">

<?php
// show potential errors / feedback (from login object) unless disabled
if (!isset($hide_global_notices) || !$hide_global_notices) {
    if (isset($login)) {
        if ($login->errors) {
            foreach ($login->errors as $error) {
                echo '<div class="alert alert-danger text-center notice">'.$error.'</div>';
            }
        }
        if ($login->messages) {
            foreach ($login->messages as $message) {
               echo '<div class="alert alert-info text-center notice">'.$message.'</div>';
            }
        }
    }

    // show potential errors / feedback (from registration object)
    if (isset($registration)) {
        if ($registration->errors) {
            foreach ($registration->errors as $error) {
                echo '<div class="alert alert-danger text-center notice">'.$error.'</div>';
            }
        }
        if ($registration->messages) {
            foreach ($registration->messages as $message) {
                echo '<div class="alert alert-info text-center notice">'.$message.'</div>';
            }
        }
    }
}
?>
