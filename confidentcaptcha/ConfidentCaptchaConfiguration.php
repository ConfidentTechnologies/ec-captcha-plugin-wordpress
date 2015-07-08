<?php
/**
 * EC Confident CAPTCHA Configuration Class
 */
class ConfidentCaptchaConfiguration
{
    /**
     * @var Array Stores the CAPTCHA properties
     */
    private $properties;

    /**
     * @var Array Stores the CAPTCHA credentials
     */
    private $credentials;

    /**
     * @var Array Stores the CAPTCHA keys
     */
    private $keys;


    /**
     * Constructs a ConfidentCaptchaConfiguration object
     * @param String $settingsXml Server path to the settings.xml file
     */
    public function __construct($settingsArray = null) {

        if (isset($settingsArray)) {
            $this->properties = array('failure_policy'=>$settingsArray['failure_policy'],
                                      'debug_mode'=>$settingsArray['debug_mode']);
            $this->credentials = array('api_key'=>$settingsArray['api_key'],
                                        'public_key'=>$settingsArray['public_key']);
            $this->keys = array('server_validation_key'=>'Vv6NnsVRQw');
        }

    }

    /**
     * Getter for the array holding CAPTCHA properties
     * @return Array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Gets a property by name
     * @param String $key name of the property you would like
     * @return String Value of the property or null if it is unavailable
     */
    public function getProperty($key)
    {

        if(isset($this->properties[$key])){
            return $this->properties[$key];
        } 
        return null;
    }

    private function setDefaultProperties()
    {
        if(!isset($this->properties['failure_policy'])){
            $this->properties['failure_policy'] = 'math';
        }
        if(!isset($this->properties['debug_mode'])){
        $this->properties['debug_mode'] = false;
        }
    }
    /**
     * Sets the value of a property using its key
     * @param String $key Key of the property
     * @param String $value Value of the property
     */
    public function setProperty($key, $value)
    {
        $this->properties[$key] = $value;
    }
    /**
     * Getter for the array holding CAPTCHA credentials
     * @return Array
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    public function getCredential($key)
    {
        if(isset($this->credentials[$key])){
            return $this->credentials[$key];
        }
        return null;
    }

    public function setCredential($key, $value)
    {
        $this->credentials[$key] = $value;
    }

    /**
     * Getter for the array holding CAPTCHA keys
     * @return Array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Gets a CAPTCHA API key by name.
     * @param $key
     * @return String
     */
    public function getKey($key)
    {
        if(isset($this->keys[$key])){
            return $this->keys[$key];
        }
        return null;
    }

    /**
     * Sets a CAPTCHA API Key using the key name and its value.
     * @param String $key Name of the API key
     * @param String $value Value of the API key
     */
    public function setKey($key, $value)
    {
        $this->keys[$key] = $value;
    }
}
