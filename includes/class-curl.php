<?php
class Clever_Curl {
	protected  $_status; 
	protected  $_info; 
    /**
     * The file to read and write cookies to for requests
     *
     * @var string  _COOKIE_FILE
    **/
    public $cookie_file;
     
    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
    **/
    public $follow_redirects = true;
    
    /**
     * An associative array of headers to send along with requests
     *
     * @var array
    **/
    public $headers = array();
    
    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array
    **/
    public $options = array();
    
    /**
     * The referer header to send along with requests
     *
     * @var string
    **/
    public $referer;
    
    /**
     * The user agent to send along with requests
     *
     * @var string
    **/
    public $user_agent;
    
    /**
     * Stores an error string for the last request if one occurred
     *
     * @var string
     * @access protected
    **/
    protected $error = '';
    
    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource
     * @access protected
    **/
    public $request;
    
    
    /**
     * Initializes a Curl object
     *
     * Sets the $cookie_file to "curl_cookie.txt" in the current directory
     * Also sets the $user_agent to $_SERVER['HTTP_USER_AGENT'] if it exists, 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)' otherwise
    **/
    function __construct() {
        // $this->cookie_file = _COOKIE_FILE;  //定义常量
        $this->user_agent = 'User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:8.0) Gecko/20100101 Firefox/8.0 Adobe Flash Player 11\r\n';
    }
    
    /**
     * Makes an HTTP DELETE request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse object
    **/
    function delete($url, $vars = array()) {
        return $this->request('DELETE', $url, $vars);
    }
    
    /**
     * Returns the error string of the current request if one occurred
     *
     * @return string
    **/
    function getError() {
        return $this->error;
    }
    
    /**
     * Makes an HTTP GET request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse
    **/
    function get($url, $vars = array()) {
        if (!empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        return $this->request('GET', $url);
    }
    
    /**
     * Makes an HTTP HEAD request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return CurlResponse
    **/
    function head($url, $vars = array()) {
        return $this->request('HEAD', $url, $vars);
    }
    
    /**
     * Makes an HTTP POST request to the specified $url with an optional array or string of $vars
     *
     * @param string $url
     * @param array|string $vars 
     * @param $type 0  normal  1 post files //fly
     * @return CurlResponse|boolean
    **/
    function post($url, $vars = array(), $type = 0) {
        return $this->request('POST', $url, $vars, $type);
    }
    
    /**
     * Makes an HTTP PUT request to the specified $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars 
     * @return CurlResponse|boolean
    **/
    function put($url, $vars = array()) {
        return $this->request('PUT', $url, $vars);
    }
    
    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
     *
     * Returns a CurlResponse object if the request was successful, false otherwise
     *
     * @param string $method
     * @param string $url
     * @param array|string $vars
     * @return CurlResponse|boolean
    **/
    function request($method, $url, $vars = array(), $type = 0) {
        $this->error = '';
        $this->request = curl_init();
        
        //@ $type=1
        if (!$type && is_array($vars)) $vars = http_build_query($vars, '', '&');
        
        
        $this->set_request_method($method);
        $this->set_request_options($url, $vars);
        $this->set_request_headers();
         
        $response = curl_exec($this->request);
        
        if ($response === false) {      	
        	$this->error = curl_errno($this->request).' - '.curl_error($this->request);
        } else {
          
           $response = $response; 
        }
        
        //$headers = curl_getinfo($this->request);  print_r($headers);exit;
        $this->_info = curl_getinfo($this->request); 
        //$this->_status = curl_getinfo($this->request,CURLINFO_HTTP_CODE); 
        $this->_status = $this->_info['http_code'];
        curl_close($this->request);
        
        return $response;
    }
    
    
	 public function getHttpStatus() 
	 { 
	 	return $this->_status; 
	 } 
	 
	 public function getInfo() 
	 { 
	 	return $this->_info; 
	 }
       
    /**
     * Formats and adds custom headers to the current request
     *
     * @return void
     * @access protected
    **/
    protected function set_request_headers() {
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key.': '.$value;
        }
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
    }
    
    /**
     * Set the associated CURL options for a request method
     *
     * @param string $method
     * @return void
     * @access protected
    **/
    protected function set_request_method($method) {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }
    
    /**
     * Sets the CURLOPT options for the current request
     *
     * @param string $url
     * @param string $vars
     * @return void
     * @access protected
    **/
    protected function set_request_options($url, $vars) {
        curl_setopt($this->request, CURLOPT_URL, $url);
        
        if (!empty($vars)) curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);
        
        # Set some default CURL options
        curl_setopt($this->request, CURLOPT_HEADER, false);
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->request, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->request, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->request, CURLOPT_TIMEOUT, 10); 
        
        
        if ($this->cookie_file) {
            curl_setopt($this->request, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($this->request, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        
        if ($this->follow_redirects) curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
        if ($this->referer) curl_setopt($this->request, CURLOPT_REFERER, $this->referer);
       
        # Set any custom CURL options
        foreach ($this->options as $option => $value) {
            curl_setopt($this->request, constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }

}

