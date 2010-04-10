<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Azlog Changelog Library
 *
 * @package Azlog
 * @category CodeIgniter Library
 * @author Aziz Light
 * @link Azlog
 * @version 0.0.1
 * @copyright Copyright (c) 2010, Aziz Light
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 **/
class Azlog
{
	/**
	 * The CodeIgniter object.
	 *
	 * @access private
	 * @var object
	 */
	private $_ci;
	
	/**
	 * The default logfile.
	 *
	 * @access private
	 * @var string
	 */
	private static $_logfile;
	
	/**
	 * The path where the log file(s) will be saved.
	 *
	 * @access private
	 * @var string
	 */
	private static $_log_path;
	
	/**
	 * The format of the date that will be prepended to each log.
	 *
	 * @access private
	 * @var string
	 */
	private static $_log_date_format;
	
	/**
	 * The format of the time that will be prepended to each log.
	 *
	 * @access private
	 * @var string
	 */
	private static $_log_time_format;
	
	/**
	 * Log attribute delimiter. Is used to seperate the user from the date, the message, etc.
	 * MUST BE 1 CHARACTER ONLY!
	 *
	 * @access private
	 * @var string
	 */
	private static $_delimiter;
	
	/**
	 * The maximum number of log lines per log file.
	 *
	 * @access private
	 * @var int
	 */
	private static $_max_logs;
	
	/**
	 * The max length of each log line.
	 *
	 * @access private
	 * @var int
	 */
	private static $_max_log_length;
	
	/**
	 * The name of the folder where the old log files will automatically be moved.
	 *
	 * @access private
	 * @var string
	 */
	private static $_old_dir_name;
	
// ------------------------------------------------------------------------
	
	/**
	 * The Constructor!
	 * 
	 * @access public
	 **/
	public function __construct()
	{
		log_message('debug','Azlog Library Initialized');
		
		// assign the CodeIgniter object to a private variable
		$this->_ci =& get_instance();
		
		$this->_config();
	} // End of __construct
	
// ------------------------------------------------------------------------
	
	/**
	 * Get the config items if they exist and initialize
	 * the static class variables accordingly.
	 *
	 * @access private
	 * @return void
	 */
	private function _config()
	{
		$log_path        = $this->_ci->config->item('_log_path'       ); // NOTE: The log_path must end with a forward slash (/)!!!!!
		$logfile         = $this->_ci->config->item('_logfile'        );
		$log_date_format = $this->_ci->config->item('_log_date_format');
		$delimiter       = $this->_ci->config->item('_delimiter'      );
		$max_logs        = $this->_ci->config->item('_max_logs'       );
		$max_log_length  = $this->_ci->config->item('_max_log_length' );
		$old_dir_name    = $this->_ci->config->item('_old_dir_name'   );
		
		self::$_log_path        = (!empty($log_path       )) ? $log_path        : APPPATH . 'logs/';
		self::$_logfile         = (!empty($logfile        )) ? $logfile         : 'Changelog.php';
		self::$_log_date_format = (!empty($log_date_format)) ? $log_date_format : 'Y-m-d';
		self::$_log_time_format = (!empty($log_time_format)) ? $log_time_format : 'H:i:s';
		self::$_delimiter       = (!empty($delimiter      )) ? $delimiter       : ';';
		self::$_max_logs        = (!empty($max_logs       )) ? $max_logs        : 350;
		self::$_max_log_length  = (!empty($max_log_length )) ? $max_log_length  : 140;
		self::$_old_dir_name    = (!empty($old_dir_name   )) ? $old_dir_name    : '_old';
		
		return;
	} // End of _config
	
// ------------------------------------------------------------------------
	
	/**
	 * Log a message.
	 *
	 * @access public
	 * @param string $type : The type of the message.
	 * @param string $m : The message.
	 * @param string $logfile : Optional: the name of the logfile. JUST THE NAME NOT THE PATH!!! The extension is not required but can be part of the string. EXTENSION MUST BE PHP!!!
	 * @return bool
	 */
	public function log($type, $m, $logfile = '')
	{
		if (!$this->_is_valid_log($type, $m))
			return FALSE;
		
		if (!$logfile = $this->_validate($logfile))
		{
			if (empty($logfile))
				$logfile = self::$_logfile;
			else
				return FALSE;
		}
		
		$type = strtoupper($type);
		$logfile = self::$_log_path . $logfile;
		
		// FIXME - The library is dependant on Azauth. Provide an easy way to change that. Idea: create a method to get the user.
		// -Getting the user's username--------------------------------------------
		$this->_ci->load->library('Azauth');
		$user = $this->_ci->azauth->get_user('username');
		if ($user === NULL)
			return FALSE;
		// ------------------------------------------------------------------------
		
		$message = '';
		if (!file_exists($logfile))
		{
			$message .= "<" . "?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?" . ">\n\n";
		}
		elseif ($this->_count_lines($logfile) >= (self::$_max_logs + 3))
		{
			if (!$this->_lock($logfile))
				return FALSE;
			$message .= "<" . "?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?" . ">\n\n";
		}
		
		if (!$handle = @fopen($logfile, 'ab'))
			return FALSE;
		
		// -The log line-----------------------------------------------------------
		$message .= $user . ';';
		$message .= date(self::$_log_date_format) . ';';
		$message .= date(self::$_log_time_format) . ';';
		$message .= $type . ';';
		$message .= $m;
		$message .= "\n";
		// -End of log line--------------------------------------------------------
		
		flock($handle, LOCK_EX);
		fwrite($handle, $message);
		flock($handle, LOCK_UN);
		fclose($handle);
		
		@chmod($logfile, 0666);
		return TRUE;
	} // End of log
	
// ------------------------------------------------------------------------
	
	/**
	 * Gets the last logs of the log file.
	 *
	 * @access public
	 * @param int $number : The number of logs to get.
	 * @param string $logfile : The name of the logfile. Enter a name only if you didn't specify the name in the config file AND you don't want to use the default log file.
	 * @return array : The array of logs
	 */
	public function get($number = 10, $logfile = '')
	{
		// NOTE - I can probably make this method more efficient if I use the _count_lines() method more intelligentely...
		
		if (!$logfile = $this->_validate($logfile))
		{
			if (empty($logfile))
				$logfile = self::$_logfile;
			else
				return FALSE;
		}
		$logfile = self::$_log_path . $logfile;
		
		// This is the max sum of the chars of the last logs.
		$last_logs_size = $this->_get_max_log_line_size() * $number;
		
		if (!$handle = @fopen($logfile, 'r'))
			return FALSE;
		
		/*get the file size with a trick*/
		fseek($handle, 0, SEEK_END);
		$filesize = ftell($handle) - 80; // We don't want to count the 2 lines of php that restrict direct access to the log file.
		
		$position = - min($last_logs_size, $filesize);
		
		fseek($handle, $position, SEEK_END);
		$chunk = '';
		while (!feof($handle))
		{
			$chunk .= fgets($handle, $last_logs_size);
		}
		
		$logs_array = explode("\n", $chunk);
		$logs_array = array_reverse($logs_array);
		array_shift($logs_array);
		
		$logs = array();
		$max = min($number, count($logs_array));
		for ($i = 0, $tmp = array(); $i < $max; $i++)
		{
			$tmp = explode(self::$_delimiter, $logs_array[$i]);
			$logs[] = $this->_get_log_attributes($tmp);
		}
		
		return $logs;
	} // End of get
	
// ------------------------------------------------------------------------
	
	/**
	 * Check if the if the log type and the log message provided are valid
	 *
	 * @access private
	 * @param string $type
	 * @param string $message
	 * @return bool
	 */
	private function _is_valid_log($type, $message)
	{
		if (empty($type) || empty($message))
			return FALSE;
		
		// -Checking the type------------------------------------------------------
		$type = strtoupper($type);
		$valid_types = array('CREATE', 'UPDATE', 'DELETE', 'ACTIVATE', 'DEACTIVATE');
		if (!in_array($type, $valid_types))
			return FALSE;
		
		// -Checking the message---------------------------------------------------
		if (strrchr($message, self::$_delimiter) || strlen($message) > self::$_max_log_length)
			return FALSE;
		
		return TRUE;
	} // End of _is_valid_log
	
// ------------------------------------------------------------------------
	
	/**
	 * Verify that the file provided has a
	 * valid logfile filename and add an extension 
	 * if the file doesn't have one.
	 *
	 * @access private
	 * @param string $file : the file/filename.
	 * @return bool|string : returns the filename (with the extension if it wasn't there before) or FALSE.
	 */
	private function _validate($file)
	{
		if ((bool)preg_match('/^(?:[a-zA-Z0-9]+(?:[-_]?[a-zA-Z0-9]+)*)(\.php)?$/', $file, $matches))
		{
			$extension = explode('.', $file);
			if (!isset($extension[1]))
				$file .= '.php';
			return $file;
		}
		else
		{
			return FALSE;
		}
	} // End of _validate
	
// ------------------------------------------------------------------------
	
	/**
	 * Count number of lines in a file.
	 *
	 * @access private
	 * @param string filepath : The name of the file
	 * @return int : The number of lines
	 */
	private function _count_lines($filepath)
	{
		$handle = fopen( $filepath, "r" );
		$count = 0;
		while(fgets($handle))
		{
			$count++;
		}
		fclose($handle);
		return $count;
	} // End of _count_lines
	
// ------------------------------------------------------------------------
	
	/**
	 * Lock a log file:
	 * - Creates the old logs folder if it doesn't exist (by default: [logs_folder]_old).
	 * - Moves the file to the old logs folder.
	 * - Renames the logs file to a uniquely timestamped name.
	 * - Changes the permissions of the logs file to read-only (644).
	 *
	 * @access private
	 * @param string $filepath : The path of the file along with the file's name and its extension
	 * @return bool
	 */
	private function _lock($filepath)
	{
		$lock_stamp = date('yzWNjGisn_');
		
		if (!is_dir(self::$_log_path . self::$_old_dir_name))
		{
			if (!@mkdir(self::$_log_path . self::$_old_dir_name, 0755))
				return FALSE;
		}
		
		// extract file name and seperate the folder path, the file name and the extension
		if ((bool)preg_match('/^(\/?(?:\.{1,2}|[\w\S-])+\/)*([a-zA-Z0-9]+(?:[-_]?[a-zA-Z0-9]+)*)(\.php)$/', $filepath, $matches))
		{
			$new_filepath = $matches[1] . self::$_old_dir_name . '/' . $lock_stamp . $matches[2] . $matches[3];
			if (!@rename($filepath, $new_filepath))
				return FALSE;
			chmod($new_filepath, 0644);
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	} // End of _lock
	
// ------------------------------------------------------------------------
	
	/**
	 * Calculate the maximum possible length of a full log line.
	 *
	 * @access private
	 * @return int : The maximum length.
	 */
	private function _get_max_log_line_size()
	{
		// FIXME - Not flexible.
		$max_message_length   = self::$_max_log_length + 1;
		$max_date_length      =  8;
		$max_time_length      = 10;
		$max_username_length  = 40;
		$max_type_name_length = 10;
		
		$max_log_length = $max_message_length + $max_date_length + $max_time_length + $max_username_length + $max_type_name_length;
		return $max_log_length;
	} // End of _get_max_log_line_size
	
// ------------------------------------------------------------------------
	
	/**
	 * Reformats a log array to have each chunk associated with it's attribute name
	 *
	 * @access private
	 * @param array $log : the log array
	 * @return array : the new log array
	 */
	private function _get_log_attributes($log)
	{
		// FIXME - Not flexible.
		// the log attributes
		$log_attr = array(
			'user',
			'date',
			'time',
			'type',
			'message',
		);
		
		$i = 0;
		$log_array = array();
		foreach ($log as $chunk)
		{
			$log_array[$log_attr[$i]] = $chunk;
			$i++;
		}
		unset($i, $log_attr);
		return $log_array;
	} // End of _get_log_attributes
	
} // End of Azlog class

/* End of file Azlog.php */
/* Location: ./application/libraries/Azlog.php */