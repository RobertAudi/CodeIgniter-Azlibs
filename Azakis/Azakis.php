<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Azakis Akismet Library
 *
 * @package Azakis
 * @category CodeIgniter Library
 * @author Aziz Light
 * @link http://github.com/AzizLight/Azakis
 * @version 0.0.1
 * @copyright Copyright (c) 2009, Aziz Light
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 **/
class Azakis
{
// ------------------------------------------------------------------------
// Class Variables
// ------------------------------------------------------------------------
	
	/**
	 * The current version of akismet.
	 *
	 * @access private
	 * @var string
	 **/
	private $_akismet_version;
	
	/**
	 * akismet server
	 *
	 * @access private
	 * @var string
	 **/
	private $_akismet_server;
	
	/**
	 * keys to be ignored when parsing the $_SERVER array
	 *
	 * @access private
	 * @var array
	 **/
	private $_ignore;
	
	/**
	 * Akismet API key.
	 *
	 * @access private
	 * @var string
	 **/
	private $_api_key;
	
	/**
	 * The home URL of the instance making the request
	 *
	 * @access private
	 * @var string
	 **/
	private $_home_url;
	
	/**
	 * The user-agent of the application. Needs to have the following form (for conventions' sake):
	 * Application Name/Version | Library Name/Version
	 *
	 * @access private
	 * @var string
	 **/
	private $_app_user_agent;
	
// ------------------------------------------------------------------------
// End of Class Variables
// ------------------------------------------------------------------------
	
	/**
	 * The Constructor!
	 * 
	 * @access public
	 **/
	public function __construct() {
		log_message('debug', 'Azakis Akismet Library Initialized');
		
		$this->_akismet_version = '1.1';
		$this->_akismet_server  = 'rest.akismet.com';
		$this->_ignore = array(
							'HTTP_COOKIE',
							'HTTP_X_FORWARDED_FOR',
							'HTTP_X_FORWARDED_HOST',
							'HTTP_MAX_FORWARDS',
							'HTTP_X_FORWARDED_SERVER',
							'REDIRECT_STATUS',
							'SERVER_PORT',
							'PATH',
							'DOCUMENT_ROOT',
							'SERVER_ADMIN',
							'QUERY_STRING',
							'PHP_SELF',
							'argv'
						);
		
		// Insert your Wordpress.com API here
		$this->_api_key        = 'be0560300547';
		// Insert the url of your blog or site here
		$this->_home_url       = 'http://www.example.com/';
		// Replace Application Name by your Application's name and 
		// Version by your Application's Verion. 
		// You can Also remove the first part of the string to keep only Azakis/0.01
		$this->_app_user_agent = 'CIMBlE/0.02 | Azakis/0.01';
	} // End of __construct
	
// ------------------------------------------------------------------------
	
	/**
	 * Checks if a comment is spam.
	 *
	 * @access public
	 * @param array $comment
	 * @return bool
	 **/
	public function is_spam($comment)
	{
		// if the Wordpress.com API key provided is not valid,
		// the comment will not be checked by akismet and will just be approved!
		if (!$this->_is_valid_api_key())
		{
			log_message('error','The Wordpress.com API key provided is not valid. Comment approved without being checked by Akismet!');
			return FALSE;
		}
		
		// generate the request string
		$request = $this->_setup_request($comment);
		// get a response from the Akismet server using the request string
		$response = $this->_get_response($request, 'comment-check');
		
		return ($response == 'true');
	} // End of is_spam
	
// ------------------------------------------------------------------------
	
	/**
	 * Submit a missed spam to Akismet
	 *
	 * @access public
	 * @param array $comment
	 * @return bool
	 **/
	public function submit_spam($comment)
	{
		// if the Wordpress.com API key provided is not valid,
		// the comment will not be checked by akismet and will just be approved!
		if (!$this->_is_valid_api_key())
		{
			log_message('error','The Wordpress.com API key provided is not valid. Comment approved without being checked by Akismet!');
			return FALSE;
		}
		
		// generate the request string
		$request = $this->_setup_request($comment);
		// get a response from the Akismet server using the request string
		$response = $this->_get_response($request, 'submit-spam');
		
		return TRUE;
	} // End of submit_spam
	
// ------------------------------------------------------------------------
	
	/**
	 * Submit to Akismet a non-spam comment that was considered a spam
	 *
	 * @access public
	 * @param array $comment
	 * @return bool
	 **/
	public function submit_ham($comment)
	{
		// if the Wordpress.com API key provided is not valid,
		// the comment will not be checked by akismet and will just be approved!
		if (!$this->_is_valid_api_key())
		{
			log_message('error','The Wordpress.com API key provided is not valid. Comment approved without being checked by Akismet!');
			return FALSE;
		}
		
		// generate the request string
		$request = $this->_setup_request($comment);
		// get a response from the Akismet server using the request string
		$response = $this->_get_response($request, 'submit-ham');
		
		return TRUE;
	} // End of submit_ham
	
// ------------------------------------------------------------------------
// Private Methods
// ------------------------------------------------------------------------
	
	/**
	 * Get a response from the Akismet server
	 *
	 * @access private
	 * @param string $request
	 * @param string $path
	 * @return string
	 **/
	private function _get_response($request, $path)
	{
		// set the host variable
		$host  = ((!empty($this->_api_key)) ? $this->_api_key : NULL);
		$host .= '.' . $this->_akismet_server;
		
		$http_request  = "POST /{$this->_akismet_version}/{$path} HTTP/1.0\r\n";
		$http_request .= "Host: {$host}\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
		$http_request .= "Content-Length: " . strlen($request) . "\r\n";
		$http_request .= "User-Agent: {$this->_app_user_agent}\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;
		
		$response = '';
		if ( false !== ($fs = @fsockopen($host, 80, $errno, $errstr, 3)))
		{
			fwrite($fs, $http_request);
			while (!feof($fs))
			{
				$response .= fgets($fs, 1160); // One TCP-IP packet
			}
			fclose($fs);
			$response = explode("\r\n\r\n", $response, 2);
		}
		return $response[1];
	} // End of _get_response
	
// ------------------------------------------------------------------------
	
	/**
	 * Check if the provided api key is valid
	 *
	 * @access private
	 * @return bool
	 **/
	private function _is_valid_api_key()
	{
		$key_check = $this->_get_response('key=' . $this->_api_key . '&blog=' . $this->_home_url, 'verify-key');
			
		return ($key_check == "valid");
	} // End of _is_valid_api_key
	
// ------------------------------------------------------------------------
	
	/**
	 * Add the necessary keys/values to the provided comment array and
	 * generate the request string using that array
	 *
	 * @access private
	 * @param array $comment 
	 * @param string $type 
	 * @return string
	 **/
	private function _setup_request($comment, $type = 'comment')
	{
		// setup the $post array
		$post['key']                  = $this->_api_key;
		$post['blog']                 = $this->_home_url;
		$post['comment_type']         = $type;
		$post['comment_author']       = $comment['author_name'];
		$post['comment_author_email'] = $comment['author_email'];
		$post['comment_author_url']   = $comment['author_website'];
		$post['comment_content']      = $comment['body'];
		
		// add info to the $post array
		foreach($_SERVER as $key => $value)
		{
			// there is in the $_SERVER array some 'sensitive' info that
			// we don't want to send to Akismet
			if(!in_array($key, $this->_ignore))
			{
				// Rename the REMOTE_ADDR, HTTP_REFERER and HTTP_USER_AGENT
				// to user_ip, referrer and user_ip respectively
				switch ($key) {
					case 'REMOTE_ADDR':
						$key = 'user_ip';
					break;
					case 'HTTP_REFERER':
						$key = 'referrer';
					break;
					case 'HTTP_USER_AGENT':
						$key = 'user_agent';
					break;
				}
				// add the info to the $post array
				$post[$key] = $value;
			}
		}
		
		$request = array();
		// generate the request string
		foreach ($post as $key => $value) {
			$request[] = $key . '=' . $value;
		}
		$request = implode('&',$request);
		
		return $request;
	} // End of _setup_request
	
} // End of Azakis class

/* End of file Azakis.php */
/* Location: ./application/libraries/Azakis.php */