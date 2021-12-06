# PHP-mini-website-framework

I developed this framework for a small, private website:
 - easy database wrapper (with bind)
 - secure register/login interface, with throttling
 - free source code (MIT), no other dependencies
 - full control over the presentation (no libraries, .css included)
 - no tracking of external downloads (fonts, .js)

I hope I can set up a server specifically for testing this framework in the near future.

Your Feedback in "Issues" is welcome! So are pull requests!

# DB Wrapper

I forked [PHP-MySQLi-Database-Class](https://github.com/ThingEngineer/PHP-MySQLi-Database-Class) (GNU), checked the code and did not find any issues. Even though it doesn't get many updates I think it is stable code. It's basically a wrapper for mysqli, using statements and bind_param.

I couldn't wish for an easier usage. The $db object is globally available with db(). The functions I use the most are getRow() and getV(), insert() and update(). While this is a class, there's no need to program in classes otherwise. I chose not to.

I did update a few comments and added the functions getRow() to make it more clear this is one row, and getV() for just one value. See sqlClass.php in this repository.


