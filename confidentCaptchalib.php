<?php

define("confidentCaptcha_API_SERVER", "http://captcha.confidenttechnologies.com/captcha");
define("confidentCaptcha_API_SECURE_SERVER", "https://captcha.confidenttechnologies.com/captcha");
define("confidentCaptcha_VERIFY_SERVER", "https://captcha.confidenttechnologies.com/captcha/");

function _confidentCaptcha_http_post($host, $path, $data, $port = 80) {
	   $req = encode_POST_data($data);
       $ch = curl_init();
	   curl_setopt($ch, CURLOPT_URL, $host);
	   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE );
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE );
	   curl_setopt($ch, CURLOPT_HEADER, FALSE );
	   curl_setopt($ch, CURLOPT_POST, count($data));
	   curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
       $response = curl_exec($ch);
	   return $response;
}
function encode_POST_data($data)
{
   $req = "";
   foreach ($data as $key => $value)
   {
      $req .= $key . '=' . urlencode(stripslashes($value)) . '&';
   }
   $req = rtrim($req, '&');
   return $req;
}

function confidentCaptcha_get_html ($options, $error = null, $use_ssl = false)
{
	if ($options['site_id'] == null || $options['site_id'] == '') {
		die ("To use confidentCaptcha you must get an API key from <a href='https://www.confidenttechnologies.com'>https://www.confidenttechnologies.com</a>");
	}
	if ($use_ssl) {
                $server = confidentCaptcha_API_SECURE_SERVER;
        } else {
                $server = confidentCaptcha_API_SERVER;
        }
    $response = _confidentCaptcha_http_post ($server, "/captcha", $options );
    if($response == '' || $response == null){
	   return createArithmeticCaptcha();
	}
        $errorpart = "";
        if ($error) {
           $errorpart = "&amp;error=" . $error;
        }
		return $response;
}
function createArithmeticCaptcha()
{
   $captcha = <<<CAPTCHA
   <script type="text/javascript">
      var a = Math.ceil(Math.random() * 10);
      var b = Math.ceil(Math.random() * 10);
      function createArithmeticCaptcha(){
         document.write("What is "+ a + " + " + b +"? ");
         document.write("<input name='arithmeticCaptchaUserInput' id='arithmeticCaptchaUserInput' type='text' maxlength='2' size='2'/>");
         document.write("<input name='arithmeticCaptchaNumberA' id='arithmeticCaptchaNumberA' type='hidden' value='var a'/>");
         document.write("<input name='arithmeticCaptchaNumberB' id='arithmeticCaptchaNumberB' type='hidden' value='var b'/>");
         document.getElementById('arithmeticCaptchaNumberA').value = a;
         document.getElementById('arithmeticCaptchaNumberB').value = b;
      }
      createArithmeticCaptcha();
   </script>
CAPTCHA;
   return $captcha;
}
function validateArithmeticCaptcha($request)
{
   // init our return value
   $matchCaptchaRequestPassed = false;

   if (! is_null($request) && ! empty($request))
   {
       $numberA = $request['arithmeticCaptchaNumberA'];
       $numberB = $request['arithmeticCaptchaNumberB'];
       $userGivenAnswer = $request['arithmeticCaptchaUserInput'];
   }
   if (isset($numberA) && isset($numberB) && isset($userGivenAnswer))
   {
      // see if they got the answer correct
      if (intval($numberA) + intval($numberB) == intval($userGivenAnswer))
      {
         $matchCaptchaRequestPassed = true;
      }
   }
   return $matchCaptchaRequestPassed;
}
class confidentCaptchaResponse {
        var $is_valid;
        var $error;
}

function confidentCaptcha_check_answer ($data)
{
    if( isset($_POST['arithmeticCaptchaNumberA']) )
	{
	    $result = $this->validateArithmeticCaptcha($_POST);
		if ( $result == true ){
		    $confidentCaptcha_response->is_valid = true;
		}
	    else
		{
                $confidentCaptcha_response->is_valid = false;
                $confidentCaptcha_response->error = "EC Confident CAPTCHA was solved incorrectly";
        }
        return $confidentCaptcha_response;
	}
	if ($data['api_key'] == null || $data['api_key'] == '') {
		die ("To use confidentCaptcha you must get an API key from <a href='https://www.google.com/confidentCaptcha/admin/create'>https://www.google.com/confidentCaptcha/admin/create</a>");
	}
   $server = confidentCaptcha_API_SECURE_SERVER . '/' . $data['confidentCaptchaID'];
   $options = array (
	  'public_key' => $data['public_key'],
	  'api_key' => $data['api_key'],
	  'click_coordinates' => $data['click_coordinates'],
	  'code' => $data['code'],
	  'library_version' => $data['library_version']
   ); 
    //discard spam submissions
    $response = _confidentCaptcha_http_post ($server, "/captcha", $options);
    $confidentCaptcha_response = new confidentCaptchaResponse();
    if (trim ($response) == 'True') {
       $confidentCaptcha_response->is_valid = true;
    }
        else {
                $confidentCaptcha_response->is_valid = false;
                $confidentCaptcha_response->error = "EC Confident CAPTCHA was solved incorrectly";
        }
        return $confidentCaptcha_response;
}
function confidentCaptcha_get_signup_url ($domain = null, $appname = null) {
	return "http://www.confidenttechnologies.com";
}
function _confidentCaptcha_aes_pad($val) {
	$block_size = 16;
	$numpad = $block_size - (strlen ($val) % $block_size);
	return str_pad($val, strlen ($val) + $numpad, chr($numpad));
}
?>
