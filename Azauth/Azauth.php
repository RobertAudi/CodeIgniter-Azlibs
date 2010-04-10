<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Azauth CodeIgniter Authentication Library
 * 
 * @package Azauth
 * @category CodeIgniter Library
 * @author Aziz Light
 * @link http://github.com/AzizLight/Azauth
 * @version 0.0.2
 * @copyright Copyright (c) 2009, Aziz Light
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 **/
class Azauth
{
	/**
	 * private variable that will be used to store the CodeIgniter object
	 *
	 * @access private
	 * @var object
	 **/
	private $_ci;
	
// ------------------------------------------------------------------------
	
	/**
	 * The Constructor!
	 */
	public function __construct()
	{
		log_message('debug','Azauth CodeIgniter Authentication Library Initialized');
		
		// assign the CodeIgniter object to a private variable
		$this->_ci =& get_instance();
		
		// load necessary libraries and helpers
		$this->_ci->load->library('session');
		$this->_ci->load->helper('url');
		$this->_ci->load->helper('email');
		$this->_ci->load->helper('string');
	}
	
// ------------------------------------------------------------------------
	
	/**
	 * Add a new user to the database
	 *
	 * @access public
	 * @param string $username 
	 * @param string $password 
	 * @param string $email 
	 * @return bool
	 **/
	public function register($username = NULL, $password = NULL, $email = NULL)
	{
		// the username, email and password are all required for the user to register successfully
		if (empty($username) || empty($password) || empty($email))
			return FALSE;
		
		// encrypt the password and generate a salt
		$vault = $this->_encrypt($password);
		
		// assign the password and the salt to two variables
		$password = $vault['password'];
		$salt     = $vault['salt'];
		
		// prepare the data array for insertion in the database
		$data = array(
					'username' => $username,
					'password' => $password,
					'salt'     => $salt,
					'email'    => $email
				);
		
		// finally add the user to the users table in the database
		$this->_ci->db->insert('users', $data);
		
		// and return TRUE to tell to the controllers that everything worked normally
		return TRUE;
	} // End of register
	
// ------------------------------------------------------------------------
	
	/**
	 * Logs a user in
	 *
	 * @access public
	 * @param string $username 
	 * @param string $password 
	 * @return bool
	 **/
	public function login($username = NULL, $password= NULL)
	{
		// both the username and the password are required to log a user in successfully.
		if (empty($username) || empty($password))
			return FALSE;
		
		// the password provided must be correct!
		if ($this->_check_password($username, $password) !== TRUE)
			return FALSE;
		
		// get from the db the necessary info about of the user so that we can stock it in the session.
		$this->_ci->db->where('username', $username);
		$query = $this->_ci->db->get('users');
		$row = $query->row();
		
		// TODO - add the group id to the session too
		// prepare the data array that we will store in the session.
		$data = array(
					'username'  => $username,
					'email'     => $row->email,
					'user_id'   => $row->id,
					'logged_in' => TRUE
				);
		
		// finally set the session.
		$this->_ci->session->set_userdata($data);
		return TRUE;
	} // End of login
	
// ------------------------------------------------------------------------
	
	/**
	 * Check if a user is logged in or not
	 *
	 * @access public
	 * @return bool
	 **/
	public function logged_in()
	{
		// check if the logged_in variable is set in the session
		$session_data = $this->_ci->session->userdata('logged_in');
		if (isset($session_data))
		{
			// if it is, return its value
			return $this->_ci->session->userdata('logged_in');
		}
		else
		{
			// if it's not, return false (which means no one is logged in)
			return FALSE;
		}
	} // End of logged_in
	
// ------------------------------------------------------------------------
	
	/**
	 * Logs out any user that might be logged in
	 *
	 * @access public
	 * @return void
	 **/
	public function logout()
	{
		// destroy all the session variables
		$this->_ci->session->sess_destroy();
	} // End of logout
	
// ------------------------------------------------------------------------
	
	/**
	 * Retrieve information about the user currently logged in
	 *
	 * @access public
	 * @return array
	 **/
	public function get_user($data = NULL)
	{
		if($this->logged_in())
		{
			if ($data == 'username' || $data == 'email' || $data == 'user_id')
			{
 				return $this->_ci->session->userdata($data);
			}
			else
			{
				$user['username'] = $this->_ci->session->userdata('username');
				$user['email']    = $this->_ci->session->userdata('email');
				$user['user_id']  = $this->_ci->session->userdata('user_id');
				return $user;
			}
		}
		else
		{
			return NULL;
		}
	} // End of get_user
	
// ------------------------------------------------------------------------
// Private Methods
// ------------------------------------------------------------------------
	
	/**
	 * Encrypts the password and then check in the database if
	 * the password of the user passed as argument is the same one.
	 *
	 * @access private
	 * @param string $username 
	 * @param string $password 
	 * @return bool
	 **/
	private function _check_password($username, $password)
	{
		$this->_ci->db->select('password, salt');
		$this->_ci->db->where('username', $username);
		$this->_ci->db->limit(1);
		$query = $this->_ci->db->get('users');
		
		if ($query->num_rows() == 1)
		{
			$result = $query->row();
			$encrypted = $this->_encrypt($password, $result->salt);
			if ($result->password === $encrypted['password'])
			{
				return TRUE;
			}
		}
		return FALSE;
	} // End of _check_password
	
// ------------------------------------------------------------------------
	
	/**
	 * Generate a unique salt.
	 *
	 * @access private
	 * @return string
	 **/
	private function _generate_salt()
	{
		// NOTE: This only works in PHP5
		return sha1(uniqid(mt_rand(), TRUE));
	} // End of _generate_salt
	
	/**
	 * Encrypt a password and generation a salt is one is not provided.
	 *
	 * @access private
	 * @param string $password 
	 * @param string $salt 
	 * @return array
	 **/
	private function _encrypt($password, $salt = NULL)
	{
		if ($salt === NULL)
			$salt = $this->_generate_salt();
		
		$key = $this->_ci->config->item('encryption_key');
		$encrypted = sha1($key.$password.$salt);
		return array('password' => $encrypted, 'salt' => $salt);
	} // End of _encrypt
	
} // End of Azauth Class

/* End of file Azauth.php */
/* Location: ./application/libraries/Azauth.php */