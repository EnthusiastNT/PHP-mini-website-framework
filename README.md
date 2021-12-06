# User Authentication and SQL Wrapper

 - secure register/login interface (with throttling and bot detection)
 - easy to use, complete database wrapper (using bind)
 - ready to use for a small, private website
 - free source code (MIT), no other dependencies (ie. no tracking)
 - full control over the presentation (ie. no libraries, .css included)
 - plenty of comments in the source code

Your feedback in "Issues" is welcome! So are pull requests!

# Installation

Just copy the files from PHP and WWW to your server. Everything in WWW is public. Everything in PHP doesn't have to be public. It can reside anywhere, just configure the path accordingly in index.php.

Search for TODOs to find all places with custom settings.

To set up the users table, call db_createMiniDb() once.

Add your own code to main.php and user.php.

# mySQL Wrapper

To simplify database access, I forked [PHP-MySQLi-Database-Class](https://github.com/ThingEngineer/PHP-MySQLi-Database-Class) (GNU), checked the code and did not find any issues. Even though it doesn't get many updates I believe it is stable code. It's basically a wrapper for mysqli, using statements and bind_param. It is included as sqlClass.php (while this is a class, there's no need to program in classes otherwise. I chose not to.)

The $db object is globally available with db(). Most common functions are getRow() and getV(), insert() and update(). For example:

```php
$db = db();
$db->where('ID', user('ID'));
$rowUser = $db->getRow('tblUsers', 1);
if ( $rowUser )
    echo 'name: '.$rowUser['displayName'];
```

Example for updating data:

```php
$data = array();
$data['displayName'] = 'Eastwood';
$db = db();
$db->where('ID', user('ID'));
$db->update('tblUsers', $data, 1);
```
