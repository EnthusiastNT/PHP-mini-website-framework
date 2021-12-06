<?php

/*
 *  helper, string and html functions
 */

	// general constants
	define('KB', 1024);
	define('MB', 1048576);  // 1024*1024
	define('GB', 1073741824);  // 1024*1024*1024
	define('TB', 1099511627776);  // 1024*1024*1024*1024

	define('mn', 60);
	define('hr', 3600);  // 60*60
	define('day', 86400);  // 24*60*60
	define('week', 604800);  // 7*24*60*60

	define('stamp', 'Y-m-d H:i:s');

// debug before redirect
$g_stopRedirect = 0;

// --------------------------------------------------
function func_GetInput($name, $limit = 0, $how = 'basic')
{
	// get input from $_GET $_POST $_COOKIE, sanitize and shorten
	$value = '';
	if ( 'cookie' == $how && isset($_COOKIE[$name]) )
	{
		$value = $_COOKIE[$name];
		$how = 'a2z';  // like a token
	}
	else if ( isset($_POST[$name]) )
	{
		$value = $_POST[$name];
	}
	else if ( isset($_GET[$name]) )
	{
		$value = $_GET[$name];
		$value = rawurldecode($value);  // urldecode would also replace + with Space
	}
	else
	{
		return '';
	}


	if ( 'pass' == $how )
	{
		// keep password exactly as entered, all unicode characters allowed
		//  (password is not stored and never diplayed)
		return $value;
	}
	else if ( 'a2z' == $how )
	{
		// only a-z and 0-9 and space
		$value = my_replaceR('^A-Za-z0-9 ', '', $value);
		$value = mb_convert_case($value, MB_CASE_LOWER);
	}
	else if ( 'case' == $how )
	{
		// keep case, same as a2z but keep upper and lower
		$value = my_replaceR('^A-Za-z0-9 ', '', $value);
	}
	else if ( 'email' == $how )
	{
		// should cover most of it
		$value = my_replaceR('^A-Za-z0-9@_+-.', '', $value);
		$value = mb_convert_case($value, MB_CASE_LOWER);
	}
	else if ( 'num' == $how || 'number' == $how || 'int' == $how )
	{
		// integer only
		$value = my_replaceR('^0-9', '', $value);
		$value = intval($value);
	}
	else if ( 'float' == $how )
	{
		// integer only
		$value = my_replaceR('^0-9\.\,', '', $value);
		$value = my_replaceR(',', '.', $value);
	}
	else if ( 'date' == $how )
	{
		// replace all seperators with -
		$value = my_replaceR('\ \.\,\:\;\/', '-', $value);
		$value = my_replaceR('^0-9\-', '', $value);
	}
	else if ( 'search' == $how )
	{
		// replace funny characters with wildcard "_"
		$value = my_replaceR('^A-Za-z0-9 ', '_', $value);
		$value = mb_convert_case($value, MB_CASE_LOWER);  // sql LIKE doesn't care for case
	}
	else
	{
		// basic filter for chat, blog, notes
		$value = my_replaceR("\r\n", "\n", $value, true);  // keep it consistent
		$value = my_replaceR("\r", "\n", $value);  // keep it consistent
		$value = my_replaceR("–—―‒", "-", $value);  // long dash -> minus
		$value = my_replaceR("‘’´`", "'", $value);  // rounded-apo/backticks -> simple-apo
		$value = my_replaceR("“”«»", "\"", $value);  // rounded-quotes -> simple-quot
		$value = my_replaceR("\p{Zs}*+", ' ', $value);  // simple space

		/** 
		*   name: Jo-hon "cool" d'Rock, jr.
		*	title: Fighter: one,two & 2.0!
		* remove everything that is not a letter, number, or one of these  .,-:!?&"'
		* p search for unicode class
		* L Letters
		* Nd Numbers - d digits
		* Zs space character
		*/
		if ( 'name' == $how )
			$value = my_replaceR("^\p{L}*+\p{Nd}*+\ \.\,\-\:\!\?\&\"\'", '', $value);

		// prepare for output
		$value = htmlspecialchars($value, ENT_QUOTES|ENT_IGNORE|ENT_HTML5, 'UTF-8', false);  // replace < > & " ' with &lt; &gt; &amp; &quot; &apos;
	}

	// limit length?
	$value = trim($value);
	if ( $limit > 0 )
		$value = mb_substr($value, 0, $limit);
	$value = trim($value);

	return $value;
}

// --------------------------------------------------
function func_Limit($text, $maxLen, $ellipse = true)
{
	if ( mb_strlen($text) > $maxLen )
	{
		$text = mb_substr($text, 0, $maxLen);
		$text = trim($text);  // falls jetzt am Ende ein Leerzeichen ist
		if ( $ellipse )
			$text = $text . '&hellip;';
	}
	return $text;
}

// --------------------------------------------------
function func_olderThanSec($date, $sec)
{
	$now = strtotime("now");
	$created = strtotime($date);
	if ( $created + $sec > $now )
		return false;
	return true;
}

// --------------------------------------------------
function strEmpty($strIn)
{
 	// like empty() but:
 	//  - string with spaces-only is "empty"
 	//  - '0' or 0 is not "empty"
	if ( !isset($strIn) )
		return true;

	if ( is_array($strIn) ) 
	{
		if ( empty($strIn) )
			return true;

		// all array-entries empty?
		foreach ( $strIn as $value )
		{
			if ( isset($value) && '' != trim($value) )
				return false;
		}
		return true;
	}

	if ( '' == trim($strIn) )
		return true;

	return false;
}

// --------------------------------------------------
function my_replace($pattern, $replacement, $str)
{
	// handle utf8 correctly
	$pattern = preg_quote($pattern, '/');
	return preg_replace("/".$pattern."/u", $replacement, $str);
}

// --------------------------------------------------
function my_replaceR($pattern, $replacement, $str)
{
	// handle utf8 correctly, R as in Range
	return preg_replace("/[".$pattern."]/u", $replacement, $str);
}

// --------------------------------------------------
function instr($hay, $needle)
{
	if( strEmpty($hay) || strEmpty($needle) )
		return false;

	if( is_array($needle) )
	{
		// array-entries are handled with "OR"
		foreach ( $needle as $value )
		{
			// case-insensitive
			if( mb_stripos($hay, $value) > 0 )
				return true;
		}
	}
	else
	{
		// case-insensitive
		if( mb_stripos($hay, $needle) > 0 )
			return true;
	}

	return false;
}

// --------------------------------------------------
function func_generateToken($length = c_tokenLen, $type = 'save')
{
	// no vowels -> no funny words
	$str = "bcdfghjklmnpqrstvwxyz0123456789";	// 31^8  =	      852.891.037.440  ~40 bits
	if ( 'big' == $type )
		$str = "BCDFGHJKLMNPQRSTVWXYZ";			// 21^10 =     16.679.880.978.201  ~44 bits

	// no homoglyphs: lI nm sS zZ 0O 1lI 2Z 5S 8B 6G
	// BCDFGHJKLMNPQRSTVWXYZbcdfghjkmpqrtvwxy34679 43^8  =     11.688.200.277.601  ~43 bits

	$len = mb_strlen($str);
	$secret = "";
	for( $idx = 0; $idx < $length; $idx++)
		$secret .= $str[random_int(0, $len - 1)];

	return $secret;
}

// ^^^^^ string ^^^^^


// vvvvv other vvvvv

// --------------------------------------------------
function in($idx, $new = null)
{
    // this works like a global variable
	static $arr = array();

	// get or set?
	if ( null === $new )
	{
		// get value
		if ( isset($arr[$idx]) )
			return $arr[$idx];
	}
	else
	{
		// set new value
		$arr[$idx] = $new;
		return true;
	}

	return null;
}

// --------------------------------------------------
function func_Redirect($url)
{
	global $g_stopRedirect;
	if ( $g_stopRedirect > 0 )
	{
		// redirect per script
print <<<REDIREND
	<script>
		function sleep(ms)
		{
			return new Promise(resolve => setTimeout(resolve, ms));
		}

		sleep(3000);
		window.location.href = "{$url}";
	</script>
REDIREND;
		return; 
	}
	else
	{
		while (@ob_end_clean());  // either don't output anything before, or get rid of it now

		// redirect
		header('Location: ' . $url);

		// let it unwind, so db_DoExit is called
	}
}

// --------------------------------------------------
function debug($data)
{
	// print to console before redirecting
    $output = $data;
    if (is_array($output))
        $output = implode(', ', $output);

print <<<DEBUGEND
	<script>
		console.log("debug: {$output}");
	</script>
DEBUGEND;

	global $g_stopRedirect;
	$g_stopRedirect = 3;
}



// ************* output *************
//   use functions instead of editing html tags...


// --------------------------------------------------
function outLine($text)
{
	echo "{$text}\n";
}

// --------------------------------------------------
function outPara($class, $text, $id = '', $style = '', $title = '')
{
	$outClass = '';
	if ( !strEmpty($class) )
		$outClass = " class='{$class}'";

	$outId = '';
	if ( !strEmpty($id) )
		$outId = " id='{$id}'";

	$outStyle = '';
	if ( !strEmpty($style) )
		$outStyle = " style='{$style}'";

	$outTitle = '';
	if ( !strEmpty($title) )
		$outTitle = " title='{$title}'";

	outLine("<p{$outClass}{$outId}{$outStyle}{$outTitle}>{$text}</p>");
}

// --------------------------------------------------
function spacer($size = 16)
{
	if ( $size > 0 && $size < 1000 )
		outDiv('', "&nbsp;", '', "font-size: 0; line-height: 0; height: {$size}px;");
}

// --------------------------------------------------
function outErr($text)
{
	outPara('outErr', $text, '', 'background-color: rgb(100, 0, 0); color: white;');
}

// --------------------------------------------------
function outOK($text)
{
	outPara('outOK', $text, '', 'background-color: rgb(0, 100, 0); color: white;');
}

// --------------------------------------------------
function outInfo($text)
{
	outPara('outOK', $text, '', 'background-color: rgb(0, 0, 100); color: white;');
}

// --------------------------------------------------
function makeUrl($area = '', $cmd = '', $id = '')
{
/* expects 3 parameters: area, cmd, id
 * add this to your httpd.conf or httpd-vhosts.conf
 * QSA = Query String Append
 * PT = passthrough
	RewriteEngine On
	RewriteRule ^/(movies|games|music|books|user)/?([^/]*)/?([^/]*)/?  \
		/index.php?area=$1&cmd=$2&id=$3 [PT,QSA]
*/

	// if empty stop there, no defaults
	$areaOut = '';
	$cmdOut = '';
	$idOut = '';
	if ( !strEmpty($area) )
	{
		$areaOut = "{$area}/";
		if ( !strEmpty($cmd) )
		{
			$cmdOut = "{$cmd}/";
			if ( !strEmpty($id) )
			{
				$idOut = "{$id}/";
			}
		}
	}

	// with closing slash
	$url = c_MainUrl."{$areaOut}{$cmdOut}{$idOut}";
	return htmlspecialchars($url);
}

// --------------------------------------------------
function makeLink($url = '', $text = '', $class = '', $external = false)
{
	$urlOut = '#';
	if ( !strEmpty($url) )
		$urlOut = $url;

	$classOut = '';
	if ( !strEmpty($class) )
		$classOut = "class='{$class}' ";

	$textOut = $url;  // the url if no text
	if ( !strEmpty($text) )
		$textOut = $text;

	$targetOut = "rel='nofollow' ";  // we don't like web crawlers
	if ( $external )
		$targetOut = "target='_blank' rel='noopener noreferrer external nofollow' ";

	return "<a href='{$urlOut}' {$classOut}{$targetOut}>{$textOut}</a>";
}

// --------------------------------------------------
function outLink($url = '', $text = '', $class = '', $id = '')
{
	outLine(makeLink($url, $text, $class, $id));
}

// --------------------------------------------------
function makeSpan($class = '', $text = '', $title = '', $style = '')
{
	$out = "<span ";
	if ( !strEmpty($class) )
		$out .= "class='{$class}' ";
	if ( !strEmpty($style) )
		$out .= "style='{$style}' ";
	if ( !strEmpty($title) )
		$out .= "title='{$title}' ";
	$out .= ">{$text}</span>";
	return $out;
}

// --------------------------------------------------
function outSpan($class, $text, $title = '', $style = '')
{
	outLine(makeSpan($class, $text, $title, $style));
}

// --------------------------------------------------
function makeDiv($class, $text, $id = '', $style = '')
{
	$out = "<div ";
	if ( !strEmpty($id) )
		$out .= "id='{$id}' ";
	if ( !strEmpty($class) )
		$out .= "class='{$class}' ";
	if ( !strEmpty($style) )
		$out .= "style='{$style}' ";
	$out .= ">{$text}</div>";
	return $out;
}

// --------------------------------------------------
function outDiv($class, $text, $id = '', $style = '')
{
	outLine(makeDiv($class, $text, $id, $style));
}

// --------------------------------------------------
function div_($class = '', $id = '', $style = '')
{
	$out = "<div ";
	if ( !strEmpty($id) )
		$out .= "id='{$id}' ";
	if ( !strEmpty($class) )
		$out .= "class='{$class}' ";
	if ( !strEmpty($style) )
		$out .= "style='{$style}' ";
	$out .= ">";
	outLine($out);
}

// --------------------------------------------------
function _div()
{
	outLine("</div>");
}

// --------------------------------------------------
function makeImg($class = '', $src = '', $title = '')
{
	$outClass = '';
	if ( !strEmpty($class) )
		$outClass = " class='{$class}'";

	$outSrc = '';
	if ( !strEmpty($src) )
		$outSrc = " src='{$src}'";

	$outTitle = '';
	if ( !strEmpty($title) )
		$outTitle = " title='{$title}'";

	$out = "<img{$outClass}{$outSrc}{$outTitle}>";
	return $out;
}

// --------------------------------------------------
function outImg($class = '', $src = '', $title = '')
{
	outLine(makeImg($class, $src, $title));
}

?>