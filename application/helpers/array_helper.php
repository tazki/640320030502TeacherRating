<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Prearr
 *
 * Lets you determine whether an array index is set and whether it has a value.
 * If the prearr is empty it returns FALSE
 *
 * @access	public
 * @param	array
 */
if ( ! function_exists('prearr'))
{
	function prearr($array)
	{
		if(is_array($array) || is_object($array))
		{
			echo'<pre><div style="border:1px solid #990000; padding:20px; margin:0 0 10px;">';
			print_r($array);
			echo'</div></pre>';
		}
		else
		{
			echo $array.' is not an array!';
		}
	}
}

// ------------------------------------------------------------------------

/* End of file array_helper.php */
/* Location: ./senta/application/helpers/array_helper.php */