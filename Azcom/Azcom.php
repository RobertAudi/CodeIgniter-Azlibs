<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Azcom Comments Library
 *
 * @package Azcom
 * @category CodeIgniter Library
 * @author Aziz Light
 * @link Azcom
 * @version 0.0.1
 * @copyright Copyright (c) 2009, Aziz Light
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 **/
class Azcom
{
	
// ==================
// = Public Methods =
// ==================
	
	/**
	 * The CodeIgniter Object
	 *
	 * @var object
	 */
	private $_ci;
	
	/**
	 * All the parent comments
	 *
	 * @var array
	 */
	public $parents  = array();
	
	/**
	 * All the child comments
	 *
	 * @var string
	 */
	public $children = array();
	
	/**
	 * All the comments after being edited.
	 * Children are next to their parents in this array.
	 *
	 * @var array
	 */
	private $_comments = array();
	
// ------------------------------------------------------------------------
	
	/**
	 * The Constructor!
	 * 
	 * @access public
	 **/
	public function __construct() 
	{
		log_message('debug','Azcom Comments Library Initialized');
		
		// assign the CodeIgniter object to a private variable
		// $this->_ci =& get_instance();
	} // End of __construct
	
	
// ------------------------------------------------------------------------
	
	
	public function init($comments)
	{
		if (!is_array($comments))
		{
			log_message('error','Azcom::init() : You need to supply an array of comments! You supplied a(n) ' . gettype($comments));
			return FALSE;
		}
		elseif (empty($comments))
		{
			log_message('error','Azcom::init() : You supplied an empty array!!');
			return FALSE;
		}
		
		foreach ($comments as $comment)
		{
			if ($comment->parent_id === NULL)
			{
				$this->parents[$comment->id][] = $comment;
			}
			else
			{
				$this->children[$comment->parent_id][] = $comment;
			}
		}
		
		$this->_setup($comments);
		
		return $this->_comments;
	} // End of init
	
// ------------------------------------------------------------------------
	
	
	public function format($comment)
	{
		
	} // End of format
	
// ------------------------------------------------------------------------
	
	/**
	 * Seperate the comments by status (Ham and Spam)
	 *
	 * @access public
	 * @param array $comments
	 * @return array
	 **/
	public function categorize($comments)
	{
		$ham = array();
		$spam = array();
		
		foreach ($comments as $comment) {
			if ($comment->is_spam == 1)
			{
				array_push($spam, $comment);
			}
			elseif ($comment->spam == 0)
			{
				array_push($hpam, $comment);
			}
		}
		
		return array('ham' => $ham, 'spam' => $spam);
	} // End of categorize
	
// ------------------------------------------------------------------------
// Private Methods
// ------------------------------------------------------------------------
	
	
	private function _setup($comment, $depth = 0)
	{
		foreach ($comment as $c)
		{
			$c->depth = $depth;
			$this->_comments[] = $c;
			
			if (isset($this->children[$c->id]))
				$this->_setup($this->children[$c->id], $depth + 1);
		}
		
		return TRUE;
	} // End of _setup
	
} // End of Azcom class

/* End of file Azcom.php */
/* Location: ./application/libraries/Azcom.php */