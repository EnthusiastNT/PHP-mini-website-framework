<?php

// --------------------------------------------------
function user_Main($cmd)
{
	// login & register
	if ( login_checkCmds($cmd) )
		return;


	// user cmds
	if ( !user() )
	{
		// no user
		switch( $cmd )
		{
			default:
				user_PleaseLogin();
				break;
		}
	}
	else
	{
		// we have a user
		switch( $cmd )
		{
			case 'settings':
				user_showSettings();
				break;

			case 'show':
			default:
				user_Show();
				break;
		}
	}
}

// --------------------------------------------------
function user_TopBar()
{
	$linkProfile = makeLink(makeUrl('user', 'show'), 'Profile');
	$linkSettings = makeLink(makeUrl('user', 'settings'), 'Settings');
	$linkLogout = makeLink(makeUrl('user', 'logout'), 'Logout');
	spacer(2);
	outDiv('sml drk', user('displayName').": {$linkProfile} | {$linkSettings} | {$linkLogout}");
	spacer();
}

// --------------------------------------------------
function user_PleaseLogin()
{
	// public stuff, no logon required
	spacer();
	outDiv('hh2', 'PHP Mini Website Framework');
	spacer(32);
	outPara('', 'please login or register...');
	spacer();
	outPara('', makeLink(makeUrl('user', 'login'), 'login'));
	outPara('', makeLink(makeUrl('user', 'register'), 'register'));
}

// --------------------------------------------------
function user_Show()
{
	if ( !user() )
		user_PleaseLogin();
	user_TopBar();

	// user profile
	outDiv('hh2', 'Your Profile');
	spacer(32);
	outPara('', 'Display Name: '.user('displayName'));
	outPara('', 'Status: '.user('status'));
	outPara('drk', 'Login Name: '.user('loginName'));
	outPara('drk', 'SID: '.user('sid'));
	outPara('drk', 'Last Login: '.user('goodLogin'));
	outPara('drk', 'Registered: '.user('created'));
}

// --------------------------------------------------
function user_showSettings()
{
	if ( !user() )
		user_PleaseLogin();
	user_TopBar();

	// change password
	outDiv('hh2', 'Settings');
	spacer(32);
	outPara('', makeLink(makeUrl('user', 'newname'), 'Change Display Name'));
	outPara('', makeLink(makeUrl('user', 'newpw'), 'Change Password'));
}

// --------------------------------------------------
function makeAvatar($imgClass = 'avatarMini')
{
	$imgUrl = htmlspecialchars(c_imgUrl . 'avatar.png');
	$imgTag = makeImg($imgClass, $imgUrl, user('displayName'));
	$imgLink = makeLink(makeUrl('user', 'show'), $imgTag);
	return $imgLink;
}

?>