<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

# ------------------------------------------------------------------------
# NOTES:
# ------------------------------------------------------------------------
#
#  This library, like all my libraries, uses functions from the application
#  helper available on GitHub. Without the application helper and the 
 # validation helper this library will NOT work!
#
# ------------------------------------------------------------------------

/**
 * Azbraz Breadcrumbs Library
 *
 * @package Azbraz
 * @category CodeIgniter Library
 * @author Aziz Light
 * @link Azbraz
 * @version 0.0.1
 * @copyright Copyright (c) 2009, Aziz Light
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 **/
class Azbraz
{
	/**
	 * The CodeIgniter object.
	 *
	 * @access private
	 * @var object
	 */
	private $_ci;
	
	/**
	 * The segments defined in the breadcrumbs.php config file.
	 *
	 * @access private
	 * @var array
	 */
	private $_segments;
	
	/**
	 * First this variable will contain the breadcrumbs array containing all the segments.
	 * Then it will contain the formated breadcrumbs string that will be returned to the controller.
	 *
	 * @access private
	 * @var array|string
	 */
	private $_breadcrumbs;
	
	/**
	 * The seperator that will appear between each segment.
	 *
	 * @access private
	 * @var string
	 */
	private $_seperator;
	
	/**
	 * The max length of each segment.
	 *
	 * @access private
	 * @var int
	 */
	private $_length;
	
// ------------------------------------------------------------------------
	
	/**
	 * The Constructor!
	 * 
	 * @access public
	 **/
	public function __construct()
	{
		log_message('debug','Azbraz Breadcrumbs Library Initialized');
		$this->_ci =& get_instance();
		
		$this->_ci->config->load('breadcrumbs');
		$this->_segments  = $this->_ci->config->item('azbraz_segments');
		$this->_seperator = $this->_ci->config->item('azbraz_seperator');
		$this->_length    = $this->_ci->config->item('azbraz_max_segment_length');
		
		$this->_ci->load->library('firephp');
	} // End of __construct
	
// ------------------------------------------------------------------------
	
	/**
	 * The Destructor!
	 *
	 * @access public
	 */
	public function __destruct()
	{
		if (isset($this->_breadcrumbs))
			unset($this->_breadcrumbs);
	} // End of __destruct
	
// ------------------------------------------------------------------------
	
	/**
	 * Adds a new custom segment at the end of the breadcrumbs array
	 *
	 * @access public
	 * @param string $title : the title of the segment
	 * @param string $url : the url of the segment the segment is referring to
	 * @param array $breadcrumbs : the breadcrumbs array. If set to NULL this method will just return one segment array without merging it to anything
	 * @return array : the new breadcrumbs array with the custom segment at the end or just the segment array
	 */
	public function new_segment($title, $url, $breadcrumbs = array())
	{
		if (!is_valid_string($title) && !is_valid_ci_url($url) && !is_valid_slug($url) && !is_valid_number($url) && !is_array($breadcrumbs) && !empty($breadcrumbs))
		{
			if (!empty($breadcrumbs))
				return FALSE;
			return $breadcrumbs;
		}
		
		if (!empty($breadcrumbs) && !is_valid_ci_url($url))
		{
			$last = $breadcrumbs[count($breadcrumbs) - 1]['url'];
			$url  = strip_trailing_slash($last) . '/' . $url;
		}
		
		$segment = array(
			'title' => $title,
			'url' => $url
		);
		$breadcrumbs[] = $segment;
		return $breadcrumbs;
	} // End of new_segment
	
// ------------------------------------------------------------------------
	
	/**
	 * Generate the breadcrumbs HTML block.
	 *
	 * @access public
	 * @param string $bc_string : The breadcrumbs string.
	 * @param array $custom_segments : custom breadcrumbs segments (generated via the new_segment method or created manually).
	 * @return string
	 */
	public function generate($bc_string = '', $custom_segments = NULL)
	{
		if (is_array($bc_string))
		{
			if ($custom_segments === NULL)
			{
				$custom_segments = $bc_string;
				$bc_string = '';
			}
			else
			{
				log_message('error','Azbraz::generate() : The parameters passed are not valid. If the first parameter is an array, the second MUST be NULL!!!');
				return FALSE;
			}
		}
		
		if ($this->_get_segments($bc_string) === FALSE && ((!is_array($custom_segments) && $custom_segments !== NULL)))
		{
			# You need to supply at least a breadcrumbs query or valid custom segments array!
			log_message('error','Azbraz::generate() : You supplied an empty breadcrumbs string and (a) custom segment(s) that is(/are) not of type array!');
			return FALSE;
		}
		
		if (is_array($custom_segments) && !empty($custom_segments))
		{
			$breadcrumbs = array_merge($this->_breadcrumbs, $custom_segments);
			if ($this->_to_breadcrumbs($breadcrumbs))
				return $this->_breadcrumbs;
		}
		else
		{
			if ($this->_to_breadcrumbs($this->_breadcrumbs))
				return $this->_breadcrumbs;
		}
	} // End of generate
	
// ------------------------------------------------------------------------
// Private Methods
// ------------------------------------------------------------------------
	
	/**
	 * Convert the breadcrumbs string to an array of segments
	 *
	 * @access private
	 * @param string $bc_string
	 * @return bool
	 */
	private function _get_segments($bc_string)
	{
		if (empty($bc_string))
		{
			$bc_string = $this->_detect_segments();
		}
		elseif (!is_valid_breadcrumbs_string($bc_string))
		{
			$this->_breadcrumbs = array();
			return FALSE;
		}
		
		$seg_titles = explode('-', $bc_string);
		$this->_breadcrumbs = array();
		
		foreach ($seg_titles as $segment)
		{
			if (!array_key_exists($segment, $this->_segments))
			{
				log_message('error','The ' . $segment . ' segment is not set in the breadcrumbs.php config file!');
				continue;
			}
			array_push($this->_breadcrumbs, $this->_segments[$segment]);
		}
		// return $breadcrumbs;
		return TRUE;
	} // End of _get_segments
	
// ------------------------------------------------------------------------
	
	/**
	 * Capture the URI segments and detect the corresponding breadcrumbs segments
	 *
	 * @access private
	 * @return string
	 */
	private function _detect_segments()
	{
		$uri = $this->_ci->uri->segment_array();
		
		if (empty($uri))
		{
			$bc_string = $this->_ci->config->item('default_segment');
		}
		else
		{
			// The first thing we need to do is to do is to treat each uri segment
			// so that it could possibly match the 'url' attribute of each segment
			// set up in the breadcrumbs.php config file
			$i = 0;
			$segments = array();
			$temp_segment = '';
			foreach ($uri as $uri_segment)
			{
				if ($uri_segment != $uri[1])
					$temp_segment .= '/';
				
				$temp_segment .= $uri_segment;
				$segments[$i]  = $temp_segment;
				$i++;
			}
			
			$_seg_keys = array_keys($this->_segments);
			$bc_string = '';
			foreach ($segments as $bc_segment)
			{
				$i = 0;
				foreach ($this->_segments as $seg)
				{
					if (in_array($bc_segment, $seg))
					{
						$bc_string .= $_seg_keys[$i] . '-';
					}
					$i++;
				}
			}
			
			// remove the extra dash at the end of the string
			if ($bc_string[strlen($bc_string) - 1] === '-')
				$bc_string = substr_replace($bc_string, '', -1, 1);
		}
		return $bc_string;
	} // End of _detect_segments
	
// ------------------------------------------------------------------------
	
	/**
	 * Convert the breadcrumbs array to an HTML block (the actual breadcrumbs)
	 *
	 * @access private
	 * @param array $bc_array : The breadcrumbs array.
	 * @return bool
	 */
	private function _to_breadcrumbs($bc_array)
	{
		$i = 0;
		$this->_breadcrumbs = '';
		
		foreach ($bc_array as $segment)
		{
			// Don't display a seperator before the first breadcrumb element
			$temp_seperator = ($i == 0) ? '' : ' ' . $this->_seperator . ' ';
			if ($i == 0)
			{
				$temp_seperator = '';
				$i++;
			}
			else
			{
				$temp_seperator = ' ' . $this->_seperator . ' ';
			}
			
			$this->_breadcrumbs .= $temp_seperator;
			$this->_breadcrumbs .= '<a href="' . site_url($segment['url']) . '" title="' . $segment['title'] . '">';
			$this->_breadcrumbs .= truncate($segment['title'], $this->_length) . '</a>';
		}
		return TRUE;
	} // End of to_breadcrumb
	
} // End of Azbraz class

/* End of file Azbraz.php */
/* Location: ./application/libraries/Azbraz.php */