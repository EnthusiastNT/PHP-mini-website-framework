<?php

/*
 * set up https://mini.local/ in your hosts file:
 *  C:\Windows\System32\drivers\etc\hosts

127.0.0.1	mini.local
::1      	mini.local


 * set up XAMPP:
 *  C:\xampp\apache\conf\extra\httpd-vhosts.conf

<VirtualHost *:443>
    ServerName mini.local
    DocumentRoot "C:/_my/_mini/www"

	RewriteEngine On
	RewriteRule ^/(one|two|three|image|user|mod)/?([^/]*)/?([^/]*)/?  \
		/index.php?area=$1&cmd=$2&token=$3 [PT,QSA]

	SSLEngine on
	SSLCertificateFile "conf/ssl.crt/server.crt"
	SSLCertificateKeyFile "conf/ssl.key/server.key"
</VirtualHost>

<Directory C:/_my/_mini >
	Require all granted
</Directory>

*/


	// where are the php files? (only index.php is called from outside)
	if( 'mini.local' == $_SERVER['HTTP_HOST'] )
	{
		define('c_isLocal', true);
		$phpRoot = 'C:/_my/_mini/php';  // this is where you build your website
		$phpMini = 'C:/_my/_mini/php/mini';  // this is where the framework files go
	}
	else
	{
		define('c_isLocal', false);
		$phpRoot = '/home/you/php...';  // TODOs your settings here
		$phpMini = '/home/you/mini...';  // TODOs your settings here
	}

	// include php files
	include_once $phpRoot . '/main.php';
	include_once $phpRoot . '/user.php';

	include_once $phpMini . '/db.php';
	include_once $phpMini . '/functions.php';
	include_once $phpMini . '/login.php';
	include_once $phpMini . '/sqlClass.php';

	// single point of entry
	main_Main();
?>