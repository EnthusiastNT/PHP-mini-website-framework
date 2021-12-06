# Full User Management and Secure mysqli Wrapper

 - secure register/login interface (with throttling and bot detection)
 - easy to use, complete database wrapper (using bind)
 - ready to use for a small, private website
 - free source code (MIT), no other dependencies (no tracking)
 - full control over the presentation (no libraries, .css included)
 - plenty of comments with explananations in the source code

Your Feedback in "Issues" is welcome! So are pull requests!

# db wrapper

To simplify database access, I forked [PHP-MySQLi-Database-Class](https://github.com/ThingEngineer/PHP-MySQLi-Database-Class) (GNU), checked the code and did not find any issues. Even though it doesn't get many updates I believe it is stable code. It's basically a wrapper for mysqli, using statements and bind_param.

The $db object is globally available with db(). Functions I use the most are getRow() and getV(), insert() and update(). While this is a class, there's no need to program in classes otherwise. I chose not to.

# installation

Just copy the files from PHP and WWW to your server. Everything in WWW is public. Everything in PHP doesn't have to be public. It can reside anywhere, just configure the path accordingly in index.php.

Search for TODOs to find all places with custom settings.

To set up the users table, call db_createMiniDb() once.

Add your own code to main.php and user.php.
