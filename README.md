# PHP-mini-website-framework

I developed this framework for a small, private website:
 - easy database wrapper (with bind)
 - secure register/login interface, with throttling
 - free source code (MIT), no other dependencies
 - full control over the presentation (no libraries, .css included)
 - no tracking of external downloads (fonts, .js)

Your Feedback in "Issues" is welcome! So are pull requests!

# DB Wrapper

To simplify database access, I forked [PHP-MySQLi-Database-Class](https://github.com/ThingEngineer/PHP-MySQLi-Database-Class) (GNU), checked the code and did not find any issues. Even though it doesn't get many updates I believe it is stable code. It's basically a wrapper for mysqli, using statements and bind_param.

The $db object is globally available with db(). The functions I use the most are getRow() and getV(), insert() and update(). While this is a class, there's no need to program in classes otherwise. I chose not to.

# Installation

Just copy the files from PHP and WWW to your server. Everything in WWW is public. Everything in PHP doesn't have to be public. It can reside anywhere, just configure the path accordingly in index.php.

To set up the users table call db_createMiniDb() once.

Search for TODOs to find all places with custom settings.
