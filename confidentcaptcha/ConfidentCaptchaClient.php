<?php
require_once('ConfidentCaptchaConfiguration.php');
require_once('ConfidentCaptchaResponses.php');

require_once(__DIR__.'/../confidentCaptcha.php');

require_once(__DIR__.'/../../../../wp-load.php' );

define('CALLBACK', get_option('siteurl') .'/wp-content/plugins/ec-captcha-plugin-wordpress/confidentincludes/callback.php');
define('MATH', get_option('siteurl'). '/wp-content/plugins/ec-captcha-plugin-wordpress/confidentincludes/mathcaptcha.js');

/**
 * EC Confident CAPTCHA API Client
 * This is the entry point to call CAPTCHA API. The front end UI should call
 * this Client to interact with CAPTCHA server.
 */
class ConfidentCaptchaClient
{
    /**
     * ConfidentCaptchaConfiguration class instance
     * @var ConfidentCaptchaConfiguration
     */
    private $configuration;

    private $credentials;  

    /**
     * CAPTCHA API Server URL
     * @var String
     */
    private $apiServerUrl = "http://ctivs1.confidenttechnologies.com";
    /**
     * CAPTCHA Event Tracking URL
     * @var String
     */
    private $eventUrl = "http://tracker.confidenttechnologies.com/tracker";

    private $apiKey;

    /**
     * Error response object, set whenever an error is caught
     * @var ConfidentCaptchaResponse
     */
    private $responseError;

    private $callback_url = CALLBACK;

    private $mathCaptchaUrl = MATH;

    private $pluginLanguage = "PHP";

    private $pluginVersion = "2.6.8";

    private $api_key;

    /**
     * Constructor
     * @param String $settingsXmlFilePath Server path to settings XML file containing credentials and properties
     * @return ConfidentCaptchaClient
     */
    public function __construct($settingsArray=null)
    {

         if($settingsArray != null){
            $this->configuration = new ConfidentCaptchaConfiguration($settingsArray);
        }
        else{
            $this->configuration = new ConfidentCaptchaConfiguration();
        }

    }


    public function getCaptchaProperties()
    {
        return $this->captchaProperties;
    }

    /**
     * Setter for Properties
     *
     * @param $confidentCaptchaProperties
     */
    public function setCaptchaProperties($confidentCaptchaProperties)
    {
        $this->captchaProperties = $confidentCaptchaProperties;
    }
    /**
     * Getter for credentials and properties object
     * @return ConfidentCaptchaConfiguration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Setter for credentials and properties object
     * @param ConfidentCaptchaConfiguration $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }


     /**
     * Getter for Credentials
     *
     * @return ConfidentCaptchaCredentials
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Setter for Credentials
     *
     * @param $confidentCaptchaCredentials
     */
    public function setCredentials($confidentCaptchaCredentials)
    {

        $this->credentials = $confidentCaptchaCredentials;
    }

     /**
     * Getter for Credentials
     *
     * @return ConfidentCaptchaCredentials
     */
    public function getApiKey()
    {
        return $this->credentials;
    }

    /**
     * Setter for Credentials
     *
     * @param $confidentCaptchaCredentials
     */
    public function setApiKey($credentials)
    {
        $apiKey = $credentials->api_key;
    }


    /**
     * Getter for CAPTCHA API Server URL
     * @return String
     */
    public function getApiServerUrl()
    {
        return $this->apiServerUrl;
    }

    /**
     * Setter for CAPTCHA API Server URL
     * @param String $apiServerUrl
     */
    public function setApiServerUrl($apiServerUrl)
    {
        $this->apiServerUrl = $apiServerUrl;
    }

    /**
     * Getter for CAPTCHA Event URL
     * @return String
     */
    public function getEventUrl()
    {
        return $this->eventUrl;
    }

    /**
     * Setter for CAPTCHA API Server URL
     * @param String $apiServerUrl
     */
    public function setEventUrl($eventUrl)
    {
        $this->eventUrl = $eventUrl;
    }

        /**
     * Getter for CAPTCHA Event URL
     * @return String
     */
    public function getMathCaptchaUrl()
    {
        return $this->mathCaptchaUrl;
    }

    /**
     * Setter for CAPTCHA API Server URL
     * @param String $apiServerUrl
     */
    public function setMathCaptchaUrl($mathCaptchaUrl)
    {
        $this->mathCaptchaUrl = $mathCaptchaUrl;
    }

        /**
     * Getter for CAPTCHA Event URL
     * @return String
     */
    public function getCallbackUrl()
    {
        return $this->callback_url;
    }

    /**
     * Setter for CAPTCHA API Server URL
     * @param String $apiServerUrl
     */
    public function setCallbackUrl($callback_url)
    {
        $this->callback_url = $callback_url;
    }
    /**
     * Calls the /captcha API endpoint and returns an object containing the response from the server or an error response.
     * @param $request $_REQUEST Optional PHP request object used to keep track of auth token and server key
     * @return ConfidentCaptchaResponse
     */
    public function requestCaptcha($request = null)
    {

        $endPointUrl = $this->apiServerUrl . '/captcha';
        $httpMethod = 'POST';

        /* Initially these two parameters will be empty, but on subsequent requests, they will be populated
         * by data from the first requested CAPTCHA. The server key is used to tell our load balancers which
         * which CAPTCHA API server to send your request to. The auth token is used to keep track of how many attempts
         * it has taken a user to solve the CAPTCHA correctly.
         */


        $httpParameters['api_key'] = $this->configuration->getProperty('api_key');

        $httpParameters['auth_token'] = $request['auth_token'];
        $httpParameters['server_key'] = $request['server_key'];

        $apiResponse = $this->makeRequest($endPointUrl,  $httpMethod, $httpParameters);

        if ($apiResponse->getStatus() != 200) {
            //The server is down, so check the failure policy and react accordingly
            $failure_policy_math = $this->configuration->getProperty('failure_policy');

            if($failure_policy_math == "math"){
                $apiResponse->setBody( $this->createMathCaptcha());
            }
            else{
                $apiResponse->setBody( $this->createDummyCaptcha());
            }
        }

        return $apiResponse;
    }


    /**
     * Creates and returns the HTML code of a EC Confident CAPTCHA
     * @param $request $_REQUEST Optional PHP request object used to keep track of auth token and server key
     * @return String HTML of a CAPTCHA
     */
    public function createCaptcha($request = null)
    {
        $requestCaptchaResponse = $this->requestCaptcha($request);

        if ($requestCaptchaResponse != null)
        {
            return $requestCaptchaResponse->getBody();
        }
    }

    /**
     * Validates AJAX CAPTCHA requests from the CAPTCHA Javascript UI. Takes the $_REQUEST object from PHP
     * as input and extracts all necessary data from it to validate a CAPTCHA on the CAPTCHA API server.
     * @param Array $request PHP $_REQUEST object
     * @return CheckCaptchaResponse
     */
    public function checkCaptcha($request)
    {
        $captchaId = null;
        $code = null;
        $clickCoordinates = null;
        $parameter = null;
        $serverKey = null;
        $cacheKey = null;
        $authToken = null;

        if(!isset($captchaId) || empty($captchaId)){
            if(empty($request['confidentcaptcha_captcha_id']) == false){
                $captchaId=$request['confidentcaptcha_captcha_id'];
            }
        }

        //Checks for a valid CAPTCHA ID, otherwise assumes this was an alternate CAPTCHA
        if (!isset($captchaId) || empty($captchaId)){
            return $this->checkAlternateCaptcha($request);
        }

        if((!isset($code) || empty($code))){
            if(empty($request['confidentcaptcha_code']) == false){
                $code=$request['confidentcaptcha_code'];
            }
        }

        if((!isset($clickCoordinates) || empty($clickCoordinates))){
            if(empty($request['confidentcaptcha_click_coordinates']) == false){
                $clickCoordinates=$request['confidentcaptcha_click_coordinates'];
            }
        }

        $parameter = $request['confidentcaptcha_server_key'];
        if(!empty($parameter)){
            $serverKey = $parameter;
        }

        $parameter = $request['confidentcaptcha_cache_key'];
        if(!empty($parameter)){
            $cacheKey = $parameter;
        }

        $parameter = $request['confidentcaptcha_auth_token'];
        if(!empty($parameter)){
            $authToken = $parameter;
        }

        $parameter = $request['confidentcaptcha_auth_token'];
        if(!empty($parameter)){
            $authToken = $parameter;
        }


        $endPointUrl = $this->apiServerUrl . "/captcha/" . $captchaId;

        $httpParameters['captcha_id'] = $captchaId;
        $httpParameters['code'] = $code;
        $httpParameters['click_coordinates'] = $clickCoordinates;
        $httpParameters['cache_key'] = $cacheKey;
        $httpParameters['auth_token'] = $authToken;
        $httpParameters['server_key'] = $serverKey;

        $httpMethod = 'POST';

        $apiResponse = $this->makeRequest($endPointUrl, $httpMethod, $httpParameters);

        $response = new CheckCaptchaResponse($apiResponse->getStatus(),$apiResponse->getBody(), $apiResponse->getMethod(), $apiResponse->getUrl(), $apiResponse->getForm());

        return $response;
    }

    /**
     * Securely validates a user submitted CAPTCHA by calling the /secure_server_validation endpoint on the CAPTCHA
     * API server. Takes the $_REQUEST object from PHP as input and extracts all necessary data from it to validate
     * a CAPTCHA on the CAPTCHA API server.
     * @param Array $request PHP $_REQUEST object
     * @return CheckCaptchaResponse
     */
    public function secureServerValidate($request){
        $serverValidationKey = null;
        $authToken = null;
        $serverKey = null;

        //TODO: This may be dead, so remove it
        $serverValidationKey = $this->configuration->getKey('server_validation_key');

        $parameter = $request['confidentcaptcha_server_key'];
        if(!empty($parameter)){
            $serverKey = $parameter;
        }

        $parameter = $request['confidentcaptcha_auth_token'];
        if(!empty($parameter)){
            $authToken = $parameter;
        }

        $parameter = $this->pluginLanguage;
        if(!empty($parameter)){
            $pluginLanguage = $parameter;
        }

        $parameter = $this->pluginVersion;
        if(!empty($parameter)){
            $pluginVersion = $parameter;
        }

        if (!isset($authToken) || empty($authToken)){
            return $this->checkAlternateCaptcha($request);
        }

        $endPointUrl = $this->apiServerUrl . "/secure_server_validation";
        $httpMethod = 'POST';

        $httpParameters['api_key'] = $this->configuration->getCredentials();
        $httpParameters['server_validation_key'] = $serverValidationKey;
        $httpParameters['auth_token'] = $authToken;
        $httpParameters['server_key'] = $serverKey;
        $httpParameters['plugin_language'] = $pluginLanguage;
        $httpParameters['plugin_version'] = $pluginVersion;


        $apiResponse = $this->makeRequest($endPointUrl, $httpMethod, $httpParameters);

        return new CheckCaptchaResponse($apiResponse->getStatus(),$apiResponse->getBody(), $apiResponse->getMethod(), $apiResponse->getUrl(), $apiResponse->getForm());

    }

    /**
     * Depending on your chosen failure policy, this method validates a math captcha or uses an open or closed policy.
     * Takes the $_REQUEST object from PHP as input and extracts all necessary data from it to validate
     * a math CAPTCHA if that is what your failure policy is set to.
     * @param Array $request PHP $_REQUEST object
     * @return CheckCaptchaResponse
     */
    private function checkAlternateCaptcha($request){
        $status = 404;
        //Check the failure policy to determine what to do when the server is unresponsive
        $failure_policy_math = $this->configuration->getProperty('failure_policy');

        if($failure_policy_math == "math"){
            $isValid = $this->validateMathCaptcha($request);
        }
        elseif($failure_policy_math == "open"){
            $isValid = true;
        }
        else{
            $isValid = false;
        }

        if ($isValid == true)
        {
            $status = 200;
            $body = '{"answer": true, "server_auth_token": ""}';
        }
        else{
            $status = 200;
            $body = '{"answer": false, "server_auth_token": ""}';
        }

        return new CheckCaptchaResponse($status, $body);

    }

    /**
     * A math CAPTCHA is created when the CAPTCHA API server is not reachable and the failure policy is set to "math".
     * @return String HTML of a math CAPTCHA
     */
    private function createMathCaptcha()
    {

        $captcha = <<<CAPTCHA
        <div id="confidentcaptcha_wrapper">
            <style id="confidentcaptcha_math_css" type="text/css"></style>
            <div id="confidentcaptcha_badge"></div>
            <div id="confidentcaptcha_lightbox"></div>
            <div id="confidentcaptcha_modal">
                <div id='mathcaptcha_message'></div><div id='confidentcaptcha_user_input'><input name='arithmeticCaptchaUserInput' id='arithmeticCaptchaUserInput' type='text' maxlength='2' size='2' onkeyup='ajaxMathCaptcha();'/></div>
                <input name='arithmeticCaptchaNumberA' id='arithmeticCaptchaNumberA' type='hidden' value=''/>
                <input name='arithmeticCaptchaNumberB' id='arithmeticCaptchaNumberB' type='hidden' value=''/>
                <div id='confidentcaptcha_numpad_container'><ul id="confidentcaptcha_numpad">
                    <li class='confidentcaptcha_numpad_hover'>1</li>
                    <li class='confidentcaptcha_numpad_hover'>2</li>
                    <li class='confidentcaptcha_numpad_hover lastitem'>3</li>
                    <li class='confidentcaptcha_numpad_hover'>4</li>
                    <li class='confidentcaptcha_numpad_hover'>5</li>
                    <li class='confidentcaptcha_numpad_hover lastitem'>6</li>
                    <li class='confidentcaptcha_numpad_hover'>7</li>
                    <li class='confidentcaptcha_numpad_hover'>8</li>
                    <li class='confidentcaptcha_numpad_hover lastitem'>9</li>
                    <li class='confidentcaptcha_numpad_hover zeroinput'>0</li>
                </ul></div>
            </div>
            <script type="text/javascript">
                var confidentGlobals = {};
                confidentGlobals.tracking_pixel = "%s";
                confidentGlobals.confidentcaptcha_callback = "%s";
            </script>
            <script type="text/javascript" src="%s"></script>
        </div>
CAPTCHA;
        $tracking_url = $this->eventUrl . "?id=math&public_key=" . $this->configuration->getProperty('public_key');
        $captcha = sprintf($captcha, $tracking_url, $this->callback_url, $this->mathCaptchaUrl);

        return $captcha;
    }

    /**
     * A dummy CAPTCHA is created when the CAPTCHA API server is not reachable and the failure policy is set to "open"
     * or "closed"
     *
     * @return String
     */
    private function createDummyCaptcha()
    {
        $captcha = <<<CAPTCHA
        <script type="text/javascript">
            function createDummyCaptcha(){
                document.write("Captcha disabled.  Please continue.");
                document.write("<input name='confidentcaptcha_code' id='confidentcaptcha_code' type='hidden' value='0'/>");
                document.write("<input name='confidentcaptcha_captcha_id' id='confidentcaptcha_captcha_id' type='hidden' value='0'/>");
                document.getElementById('confidentcaptcha_code').value = 0;
                document.getElementById('confidentcaptcha_captcha_id').value = 0;
            }
            createDummyCaptcha();
        </script>
CAPTCHA;
        return $captcha;
    }

    /**
     * Validates the user's response to a math CAPTCHA.
     * @param Array $request PHP $_REQUEST object
     * @return bool Whether or not the answer was valid
     */
    private function validateMathCaptcha($request)
    {
        $matchCaptchaRequestPassed = false;

        if (! is_null($request) && ! empty($request))
        {
            $numberA = $request['arithmeticCaptchaNumberA'];
            $numberB = $request['arithmeticCaptchaNumberB'];
            $userGivenAnswer = $request['arithmeticCaptchaUserInput'];
        }

        if (isset($numberA) && isset($numberB) && isset($userGivenAnswer))
        {
            if (intval($numberA) + intval($numberB) == intval($userGivenAnswer))
            {
                $matchCaptchaRequestPassed = true;
            }
        }

        return $matchCaptchaRequestPassed;
    }

    /**
     * Interface to call RESTful CAPTCHA API
     *
     * @param $endPointUrl String Endpoint on the CAPTCHA API server
     * @param Array $httpParameters  Any optional request parameters to send
     * @param String $httpMethod  The HTTP method to use for the RESTful call
     * @return ConfidentCaptchaResponse
     */
    public function makeRequest($endPointUrl, $httpMethod, $httpParameters=null )
    {
        $serverRequestUrl = $endPointUrl;

        $options = get_option( 'confidentCaptcha_options' );

        $mandatoryRequestParameters = $this->buildMandatoryRequestParameters($httpParameters);

        if($mandatoryRequestParameters['api_key'] == null){
            $mandatoryRequestParameters['api_key'] = $options['api_key'];
        }

        $configurableParameters = $this->configuration->getProperties();
        
        if($configurableParameters == null){
            $configurableParameters['failure_policy'] = $options['failure_policy'];
            $configurableParameters['debug_mode'] = $options['debug_mode'];
        }

        $requestParameters =   $mandatoryRequestParameters + $configurableParameters;

        if($httpParameters != null){
            $requestParameters = $requestParameters + $httpParameters;
        }

        $form = NULL;
        if (strtoupper($httpMethod) == 'GET') {
            $serverRequestUrl .= '?' . http_build_query($requestParameters, '', '&');
        } elseif (strtoupper($httpMethod) == 'POST' and $requestParameters) {
            $form = http_build_query($requestParameters, '', '&');
        }

        $curlHandle = curl_init();

        if (strtoupper($httpMethod) == 'POST') {
            curl_setopt($curlHandle, CURLOPT_POST, TRUE);

            if ($form) {
                curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $form);
            }
        }

        $sslProtocolUrl = "https://";
        $secureServerRequestUrl = substr($serverRequestUrl, 0, strlen($sslProtocolUrl));
        if (strcmp($secureServerRequestUrl, $sslProtocolUrl) == 0) {
                curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        }

        curl_setopt($curlHandle, CURLOPT_URL, $serverRequestUrl);

        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, TRUE);

        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 8);

        $body = curl_exec($curlHandle);

        $httpResponseCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        if ($body === FALSE || strtolower($body) === "false" || $httpResponseCode != 200) {
            $response = new ConfidentCaptchaResponse($httpResponseCode, $body, strtoupper($httpMethod), $serverRequestUrl, $form);
            $this->responseError = $response;
        }
        else {
            $response = new ConfidentCaptchaResponse($httpResponseCode, $body, strtoupper($httpMethod), $serverRequestUrl, $form);
        }

        curl_close($curlHandle);

        return $response;
    }

    /**
     * This method is called for every RESTful call to the API server. It gathers required information for requesting
     * and validating CAPTCHAs on the CAPTCHA API server.
     * @return Array Mandatory RESTful API request parameters
     */
    private function buildMandatoryRequestParameters()
    {
        $apiTemp = $this->configuration->getCredentials();
        $mandatoryParameters['api_key'] = $apiTemp['api_key'];

        $mandatoryParameters['callback_url'] = $this->callback_url;

        $mandatoryParameters['client_ip']      = $this->getRealIpAddr();
        $mandatoryParameters['user_agent']   = $_SERVER['HTTP_USER_AGENT'];

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $mandatoryParameters['language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $mandatoryParameters['language'] = "en";
        }

        $mandatoryParameters['local_server_name'] = $this->getLocalServerURL();
        $mandatoryParameters['local_server_address'] = $_SERVER['SERVER_ADDR'];

        return $mandatoryParameters;
    }

    /**
     * Gets the client's IP Address
     * @return String
     */
    private function getRealIpAddr()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP']))             // to check ip from share internet
        {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   // to check ip is pass from proxy
        {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Gets the url of the page requesting a EC Confident CAPTCHA
     *
     * @return string
     */
    private function getLocalServerURL()
    {
        $protocol = 'http';

        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
        {
            $protocol = 'https';
        }

        $host = $_SERVER['HTTP_HOST'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $baseUrl = $protocol . '://' . $host . $requestUri;

        if (substr($baseUrl, -1)=='/') {
            $baseUrl = substr($baseUrl, 0, strlen($baseUrl)-1);
        }

        return $baseUrl;
    }

    /**
     * Used by callback.php to handle AJAX CAPTCHA requests from the CAPTCHA Javascript UI.
     * @param Array $request PHP $_REQUEST object
     * @return Array Contains header and the content of this callback response
     */
    public function callback($request){
        $endpoint = $request['endpoint'];

        $header = $_SERVER["SERVER_PROTOCOL"]." 200 OK";

        $content = null;



        if ($endpoint == 'create_captcha') {
                $content = trim($this->createCaptcha($request));
        }
        elseif ($endpoint == 'verify_captcha') {
            $check = $this->checkCaptcha($request);
            $content = $check->getBody();
        }
        elseif ($endpoint == 'callback_check') {
            $content = '{"success": true}';
        }

        return Array($header, $content);
    }

    /**
     * Gets the last error that this plugin processed when trying to request a new CAPTCHA from the CAPTCHA API server.
     * The response object's status is 200 if no errors have been detected yet.
     * @return ConfidentCaptchaResponse A ConfidentCaptchaResponse object representing the error the server sent back
     */
    public function getError(){
        if(isset($this->responseError) && !empty($this->responseError)){
            return $this->responseError;
        }
        else{
            return new ConfidentCaptchaResponse(200, '{"debug_message": "no error"}');
        }
    }
}
