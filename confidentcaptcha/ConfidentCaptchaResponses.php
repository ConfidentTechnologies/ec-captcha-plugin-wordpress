<?php
/**
 * Response objects from a EC Confident CAPTCHA API call
 */
class ConfidentCaptchaResponse
{
    /**
     * HTTP status code returned by the CAPTCHA API. Standard HTTP codes are used, as well as 0 to signify
     * that the server did not respond.
     * @var integer
     */
    private $status;

    /**
     * HTTP body returned by API
     * If the status is 200, then the body is the response from the CAPTCHA API server. Otherwise,
     * the response is an error from the CAPTCHA API server or a cURL error.
     * @var String
     */
    private $body;

    /**
     * HTTP request method
     * @var String
     */
    private $method;

    /**
     * Full RESTful API call URL
     * @var String
     */
    private $url;

    /**
     * Request form parameters, or null if not a POST
     * @var String
     */
    private $form;

    /**
     * If there is an issue with the request to the confident captcha server,
     * it will be saved here.
     * @var String
     */
    private $errorMessage;


    /**
     * Construct a ConfidentCaptchaResponse
     *
     * @param integer $status HTTP status code
     * @param String $body HTTP response body
     * @param String $method HTTP request method
     * @param String $url Request URL
     * @param String $form Form parameters (or NULL if not a POST)
     * @return ConfidentCaptchaResponse
     */
    public function __construct($status, $body, $method=null, $url=null, $form=null)
    {
        $this->status       = $status;
        $this->body         = $body;
        $this->method       = $method;
        $this->url          = $url;
        $this->form         = $form;

        if($status !=200 ){
            $this->errorMessage = $body;
        }
    }

    /**
     * Getter for response status
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Setter for response status
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Getter for response body
     * @return String
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Setter for response body
     * @param String $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Getter for HTTP request method
     * @return String
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Getter for the full RESTful API call URL
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Getter for the form request variables used
     * @return String
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Checks if the request to the CAPTCHA API server was successful
     * @return bool
     */
    public function wasRequestSuccessful()
    {
        return $this->status === 200;
    }

    /**
     * Gets the error message from the CAPTCHA API server or the cURL call
     * @return String
     */
    public function getErrorMessage(){
        if($this->wasRequestSuccessful() == false){
            return $this->errorMessage;
        }
        return null;
    }

    /**
     * Checks to see if this response object has an error message.
     * @return bool
     */
    public function hasError(){
        return $this->errorMessage != null;
    }

}

/**
 * Response from EC Confident CAPTCHA credential check API call
 */
class CheckCredentialsResponse extends ConfidentCaptchaResponse
{
    /**
     * If valid credentials
     * @return bool
     */
    public function wasValidCredentials()
    {
        return $this->getStatus() === 200;
    }
}

/**
 * Response from EC Confident CAPTCHA validation API call
 */
class CheckCaptchaResponse extends ConfidentCaptchaResponse
{

    /**
     * Getter for created CAPTCHA ID
     *
     * @return String
     */
    public function getCaptchaId()
    {
        $captchIdPos = strrpos($this->getUrl(), "captcha") + strlen("captcha") + 1;
        return substr($this->getUrl(), $captchIdPos);
    }

    /**
     * Checks whether the solution to the CAPTCHA was valid
     * @return boolean
     */
    public function wasCaptchaSolved()
    {
        if ($this->getStatus() != 200)
        {
            return false;
        }

        $response = json_decode($this->getBody(), true);
        if (strtolower($response['answer']) == true ){
            return true;
        }

        return false;
    }
}