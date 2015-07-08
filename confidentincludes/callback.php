<?php

/* Callback resource for EC Confident CAPTCHA AJAX calls
/* Requires EC Confident CAPTCHA */
require_once('../confidentcaptcha/ConfidentCaptchaClient.php');

/* Generate callback response */
if(isset($_REQUEST['endpoint'])){
    $confidentCaptchaClient = new ConfidentCaptchaClient();
    $return = $confidentCaptchaClient->callback($_REQUEST);
    header($return[0]);
    echo $return[1];
}