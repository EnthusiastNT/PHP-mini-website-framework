<?php

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
