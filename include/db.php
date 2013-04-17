<?php
function db_connect($host, $user, $pass, $db) {
	switch(DB_TYPE) {
		case "pgsql":
			$string = "dbname=$db user=$user";
			
			if ($pass) {
				$string .= " password=$pass";
			}
			
			if ($host) {
				$string .= " host=$host";
			}
			
			if (defined('DB_PORT') && DB_PORT) {
				$string = "$string port=" . DB_PORT;
			}
			
			$link = pg_connect($string);
			
			if (!$link) {
				die("Unable to connect to database (as $user to $host, database $db):" . pg_last_error());
			}
			
			return $link;
		break;
		case "mysql":
			$link = mysql_connect($host, $user, $pass);
			if ($link) {
				$result = mysql_select_db($db, $link);
				if (!$result) {
					die("Can't select DB: " . mysql_error($link));
				}
				return $link;
			} else {
				die("Unable to connect to database (as $user to $host, database $db): " . mysql_error());
			}
		break;
		case "mysqli":
			// The hostname localhost has a special meaning. It is bound to the
			// use of Unix domain sockets. It is not possible to open a TCP/IP
			// connection using the hostname localhost you must use 127.0.0.1
			// instead.
			$link = new mysqli($host, $user, $pass, $db, 3306); // @todo Declare $port = 3306; and avoid hard coding here
			$link = mysql_connect($host, $user, $pass);
			
			$link->report_mode = MYSQLI_REPORT_ALL; // MYSQLI_REPORT_STRICT
			
			if ($link->connect_errno) {
				die("Unable to connect to database (as $user to $host, database $db): ERROR[" . $link->connect_errno . "] " . $link->connect_error);
			} else {
				return $link;
			}
		break;
		case "pdo": // @todo
		break;
	}
	
	// return Db::get()->connect($host, $user, $pass, $db, 0);
}

function db_escape_string($link, $s, $strip_tags = true) {
	if ($strip_tags) $s = strip_tags($s);

	switch(DB_TYPE) {
		case "pgsql":
			return pg_escape_string($link, $s);
		break;
		case "mysql":
			return mysql_real_escape_string($s, $link);
		break;
		case "mysqli":
			return $link->real_escape_string($s);
		break;
		case "pdo": // @todo
		break;
	}
	
	// return Db::get()->escape_string($s, $strip_tags);
}

function db_query($link, $query, $die_on_error = true) {
	switch(DB_TYPE) {
		case "pgsql":
			$result = pg_query($link, $query);
			if (!$result) {
				$query = htmlspecialchars($query); // just in case
				if ($die_on_error) {
					die("Query <i>$query</i> failed [$result]: " . ($link ? pg_last_error($link) : "No connection"));
				}
			}
			return $result;
		break;
		case "mysql":
			$result = mysql_query($query, $link);
			if (!$result) {
				$query = htmlspecialchars($query);
				if ($die_on_error) {
					die("Query <i>$query</i> failed: " . ($link ? mysql_error($link) : "No connection"));
				}
			}
			return $result;
		break;
		case "mysqli":
			if(!$result) {
				$query = htmlspecialchars($query);
				if ($die_on_error) {
					die("Query <i>$query</i> failed: Error[" . $link->errno . "]: " . $link->error);
				}
			}

			return $result;
		break;
		case "pdo": // @todo
		break;
	}
	
	// 	return Db::get()->query($query, $die_on_error);
}

function db_fetch_assoc($result) {
	switch(DB_TYPE) {
		case "pgsql":
			return pg_fetch_assoc($result);
		break;
		case "mysql":
			return mysql_fetch_assoc($result);
		break;
		case "mysqli":
			return $result->fetch_assoc();
		break;
		case "pdo": // @todo
		break;
	}
	// return Db::get()->fetch_assoc($result);
}

function db_num_rows($result) {
	switch(DB_TYPE) {
		case "pgsql":
			return pg_num_rows($result);
		break;
		case "mysql":
			return mysql_num_rows($result);
		break;
		case "mysqli":
			return $result->num_rows;
		break;
		case "pdo": // @todo
		break;
	}
	
	// return Db::get()->num_rows($result);
}

function db_fetch_result($result, $row, $param) {
	switch(DB_TYPE) {
		case "pgsql":
			return pg_fetch_result($result, $row, $param);
		break;
		case "mysql":
			// I hate incoherent naming of PHP functions
			return mysql_result($result, $row, $param);
		break;
		case "mysqli":
			if($result->num_rows == 0) {
				return FALSE;
			}
			$result->data_seek($row);
			$data = $result->fetch_assoc();
			return $data[$param];
		break;
		case "pdo": // @todo
		break;
	}
	
	// return Db::get()->fetch_result($result, $row, $param);
}

function db_unescape_string($str) {
	$tmp = str_replace("\\\"", "\"", $str);
	$tmp = str_replace("\\'", "'", $tmp);
	return $tmp;
}

function db_close($link) {
	switch(DB_TYPE) {
		case "pgsql":
			return pg_close($link);
		break;
		case "mysql":
			return mysql_close($link);
		break;
		case "mysqli":
			// Per process for PHP 5.3, no needed PHP 5.4
			$link->report_mode = MYSQLI_REPORT_OFF;
			
			$link->close();
		break;
		case "pdo": // @todo
		break;
	}
	
	// return Db::get()->close();
}

function db_affected_rows($link, $result) {
	switch(DB_TYPE) {
		case "pgsql":
			return pg_affected_rows($result);
		break;
		case "mysql":
			return mysql_affected_rows($link);
		break;
		case "mysqli":
			return $link->affected_rows;
		break;
		case "pdo": // @todo
		break;
	}
	
	// return Db::get()->affected_rows($result);
}

function db_last_error($link) {
	switch(DB_TYPE) {
		case "pgsql":
			return pg_last_error($link);
		break;
		case "mysql":
			return mysql_error($link);
		break;
		case "mysqli":
			return $link->error;
		break;
		case "pdo": // @todo
		break;
	}
	// return Db::get()->last_error();
}

function db_quote($str){
	return Db::get()->quote($str);
}