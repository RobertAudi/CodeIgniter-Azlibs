<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once 'Azauth.php';

/**
 * Azparagus
 *
 * @package Azparagus
 * @category CodeIgniter Library
 * @author Aziz Light
 * @link Azparagus
 * @version 0.0.1
 * @copyright Copyright (c) 2010, Aziz Light
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 **/
class Azparagus extends Azauth
{
	/**
	 * The Constructor!
	 * 
	 * @access public
	 **/
	public function __construct() 
	{
		parent::__construct();
		
		log_message('debug','Azparagus Library Initialized');
	} // End of __construct
	
	/*
	|------------------------------------------------------------------------
	| TODO - create user groups.
	| TODO - create user profile with user metadata.
	|------------------------------------------------------------------------
	*/
	
} // End of Azparagus class

/* End of file Azparagus.php */
/* Location: ./application/libraries/Azparagus.php */