<?php

// --------------------------------------------------
function db_Connect($dbHost, $dbUser, $dbPassword, $dbName)
{
    // connect to database -> then use db() to get instance
	$db = new MysqliDb($dbHost, $dbUser, $dbPassword, $dbName, null, 'utf8mb4');
	if( !$db )
		db_DoExit();  // we can't do anything without db
	return $db;
}

// --------------------------------------------------
function db()
{
	// get db
	return MysqliDb::getInstance();
}

// --------------------------------------------------
function db_DoExit()
{
	// disconnect from db
	$db = db();
	if( isset($db) && $db )
		$db->disconnectAll();

	// that's it, send everything to client
	while (@ob_end_flush());
	exit;
}

// --------------------------------------------------
function db_createMiniDb($dbHost, $dbUser, $dbPassword, $dbName)
{
	// call this once to create tables

	// connect to mysql
	$db = db_Connect($dbHost, $dbUser, $dbPassword, null);

	// create db
	$db->rawQuery('CREATE DATABASE '.$dbName);
	$db->disconnectAll();

    // connect to new database
	$db = db_Connect($dbHost, $dbUser, $dbPassword, $dbName);


	// WARNING: format can and probably will change over time

	// create tblUsers
	$db->rawQuery('CREATE TABLE IF NOT EXISTS tblUsers (
						ID int(11) NOT NULL AUTO_INCREMENT,
						status varchar(10) NOT NULL,
						displayName varchar(30) NOT NULL,
						loginName varchar(30) NOT NULL,
						sid varchar(20) NOT NULL,
						goodLogin datetime NOT NULL,
						created date NOT NULL,
						pwHash varchar(255) NOT NULL,
						PRIMARY KEY (ID)
					);'
				);

	$db->rawQuery('CREATE TABLE IF NOT EXISTS tblThrottle (
						ID int(11) NOT NULL AUTO_INCREMENT,
						hash varchar(255) NOT NULL,
						bucket int(11) NOT NULL,
						touched datetime NOT NULL,
						PRIMARY KEY (ID)
					);'
				);

	$db->disconnectAll();
}

// --------------------------------------------------
function token_UniqueInsert($table, $data, $columnID = 'ID', $columnToken = 'token', $tokenLen = c_tokenLen)
{
	// store everything with unique token (can't insert empty token if '' already exists!)
	$db = db();
	$count = 0;
	do
	{
		$count++;
		$data[$columnToken] = func_generateToken($tokenLen);
		$insertedID = $db->insert($table, $data);

		if ( $count > 10 )
			return null;  // gotta stop somewhere... (probably something else is duplicate)
	}
	while( 1062 == $db->getLastErrNo() );  // ERROR 1062: Duplicate entry

	$data[$columnID] = $insertedID;
	return $data;  // token und ID
}

// --------------------------------------------------
function token_UniqueUpdate($table, $id, $columnID = 'ID', $columnToken = 'token', $tokenLen = c_tokenLen)
{
	$db = db();
	$data = array();
	do
	{
		$data[$columnToken] = func_generateToken($tokenLen);
		$db->where($columnID, $id);
		$db->update($table, $data);
	}
	while( 1062 == $db->getLastErrNo() );  // ERROR 1062: Duplicate entry

	return $data[$columnToken];
}

// --------------------------------------------------
function db_UpdateOrInsert($table, $data, $arrWhere)
{
	$db = db();
	foreach ( $arrWhere as $key => $value )
		$db->where($key, $value);

	// update one row
	$result = $db->update($table, $data, 1);
	if ( !$result )
	{
		// not found -> insert
		$result = $db->insert($table, $data);
	}

	return $result;
}

// --------------------------------------------------
function db_deleteWhere($table, $arrWhere, $num = 1)
{
	$db = db();
	foreach ( $arrWhere as $key => $value )
		$db->where($key, $value);

	return $db->delete($table, $num);
}

// --------------------------------------------------
function db_deleteOlder($table, $col, $olderThan)
{
	$timestamp = strtotime($olderThan);  // e.g. '-1 week'
	$datetime = date(stamp, $timestamp);
	$db = db();
	$db->where($col, $datetime, '<');
	$db->delete($table, 1);
}


?>