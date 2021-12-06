<?php

// --------------------------------------------------
function main_Main()
{
	if ( login_isBot() )
		return false;

	// basic config + db connect
	main_config();

	// check login, get user ID
	login_checkSid();

	// ----- output page -----
	main_PageOut();  // everything, including login form, ajax

	// disconnect db and exit
	db_DoExit();
}


// --------------------------------------------------
function main_config()
{
	mb_internal_encoding('UTF-8');
	mb_regex_encoding('UTF-8');
	date_default_timezone_set('UTC');


	// test or productive?
	if( c_isLocal )
	{
		error_reporting(E_ALL);
		ini_set('log_errors', 1);
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);

		// local
		define('c_Domain', 'mini.local');  // TODOs your settings here
		define('c_MainUrl', 'https://'.c_Domain.'/');

		// mysql database (run db_createMiniDb to create DB and tblUsers)
		$dbName = 'mini';  // TODOs your settings here
		$dbHost = 'localhost';  // TODOs your settings here
		$dbUser = 'root';  // TODOs your settings here (don't use root in production)
		$passwordFile = 'C:/_my/_mini/hidden/password.txt';  // TODOs your settings here (don't want to have this in our source code)
		if( file_exists($passwordFile) )
			$dbPassword = file_get_contents($passwordFile);
	}
	else
	{
		error_reporting(E_ALL);
		ini_set('log_errors', 1);  // send to log
		ini_set('display_errors', 0);
		ini_set('display_startup_errors', 0);

		// your domain
		define('c_Domain', 'php-mini-fw.com');  // TODOs your settings here
		define('c_MainUrl', 'https://'.c_Domain.'/');

		// mysql database
		$dbName = 'mini';  // TODOs your settings here
		$dbHost = 'localhost';  // TODOs your settings here
		$dbUser = 'the_user';  // TODOs your settings here
		$passwordFile = '/home/you/hidden/password.txt';  // TODOs your settings here
		if( file_exists($passwordFile) )
			$dbPassword = file_get_contents($passwordFile);
	}

	define('c_imgUrl', c_MainUrl . 'img/');
	define('c_loginImg', 'veil_nebula.jpg');
	

	// since we have throttling (like 3 logins/hour) this should be enough
	define('c_tokenLen', 8);  	// 31^8  =	                852.891.037.440  ~40 bits
	define('c_sidLen', 16);   	// 31^16 =	727.423.121.747.185.263.828.481  ~80 bits

	define('c_nameMin', 10);  // login name minimum length
	define('c_nameMax', 30);  // login name maximum length
	define('c_passMin', 10);  // password minimum length
	define('c_throttle_time', hr);  // standard intervall for max tries
	define('c_throttle_salt', '8k15qdxp');  // TODOs anything random
	define('c_status_pending', 'pending');
	define('c_status_okay', 'okay');
	define('c_status_timeout', 'timeout');
	define('c_status_banned', 'banned');



	// call this once to create user table
	//db_createMiniDb($dbHost, $dbUser, $dbPassword, $dbName);


	// we always need the db
	db_Connect($dbHost, $dbUser, $dbPassword, $dbName);
}

// --------------------------------------------------
function main_PageOut()
{
	// area, cmd, ID
	main_getInput();

	// return "text only" for AJAX, testing
	if( main_beforePageOut() )
		return;

	// collect output
	ob_start();
	header("Content-Type: text/html; charset=utf8");

	main_HtmlHeader();
	main_Content();
	main_HtmlFooter();
}

// --------------------------------------------------
function main_getInput()
{
	// get input (first 3 folders replaced as params, see RewriteRule in httpd-vhosts.conf)
	$areaIn = func_GetInput('area', 20, 'a2z');
	$cmdIn = func_GetInput('cmd', 20, 'a2z');  // cmd must be lower case!
	$idIn = func_GetInput('token', 20, 'a2z');

	// allowed areas
	switch( $areaIn )
	{
		case 'one':
			in('area', 'one'); break;
		case 'two':
			in('area', 'two'); break;
		case 'three':
			in('area', 'three'); break;
		case 'user':
			in('area', 'user'); break;
		case 'mod':
			in('area', 'mod'); break;
		default:
			in('area', '');  // no default
	}

	// still, don't trust anything coming from in()
	in('cmd', $cmdIn);
	in('token', $idIn);  // von außen kann nur token kommen (kein int)
}

// --------------------------------------------------
function is_area($area)
{
	if( 'one' === $area ||
		'two' === $area ||
		'three' === $area )
		return true;
	return false;
}

// --------------------------------------------------
function main_beforePageOut()
{
	$area = in('area');
	$cmd = in('cmd');
	$token = in('token');
	//outOK($area.' - '.$cmd.' - '.$token));

	if( 'mod' === $area )
	{
		// TODOx add rights for mods
		if( 'mod' != user('status') )
			return false;
		//mod_main();
		return true;
	}
	else if( 'searchajax' === $cmd )
	{
		// AJAX
		// if ( is_area($area) )
		//	area_outSearchResult();
		return true;  // stop, don't output anything else
	}

	return false;  // continue with main page
}


// --------------------------------------------------
function main_HtmlHeader()
{
	outLine("<!doctype html>");
	outLine("<html lang='en'>");

	outLine("");
	outLine("<head>");
	outLine("	<meta charset='utf-8' />");
	outLine("	<meta name='viewport' content='width=device-width, initial-scale=1' />");
	outLine("	<meta name='description' content='testing PHP-mini-website-framework (github)' />");
	outLine("	<meta name=\"Content-Security-Policy: default-src 'self'; form-action 'self'; frame-ancestors 'none';\" />");
	outLine("	<meta name='robots' content='noindex nofollow noarchive nosnippet noimageindex' />");
	outLine("");
	outLine("	<link rel='icon' type='image/png' href='/favicon.png' />");

	// CSS
	$cssFile = 'all.css';
	clearstatcache(true, $_SERVER['DOCUMENT_ROOT'].'/'.$cssFile);
	$fileTime = filemtime($_SERVER['DOCUMENT_ROOT'].'/'.$cssFile);
	outLine("	<link rel='stylesheet' type='text/css' href='/{$cssFile}?t={$fileTime}' >");  // gets updated when changed, gets cached if not changed, only flickers when changed


	outLine("");
	outLine("	<title>PHP-mini on Github</title>");
	outLine("</head>");
	outLine("");

	outLine("<body class='bg-dark' >");
	outLine("");
}

// --------------------------------------------------
function main_HtmlFooter()
{
	$linkMain = makeLink(makeUrl(), c_Domain);
	spacer();
	outDiv('ctr sml', ''.makeLink('https://github.com/EnthusiastNT/PHP-mini-website-framework', 'PHP-mini-website-framework', '').' on GitHub');
	spacer();
 	outPara('ctr sml', $linkMain);
	spacer();
	spacer();
	spacer();
	spacer();
	spacer();


	// javascript
	$jsFile = 'base.js';
	clearstatcache(true, $_SERVER['DOCUMENT_ROOT'].'/'.$jsFile);
	$fileTime = filemtime($_SERVER['DOCUMENT_ROOT'].'/'.$jsFile);
	outLine("	<script type='application/javascript' src='/{$jsFile}?t={$fileTime}' ></script>");

	outLine("</body>");
	outLine("</html>");
}

// --------------------------------------------------
function outForm($area, $cmd, $token = '')
{
	outLine("<form action='".c_MainUrl."{$area}/{$cmd}/{$token}' method='post' rel='nofollow'>");
	// TODOx sec token for every form
}

// --------------------------------------------------
function main_Content()
{
	// width depending viewport
	div_('content');
	

	// nav bar
	main_outTopBar();


	// make life easier
	$area = in('area');
	$cmd = in('cmd');
	$token = in('token');


	// user is logged in
	switch( $area )
	{
		case 'user':
			user_Main($cmd);
			break;

		case 'one':
		case 'two':
		case 'three':
			spacer();
			outDiv('hh2', 'stub for area '.$area);
			spacer(32);
			break;

		default:
			main_outMainPage($area, $cmd, $token);
			break;
	}

	// footer
	spacer(32);
	_div();  // content
}


// --------------------------------------------------
function main_outTopBar()
{
	$imgUrl = htmlspecialchars(c_imgUrl . 'mini.png');
	$imgTag = makeImg('nav-logo-size', $imgUrl);
	$imgLink = makeLink(makeUrl(), $imgTag);
 
	$linkOne = makeLink(makeUrl('one', 'show'), 'one', 'nav-link');
	$linkTwo = makeLink(makeUrl('two', 'show'), 'two', 'nav-link');
	$linkThree = makeLink(makeUrl('three', 'show'), 'three', 'nav-link');

	outLine("<ul class='nav-container' >");
	outLine("  <li class='nav-tab nav-logo-pad'>{$imgLink}</li>");
	outLine("  <li class='nav-tab ".('one' == in('area') ? 'nav-active':'')."'>{$linkOne}</li>");
	outLine("  <li class='nav-tab ".('two' == in('area') ? 'nav-active':'')."'>{$linkTwo}</li>");
	outLine("  <li class='nav-tab ".('three' == in('area') ? 'nav-active':'')."'>{$linkThree}</li>");

	$menu = makeAvatar();
	outLine("  <li class='nav-dropdown'>{$menu}</li>");

	outLine('</ul>');
}

// --------------------------------------------------
function main_outPublicPage($area, $cmd, $token)
{
	// public stuff, no logon required
	spacer();
	outDiv('hh2', 'PHP Mini Website Framework');
	spacer(32);
	outPara('', 'This is a test website for a minimal PHP framework, featuring a database wrapper and login management, available on GitHub:');
	spacer();
	outDiv('', makeLink('https://github.com/EnthusiastNT/PHP-mini-website-framework', 'PHP-mini-website-framework', 'bld').' on GitHub');
	spacer(64);
	outPara('', makeLink(makeUrl('user', 'login'), 'login'));
	outPara('', makeLink(makeUrl('user', 'register'), 'register'));
}

// --------------------------------------------------
function main_outMainPage($area, $cmd, $token)
{
	// login required from here
	if ( !user() )
		return main_outPublicPage($area, $cmd, $token);

	spacer();
	outDiv('hh2', 'PHP Mini Website Framework');
	spacer(32);
	outPara('', 'you are logged in as '.user('displayName'));
	spacer();
	outPara('', makeLink(makeUrl('user'), 'my profile'));
	outPara('', makeLink(makeUrl('user', 'logout'), 'logout'));
}

?>