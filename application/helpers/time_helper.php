<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

define('TIMEZONE', 'Asia/Singapore');

if ( ! function_exists('datenow'))
{
	function datenow($format='Y-m-d H:i:s')
	{
		$current_datetime = new DateTime(null, new DateTimeZone(TIMEZONE));
		return $current_datetime->format($format);
	}
}

if ( ! function_exists('dateformat'))
{
	function dateformat($date, $format='Y-m-d H:i:s')
	{
		if($date == '0000-00-00 00:00:00' || $date == '0000-00-00')
		{
			return false;
		}

		$date = new DateTime($date, new DateTimeZone(TIMEZONE));
		return $date->format($format);
	}
}



// ------------------------------------------------------------------------

/* End of file time_helper.php */
/* Location: ./senta/application/helpers/time_helper.php */