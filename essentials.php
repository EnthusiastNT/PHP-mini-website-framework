<?php

/**
 * sanitize input from _GET or _POST
 *
 * @param string $name	input name
 * @param int $limit	0 for unlimited
 * @param bool $a2z		and digits and space [a-zA-Z0-9 ]
 *
 * @return string		sanitized input
 */
function func_GetInput($name, $limit = 0, $a2z = false)
{
	$value = '';
	if( isset($_GET[$name]) )
	{
		$value = $_GET[$name];
		$value = rawurldecode($value);  // urldecode would also replace + with Space
	}
	else if( isset($_POST[$name]) )
	{
		$value = $_POST[$name];
	}
	else
	{
		return '';
	}

	// only a-z and 0-9 and space
	if( $a2z )
	{
		$value = mb_ereg_replace('[^A-Za-z0-9 ]', '', $value);
		$value = mb_convert_case($value, MB_CASE_LOWER);
	}
	else
	{
		// basics
		$value = htmlspecialchars($value, ENT_QUOTES);  // replace < > with &lt; &gt;
		$value = mb_replace(["\r\n", "\r"], "\n", $value);
	}

	$value = trim($value);
	if( $limit > 0 )
	{
		$value = mb_substr($value, 0, $limit);
	}
	$value = trim($value);

	return $value;
}

/**
 * multi-byte version of str_replace, handles arrays in pattern and replacement
 *
 * @use: mb_replace(['<', '>', '"', '\''], ['&lt;', '&gt;', '&quot;', '&#39;'], $str);
 * @use: mb_replace("*search.this*", "replace with this", $str);
 * @use: mb_replace("[^a-zA-Z0-9 ]", "", $str, false);
 *
 * @param array|string	$pattern	needle, can be a regex
 * @param array|string	$replacement
 * @param string		$str		haystack
 * @param bool			$escape		treat @pattern like a literal string
 * @return string|bool				the replaced string, or false
 */
function mb_replace($pattern, $replacement, $str, $escape = true)
{
	// array of patterns?
	if( is_array($pattern) ) 
	{
		foreach ( $pattern as $key => $value )
		{
			// treat every character literally?
			if( $escape )
				$value = preg_quote($value, '/');  // . \ + * ? [ ^ ] $ ( ) { } = ! < > | : - #

			// replace with array, or always the same thing?
			if( is_array($replacement) )
				$str = mb_ereg_replace($value, $replacement[$key], $str);
			else
				$str = mb_ereg_replace($value, $replacement, $str);
		}
	}
	else
	{
		if( $escape )
			$pattern = preg_quote($pattern, '/');
		$str = mb_ereg_replace($pattern, $replacement, $str);
	}

    return $str;
} 

?>
