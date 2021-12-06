<?php

define('th_badsid', 'badsid');
define('th_name', 'name');
define('th_passwd', 'passwd');
define('th_register', 'register');

// --------------------------------------------------
function login_checkSid()
{
	// bad guy trying (old) session IDs?
 	if ( throttle_isBlocked(th_badsid) )
	{
		login_SetCookie('sid', 'invalid', "-1 year");  // delete cookie
		return;
	}

	// SID from cookie
	$sid = login_GetCookie('sid');
	if ( $sid && c_sidLen == mb_strlen($sid) )
	{
		// check if sid belongs to a user
		if ( !login_getUser4Sid($sid) )
		{
			// bucket has lost one drop
			throttle_touch(th_badsid);
			login_SetCookie('sid', 'invalid', "-1 year");  // delete cookie
		}
	}
}

// --------------------------------------------------
function login_getUser4Sid($sid)
{
	if ( !$sid || c_sidLen != mb_strlen($sid) )
		return false;

	$db = db();
	$db->where('sid', $sid);
	$rowUser = $db->getRow('tblUsers');
	if ( !$rowUser )
		return false;

	// store in user()
	foreach ( $rowUser as $key => $value )
		user($key, $value);

	// yes, we have a user
	return true;
}

// --------------------------------------------------
function login_createUser($nameIn, $passIn)
{
	$data = array();
	$data['status'] = c_status_okay;
	$data['loginName'] = $nameIn;
	$data['displayName'] = "new".func_generateToken();  // NOT the login name
	$data['pwHash'] = password_hash($passIn, PASSWORD_DEFAULT);  // store hash

	// create unique sid
	$data = token_UniqueInsert('tblUsers', $data, 'ID', 'sid', c_sidLen);
	if ( !$data )
		return false;

	// store data in user()
	foreach ( $data as $key => $value )
		user($key, $value);

	return true;
}

// --------------------------------------------------
function login_GetCookie($name, $length = c_sidLen)
{
	$value = func_GetInput($name, $length, 'cookie');
	if( $length != mb_strlen($value) )
		return false;

	return $value;
}

// --------------------------------------------------
function login_SetCookie($name, $value, $datetime)
{
	while (@ob_end_clean());  // either don't output anything before, or get rid of it now

	// PHP >= 7.3
	$options = [
		'expires' => strtotime($datetime),
		'path' => '/',
		'domain' => c_Domain,
		'secure' => 1,
		'httponly' => 1,
		'samesite' => 'Strict'
	];
	setcookie($name, $value, $options);
}

// --------------------------------------------------
function userOK()
{
	// verified and not banned
	if ( user('ID') > 0 && c_status_okay == user('status') )
		return true;
	return false;
}

// --------------------------------------------------
function user($idx = 0, $new = null)
{
    // this works like a global variable
	static $arr = array();
	if ( empty($arr) )
	{
		$arr['ID'] = 0;
		$arr['status'] = '';  // pending (e.g. email confirmation) | normal | timed-out | banned...
		$arr['loginName'] = 'guest';
		$arr['displayName'] = 'guest';
		$arr['goodLogin'] = '0000-00-00 00:00:00';  // entered password, sid last changed
		$arr['created'] = '0000-00-00';  // member since
	}

	// debug
	if ( 'pr' === $idx )
	{
		print_r($arr);
		return true;
	}

	// short for user('ID') > 0
	if ( empty($idx) )
	{
		// we have a user, status doesn't matter
		if ( intval(user('ID')) > 0 )
			return true;
		return false;
	}

	// get or set?
	if ( null === $new )
	{
		// return value
		if ( isset($arr[$idx]) )
			return $arr[$idx];
	}
	else
	{
		// set new value, return previous
		$previous = null;
		if ( isset($arr[$idx]) )
			$previous = $arr[$idx];

		$arr[$idx] = $new;
		return $previous;
	}

	outErr('user');
	return null;
}

// --------------------------------------------------
function login_checkCmds($cmd)
{
	switch( $cmd )
	{
		// need to login again before changing pw
		case 'login':  login_ShowLogin(); return true;
		case 'login2': login_ProcessLogin(); return true;
	}

	if ( !user() )
	{
		// no user
		switch( $cmd )
		{
			case 'register': login_ShowRegister(); return true;
			case 'register2': login_ProcessRegister(); return true;
		}
	}
	else
	{
		// we have a user
		switch( $cmd )
		{
			case 'logout': login_logout(); return true;
			case 'newpw': login_showChangePassw(); return true;
			case 'newpw2': login_processChangePassw(); return true;
			case 'newname': login_showChangeName(); return true;
			case 'newname2': login_processChangeName(); return true;
		}
	}

	return false;
}


// --------------------------------------------------
function login_ShowLogin()
{
	div_("bigimg-container");
		outDiv('bigimg-top hh2', 'Login');
		outDiv('bigimg-bottom tny', 'Veil Nebula (NASA)');
		outImg('bigimg-img', c_imgUrl.c_loginImg);
	_div();

	div_("containerIndent");
	outForm('user', 'login2');
		// captcha with not-visible input: css display = "none"; (not "hidden")
		// only bots can possibley fill this out, so if it's filled out it's a bot
		outLine("<label class='none' for='name'><b>Name</b></label>");
		outLine("<input type='text' class='input100 none' placeholder='enter your name' name='name' />");

		outLine("<label for='login'><b>Login Name</b></label>");
		outLine("<input type='text' autofocus class='input100' placeholder='enter your login name' name='login' required />");

		outLine("<label for='passwd'><b>Password</b> &nbsp; &nbsp; <label><input type='checkbox' id='showPass' onclick='showPw()' />show</label></label>");
		outLine("<input type='password' class='input100' placeholder='enter your password' id='passwd' name='passwd' required />");

		outPara('', "<button class='buttonGreen' type='submit'>Login</button>");

	outLine("</form>");
	_div();  // containerIndent
}

// --------------------------------------------------
function login_ShowRegister()
{
	div_("bigimg-container");
		outDiv('bigimg-top hh2', 'Register');
		outDiv('bigimg-bottom tny', 'Veil Nebula (NASA)');
		outImg('bigimg-img', c_imgUrl.c_loginImg);
	_div();

	div_("containerIndent");
	outForm('user', 'register2');
		// captcha with not-visible input: css display = "none"; (not "hidden")
		// only bots can possibley fill this out, so if it's filled out it's a bot
		outLine("<label class='none' for='name'><b>Name</b></label>");
		outLine("<input type='text' class='input100 none' placeholder='enter your name' name='name' />");

		outLine("<label for='login'><b>Login Name</b></label>");
		outLine("<input type='text' autofocus class='input100' placeholder='enter a name' name='login' required />");

		outLine("<label for='passwd'><b>Password</b> &nbsp; &nbsp; <label><input type='checkbox' id='showPass' onclick='showPw()' />show</label></label>");
		outLine("<input type='password' class='input100' placeholder='enter a password' id='passwd' name='passwd' required />");

		outLine("<label for='passwd2'><b>Repeat Password</b></label>");
		outLine("<input type='password' class='input100' placeholder='repeat the password' id='passwd2' name='passwd2' required />");

		outPara('', "<button class='buttonGreen' type='submit'>Register</button>");

	outLine("</form>");
	_div();  // containerIndent
}


define('c_loginTitle', 'logging in...');
define('c_loginMsg', 'unknown user or password');  // same msg, don't reveal what's wrong
define('c_registerTitle', 'registering...');
define('c_registerMsg', 'Error. Please try again later.');

// --------------------------------------------------
function login_ProcessLogin()
{
	usleep(random_int(50000, 150000));  // mitigate timing attack
	if ( throttle_isBlocked(th_name) )
		return false;
	if ( throttle_isBlocked(th_passwd) )
		return false;


	// verify captchas
	if ( !login_checkCaptcha() )
		return login_outNopeMessage(c_loginTitle, c_loginMsg);


	// check input
	$nameIn = func_GetInput('login', c_nameMax, 'a2z');
	$passIn = func_GetInput('passwd', 255, 'pass');

	// does that user exist?
	$db = db();
	$db->where('loginName', $nameIn);
	$rowUser = $db->getRow('tblUsers');
	if ( !$rowUser )
	{
		// prevent bad actors from finding valid login names
		throttle_touch(th_name);
		return login_outNopeMessage(c_loginTitle, c_loginMsg);
	}

	// verify password
	if( !password_verify($passIn, $rowUser['pwHash']) )
	{
		throttle_touch(th_passwd);
		return login_outNopeMessage(c_loginTitle, c_loginMsg);
	}


	// OKAY

	// save login time (and update hash)
	$data = array();
	$data['goodLogin'] = date(stamp);
	if( password_needs_rehash($rowUser['pwHash'], PASSWORD_DEFAULT) )
		$data['pwHash'] = password_hash($passIn, PASSWORD_DEFAULT);
	$db->where('ID', $rowUser['ID']);
	$db->update('tblUsers', $data);


	// update sid as well (invalidates hacked sessions) TODOx allow multiple logins on different browsers
	$newSid = token_UniqueUpdate('tblUsers', $rowUser['ID'], 'ID', 'sid', c_sidLen);
	if ( $newSid )
		login_SetCookie('sid', $newSid, "+1 year");

	// yes, we have a user
	func_Redirect(c_MainUrl);
	return true;
}

// --------------------------------------------------
function login_ProcessRegister()
{
	usleep(random_int(50000, 150000));  // mitigate timing attack
	if ( throttle_isBlocked(th_register) )
		return false;

	static $proTip = "Pro Tip: use a password manager (like KeyPass) and generate 
						a random login name and a random password with 20 characters each.";

	// verify captchas
	if ( !login_checkCaptcha() )
		return login_outNopeMessage(c_registerTitle, c_registerMsg);


	// get input
	$nameIn = func_GetInput('login', c_nameMax, 'a2z');
	$passIn = func_GetInput('passwd', 255, 'pass');
	$pass2In = func_GetInput('passwd2', 255, 'pass');

	if ( !login_checkLoginName($nameIn, $msgOut) )
		return login_outMessage(c_registerTitle, $msgOut, $proTip);

	if ( !login_checkPasswd($nameIn, $passIn, $pass2In, $msgOut) )
		return login_outMessage(c_registerTitle, $msgOut, $proTip);


	// OKAY

	// only 1 account/day
	// (note to support: if a group of people is behind a NAT, think of a class at school, they cannot register all at once)
	throttle_touch(th_register, -3);  // no more tries for 24 hours

	// create user
	if ( login_createUser($nameIn, $passIn) )
	{
		// store SID in cookie
		login_SetCookie('sid', user('sid'), "+1 year");
		func_Redirect(c_MainUrl);
	}
}

// --------------------------------------------------
function login_checkCaptcha()
{
	// discourage bad actors/bots/scripts so that they go somewhere else 
	// (while still convenient for legitimate users)

	// only a bot would see this hidden input and fill it out
	$fakeIn = func_GetInput('name', 1);
	if ( !strEmpty($fakeIn) )
	{
		throttle_touch('bot', 1);  // only 1 try
		return false;
	}

	// TODOx time-based captcha (should take the user a few seconds to fill out the form, whereas bots respond immediately)

	// TODOx calculation captcha (3+5=? easy for humans)

	// TODOx question captcha (easy for humans)
		// how many legs does a cat have? (4/four)
		// how many months in a year? (12/twelve)
		// how many days in a week? (7/seven)
		// color of the sky? (blue)
		// enter the following letters: cool/red/you/yes/  (accept both lower and upper case)

	// TODOx script captcha (needs javascript which most bots won't have)
		// random number between 1.000 and 1.000.000.000, hidden input
		//  e.g. 6512981 -> int(ln(6512981)) -> script sets value to 15

	// all checks passed
	return true;
}

// --------------------------------------------------
function login_checkLoginName($nameIn, &$msgOut)
{
	// name length
	if ( mb_strlen($nameIn) < c_nameMin )
	{
		$msgOut = "Your login name is less than ".c_nameMin." characters.
					This is not secure enough here.";
		return false;
	}

	// only complex login name
	$bad = login_isMostHacked($nameIn);
	if ( '' !== $bad )
	{
		$msgOut = "Your login name contains '{$bad}' which is one of the most hacked passwords.
					This is not secure, and not allowed here.";
		return false;
	}

	// login name must be unique (otherwise we can't find hash)
	$db = db();
	$db->where('loginName', $nameIn);
	$rowUser = $db->getRow('tblUsers');
	if ( $rowUser )
	{
		// prevent bad actors from finding valid login names
		throttle_touch(th_name);
		$msgOut = "user name already exists";
		return false;
	}

	// good
	return true;
}

// --------------------------------------------------
function login_checkPasswd($nameIn, $passIn, $pass2In, &$msgOut)
{
	// passwords must match
	if ( $passIn !== $pass2In )
	{
		$msgOut = "passwords don't match";
		return false;
	}

	// password different from login name
	if ( $nameIn === $passIn )
	{
		$msgOut = "password must be different from login name";
		return false;
	}

	// password length
	if ( mb_strlen($passIn) < c_passMin )
	{
		$msgOut = "Your password is less than ".c_passMin." characters.
					This is no longer secure in ".date('Y').".";
		return false;
	}

	// only complex password
	$bad = login_isMostHacked($passIn);
	if ( '' !== $bad )
	{
		$msgOut = "Your password contains '{$bad}' which is one of the most hacked passwords.
					This is not secure, and not allowed here.";
		return false;
	}

	// no sequence like aaaa or 9876
	if ( login_isSequence($passIn, 4) )
	{
		$msgOut = "A sequence like 1111 or 1234 is not secure.";
		return false;
	}

	// alright
	return true;
}

// --------------------------------------------------
function login_isMostHacked($passIn)
{
	// top 100 most hacked passwords (or parts thereof)
	// (we check for sequences like 1111 and 1234 seperately)
	static $strTop100 = 
"1212
1313
6969
1122
qwe
asd
zxc
qaz
wsx
pass
word
dragon
baseball
football
monkey
letmein
shadow
master
mustang
michael
pussy
superman
fuckyou
killer
trustno1
jordan
jennifer
hunter
buster
soccer
harley
batman
andrew
tigger
sunshine
iloveyou
fuckme
charlie
robert
thomas
hockey
ranger
daniel
starwars
klaster
george
asshole
computer
michelle
jessica
pepper
freedom
pass
fuck
maggie
159753
ginger
princess
joshua
cheese
amanda
summer
love
ashley
nicole
chelsea
biteme
matthew
access
yankees
dallas
austin
thunder
taylor
matrix
minecraft";

	$arrTop = explode(PHP_EOL, $strTop100);
	foreach ( $arrTop as $bad )
	{
		if ( 1 === preg_match("/".$bad."/u", $passIn) )
			return $bad;
	}

	// password is okay
	return '';
}

// --------------------------------------------------
function login_isSequence($passIn, $max = 4)
{
	// max chars in sequence, meaning, 3 steps
	$arr = str_split($passIn);
	$count = 1;
	$firstDelta = ord($arr[0]) - ord($arr[1]);  // can be +/-/0
	for( $idx = 1; $idx < sizeof($arr) - 1; $idx++ )
	{
		$thisDelta = ord($arr[$idx]) - ord($arr[$idx + 1]);
		if( $thisDelta != $firstDelta )
		{
			// no sequence so far, restart
			$count = 1;
			$firstDelta = $thisDelta;
			continue;  // for
		}

		// is sequence
		$count++;

		// e.g. 4 chars in sequence means 3 steps
		if( $count >= ($max - 1) )
			return true;  // too much of a sequence
	}

	// no sequence, we're good
	return false;
}

// --------------------------------------------------
function login_checkDisplayName($nameIn)
{
	// don't allow certain names
	static $notAllowed = 
"mod
admin
shit
piss
fuck
cunt
cock
tits
dick
ass
pussy";

	$arr = explode(PHP_EOL, $notAllowed);
	foreach ( $arr as $bad )
	{
		if ( 1 === preg_match("/".$bad."/u", $nameIn) )
			return false;
	}
	return true;
}

// --------------------------------------------------
function login_outMessage($title, $text1, $text2 = '')
{
	spacer();
	outDiv('hh2', $title);
	spacer(32);
	outPara('', $text1);

	if ( !strEmpty($text2) )
	{
		spacer(32);
		outPara('', $text2);
	}
}

// --------------------------------------------------
function login_outNopeMessage($title, $text1, $text2 = '')
{
	login_outMessage($title, $text1, $text2);

	// discourage bots/scripts
  	usleep(random_int(300000, 900000));
}

// --------------------------------------------------
function login_logout()
{
	if ( !user() )
		return;

	// remove sid in db
	$data = array();
	$data['sid'] = '';
	$db = db();
	$db->where('ID', user('ID'));
	$db->update('tblUsers', $data);

	// tell browser to invalidate cookie
	login_SetCookie('sid', 'invalid', "-1 year");  // delete cookie
	unset($_COOKIE['sid']);
	func_Redirect(c_MainUrl);
}

// --------------------------------------------------
function login_showChangeName()
{
	if ( !user() )
		return false;

	div_("bigimg-container");
		outDiv('bigimg-top hh2', 'Change Display Name');
		outDiv('bigimg-bottom tny', 'Veil Nebula (NASA)');
		outImg('bigimg-img', c_imgUrl.c_loginImg);
	_div();

	div_("containerIndent");
	outForm('user', 'newname2');

		outLine("<label for='display'><b>Display Name</b></label>");
		outLine("<input type='text' autofocus class='input100' placeholder='enter a name' name='display' value='".user('displayName')."' required />");

		outPara('', "<button class='buttonGreen' type='submit'>Save</button>");

	outLine("</form>");
	_div();  // containerIndent
}

// --------------------------------------------------
function login_processChangeName()
{
	if ( !user() )
		return false;

 	static $title = 'changing name...';
	static $nope = "This name is already in use.";

	// check input
	$nameIn = func_GetInput('display', c_nameMax, 'name');

	// must not be the login name
	if ( user('loginName') == $nameIn )
		return login_outMessage($title, "Display name cannot be your login name.");

	// don't allow certain names
	if ( !login_checkDisplayName($nameIn) )
		return login_outMessage($title, $nope);

	// name already exist? (no throttle since display names have no value for security)
	$db = db();
	$db->where('displayName', $nameIn);
	if ( $db->has('tblUsers') )
		return login_outMessage($title, $nope);

	// OKAY

	// store new name
	$data = array();
	$data['displayName'] = $nameIn;
	$db->where('ID', user('ID'));
	$db->update('tblUsers', $data);

	// done
	func_Redirect(c_MainUrl.'user');
	return true;
}

// --------------------------------------------------
function login_showChangePassw()
{
	if ( !user() )
		return false;

	if ( func_olderThanSec(user('goodLogin'), 30*mn) )
	{
		outPara('', 'Please login again to change your password:');
		outPara('', makeLink(makeUrl('user', 'login'), 'login'));
		return false;
	}


	div_("bigimg-container");
		outDiv('bigimg-top hh2', 'Change Password');
		outDiv('bigimg-bottom tny', 'Veil Nebula (NASA)');
		outImg('bigimg-img', c_imgUrl.c_loginImg);
	_div();

	div_("containerIndent");
	outForm('user', 'newpw2');

		outLine("<label for='passwd'><b>New Password</b> &nbsp; &nbsp; <label><input type='checkbox' id='showPass' onclick='showPw()' />show</label></label>");
		outLine("<input type='password' class='input100' placeholder='enter a new password' id='passwd' name='passwd' required />");

		outLine("<label for='passwd2'><b>Repeat Password</b></label>");
		outLine("<input type='password' class='input100' placeholder='repeat the password' id='passwd2' name='passwd2' required />");

		outPara('', "<button class='buttonGreen' type='submit'>Save</button>");

	outLine("</form>");
	_div();  // containerIndent
}

// --------------------------------------------------
function login_processChangePassw()
{
	if ( !user() )
		return false;

 	static $title = 'changing password...';
	static $proTip = "Pro Tip: use a password manager (like KeyPass) and generate 
						a random password with 20 characters.";

	// check input
	$passIn = func_GetInput('passwd', 255, 'pass');
	$pass2In = func_GetInput('passwd2', 255, 'pass');

	if ( !login_checkPasswd(user('loginName'), $passIn, $pass2In, $msgOut) )
		return login_outMessage($title, $msgOut, $proTip);

	// OKAY

	// store new hash
	$data = array();
	$data['pwHash'] = password_hash($passIn, PASSWORD_DEFAULT);  // store hash
	$db = db();
	$db->where('ID', user('ID'));
	$db->update('tblUsers', $data);

	login_outMessage($title, "Great! Password has been changed!");
	spacer();
	outPara('', makeLink(makeUrl('user'), 'Profile'));
}


/* ***** bots ***** */

// --------------------------------------------------
function login_isBot()
{
	if ( login_isCrawler() )
	{
		while (@ob_end_clean());
		header("HTTP/1.1 422 Unprocessable Entity", true, 422);  // bot
		db_DoExit();
	}

	if ( login_isHacker() )
	{
		while (@ob_end_clean());
		header("HTTP/1.1 418 I'm a teapot", true, 418);  // bad actor
		db_DoExit();
	}

	// okay
	return false;
}

// --------------------------------------------------
function login_isCrawler()
{
	// filter bots/crawlers/spiders
	//   short version of: https://github.com/JayBizzle/Crawler-Detect
	if ( isset($_SERVER['HTTP_USER_AGENT']) )
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
	if ( isset($_SERVER['HTTP_FROM']) )
		$userAgent .= $_SERVER['HTTP_FROM'];

	$regex = "bot|robo|crawl|archive|transcode|spid|uptime|validat|fetch|cron|".
				"check|reader|extract|monitor|analyze|scrap|search|snif|".
				"google|bing|yahoo|baidu|slurp|wget|linked|facebook|httpx|fluffy|virus|b0t|suck|collect";
	return (bool) preg_match("/{$regex}/iu", $userAgent);
}

// --------------------------------------------------
function login_isHacker()
{
	// check the url for stuff that has no business in a url
	if ( isset($_SERVER['QUERY_STRING']) )
		$queryStr = $_SERVER['QUERY_STRING'];
	if ( isset($_SERVER['HTTP_REFERER']) )
		$queryStr .= $_SERVER['HTTP_REFERER'];

	$regex = "debug|\@|\<|\>|test\/wp|wp\-includes";
	return (bool) preg_match("/{$regex}/iu", $queryStr);
}


/* ***** THROTTLING *****
 *  limit logins/register per IP to 3/hour
 *      - after 1 hour allow 1 more try -> 4
 *      - after 3 hours allow 1 more try -> 5
 *      - after 6 hours allow 1 more try -> 6
 *  delete after 24 hours, everyone will have a new IP by then...
 */

// --------------------------------------------------
function throttle_isBlocked($what)
{
	$hash = throttle_getHash($what);

	// get bucket if exists
	$db = db();
	$db->where('hash', $hash);
	$rowThrottle = $db->getRow('tblThrottle');
	if ( $rowThrottle )
	{
		// disregard anything older than 24 hours
		if ( func_olderThanSec($rowThrottle['touched'], day) )
		{
			db_deleteOlder('tblThrottle', 'touched', '-24 hours');
			return false;  // don't throttle
		}

		// bucket empty?
		if ( $rowThrottle['bucket'] <= 0 )
		{
			if ( $rowThrottle['bucket'] > -3 )
			{
				// time to wait
				$timeTomWaits = 1 * hr;  // 0, bucket empty, wait 1 hour
				if ( -1 == $rowThrottle['bucket'] )
					$timeTomWaits = 2 * hr;  // wait 2 more hours (=3h)
				else if ( -2 == $rowThrottle['bucket'] )
					$timeTomWaits = 3 * hr;  // wait 3 more hours (=6h)

				// waited > x hours?
				if ( func_olderThanSec($rowThrottle['touched'], $timeTomWaits) )
				{
					// don't throttle, got one more try
					return false;
				}
			}

			// is bad, throttle
			throttle_badMsg($what);
			return true;
		}
	}

	// all's fine, don't throttle
	return false;
}

// --------------------------------------------------
function throttle_touch($what, $max = 3)
{
	// failed login, tried to register -> remember IP (or touch)
	$hash = throttle_getHash($what);

	// first time here?
	$data = array();
	$data['touched'] = date(stamp);  // now

	// get bucket if exists
	$db = db();
	$db->where('hash', $hash);
	$rowThrottle = $db->getRow('tblThrottle');
	if ( $rowThrottle )
	{
		$data['bucket'] = $rowThrottle['bucket'] - 1;  // one drop
		$db->where('hash', $hash);
		$db->update('tblThrottle', $data, 1);
	}
	else
	{
		$data['hash'] = $hash;
		$data['bucket'] = $max - 1;  // one drop
		$db->insert('tblThrottle', $data);
	}

	// delete everything not touched in 24 hours
	db_deleteOlder('tblThrottle', 'touched', '-24 hours');
}

// --------------------------------------------------
function throttle_badMsg($what)
{
 	static $title = 'Too Many Requests';

	switch ( $what )
	{
		case 'really bad...':
			// don't send anything back, just a blank page
			while (@ob_end_clean());
			header("HTTP/1.1 429 Too Many Requests", true, 429);
			db_DoExit();
			break;

		case th_badsid:
			// we tried...
			login_outNopeMessage($title, "Please clear your browser cache.");
			break;

		case th_name:
		case th_passwd:
			login_outNopeMessage(c_loginTitle, c_loginMsg);
			break;

		case th_register:
			login_outNopeMessage(c_registerTitle, c_registerMsg);
			break;
	}
}

// --------------------------------------------------
function throttle_dump($what)
{
	// successfull login etc. -> delete that bucket
	//   meh, get's deleted if older than 24 hours anyway...
	//   also, successfull doesn't mean they aren't bad...
	$hash = throttle_getHash($what);
	db_deleteWhere('tblThrottle', ['hash' => $hash]);
}

// --------------------------------------------------
function throttle_getHash($what)
{
	// different buckets for different actions
	$ip = throttle_getIP();
	return $what.hash('sha3-256', c_throttle_salt.$ip);
}

// --------------------------------------------------
function throttle_getIP()
{
	// remember: the IP address is "private" data, only store hash!
	if ( !isset($_SERVER['REMOTE_ADDR']) )
		return '999';
	$ip = $_SERVER['REMOTE_ADDR'];

	// ipv6? block the entire subnet /64 (or /56 or /48)
	$block = $ip;
	$bytes = inet_pton($ip);  // requires ipv6 support enabled, check with phpinfo()
	$len = strlen($bytes);  // should always be 16
	if ( 16 == $len && $ip == filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) )
	{
		$cut = 8;  // 8 for /64, 7 for /56, 6 for /48
		$net = substr($bytes, 0, $cut);
		$net = str_pad($net, $len, "\0");
		$block = inet_ntop($net);
	}

	return $block;
}

/*
$start = microtime(true);
outInfo(hash('sha3-256', "testing123"));
outOK(sprintf('%f', microtime(true)-$start));
exit;
*/

/*
FAQ

Q: Why not use e-mail as login?
A: Websites aim to identify you so they can sell targeted ads (ie. earn more money).
By giving them your e-mail address you basically identify yourself.

-> TODOx fail-safe: master password that the user can print out

Q: Why have different display and login names?
A: With your login name bad actors can attack you by entering the wrong password repeatedly.
This would effectively block you from logging in.
Brute-forcing login forms is basically impossible without the login name.

Q: Why never post the user ID?
A: The ID never changes. It connects tables in the database. Better to use a random token 
that can be changed (theoretically, if necessary, or regularly).

-> TODOx use token to link to user



TODOx allow multiple logins on different browsers, ie. desktop and mobile

*/

?>