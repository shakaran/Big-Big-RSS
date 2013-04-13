<?php
	/*
	 * WARNING!
	 *
	 * If you modify this file, you are ON YOUR OWN!
	 *
	 * Believe it or not, all of the checks below are required to succeed for
	 * tt-rss to actually function properly.
	 *
	 * If you think you have a better idea about what is or isn't required, feel
	 * free to modify the file, note though that you are therefore automatically
	 * disqualified from any further support by official channels, e.g. tt-rss.org
	 * issue tracker or the forums.
	 *
	 * If you come crying when stuff inevitably breaks, you will be mocked and told
	 * to get out. */

	function make_self_url_path() {
		$url_path = ($_SERVER['HTTPS'] != "on" ? 'http://' :  'https://') . $_SERVER["HTTP_HOST"] . parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

		return $url_path;
	}

	function initial_sanity_check($link) {

		$errors = array();

        if(file_exists('../conf/Config.php'))
        {
            require_once '../conf/Config.php';
        }
        else 
        {
            require_once 'conf/Config.php';
        }
		
		if (!file_exists("config.php")) {
			$errors[] = "Configuration file not found. Looks like you forgot to copy config.php-dist to config.php and edit it.";
		} else {

			# This code has been generated at:  Mon Apr 1 18:30:54 IDT 2013
			define('GENERATED_CONFIG_CHECK', 26);
			$required_defines = array( 'DB_TYPE',
					'DB_HOST',
					'DB_USER',
					'DB_NAME',
					'DB_PASS',
					'MYSQL_CHARSET',
					'SELF_URL_PATH',
					'SINGLE_USER_MODE',
					'SIMPLE_UPDATE_MODE',
					'LOCK_DIRECTORY',
					'CACHE_DIR',
					'ICONS_DIR',
					'ICONS_URL',
					'AUTH_AUTO_CREATE',
					'AUTH_AUTO_LOGIN',
					'FORCE_ARTICLE_PURGE',
					'PUBSUBHUBBUB_HUB',
					'PUBSUBHUBBUB_ENABLED',
					'SPHINX_ENABLED',
					'SPHINX_INDEX',
					'ENABLE_REGISTRATION',
					'REG_NOTIFY_ADDRESS',
					'REG_MAX_USERS',
					'SESSION_COOKIE_LIFETIME',
					'SESSION_CHECK_ADDRESS',
					'SMTP_FROM_NAME',
					'SMTP_FROM_ADDRESS',
					'DIGEST_SUBJECT',
					'SMTP_HOST',
					'SMTP_PORT',
					'SMTP_LOGIN',
					'SMTP_PASSWORD',
					'CHECK_FOR_NEW_VERSION',
					'ENABLE_GZIP_OUTPUT',
					'PLUGINS',
					'CONFIG_VERSION'
			);
			
			if (file_exists("install") && !file_exists("config.php")) {
				$errors[] = "Please copy config.php-dist to config.php or run the installer in install/";
			}

			if (strpos(PLUGINS, "auth_") === FALSE) {
				$errors[] = "Please enable at least one authentication module via PLUGINS constant in config.php";
			}

			if (function_exists('posix_getuid') && posix_getuid() == 0) {
				$errors[] = "Please don't run this script as root.";
			}

			if (version_compare(PHP_VERSION, '5.3.0', '<')) {
				$errors[] = "PHP version 5.3.0 or newer required.";
			}

			if (CONFIG_VERSION != Config::EXPECTED_CONFIG_VERSION) {
				$errors[] = "Configuration file (config.php) has incorrect version. Update it with new options from config.php-dist and set CONFIG_VERSION to the correct value.";
			}

			if (!is_writable(Config::CACHE_DIR . "/images")) {
				$errors[] = "Image cache is not writable (chmod -R 777 ".Config::CACHE_DIR."/images)";
			}

			if (!is_writable(Config::CACHE_DIR . "/export")) {
				$errors[] = "Data export cache is not writable (chmod -R 777 ".Config::CACHE_DIR."/export)";
			}
			
			if (!is_writable(Config::CACHE_DIR . "/upload")) {
				$errors[] = "Upload cache is not writable (chmod -R 777 ".Config::CACHE_DIR."/upload)";
			}

			if (!is_writable(Config::CACHE_DIR . "/js")) {
				$errors[] = "Javascript cache is not writable (chmod -R 777 ".Config::CACHE_DIR."/js)";
			}

			if (GENERATED_CONFIG_CHECK != Config::EXPECTED_CONFIG_VERSION) {
				$errors[] = "Configuration option checker sanity_config.php is outdated, please recreate it using ./utils/regen_config_checks.sh";
			}

			if (strlen(FEED_CRYPT_KEY) != 24) {
				array_push($errors, "FEED_CRYPT_KEY should be exactly 24 characters in length.");
			}

			if (strlen(FEED_CRYPT_KEY) != 0 && !function_exists("mcrypt_decrypt")) {
				array_push($errors, "FEED_CRYPT_KEY requires mcrypt functions which are not found.");
			}

			$config_reflector = new ReflectionClass('Config');
			$config_constants = $config_reflector->getConstants();
			
			foreach ($required_defines as $required_define) 
			{
				if (!in_array($required_define, array_keys($config_constants)))
				{
    				if(!defined($required_define)) 
    				{
    					$errors[] = 'Required configuration file parameter ' . $required_define . ' is not defined in config.php or conf/Config.php. You might need to copy it from config.php-dist.';
    				}
				}
			}

			if (Config::SINGLE_USER_MODE) 
			{
				$link = db_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

				if ($link) 
				{
					$result = db_query($link, "SELECT id FROM ttrss_users WHERE id = 1");

					if (db_num_rows($result) != 1) 
					{
						$errors[] = "SINGLE_USER_MODE is enabled in config.php but default admin account is not found.";
					}
				}
			}

			if (SELF_URL_PATH == "http://yourserver/tt-rss/") {
				$urlpath = preg_replace("/\w+\.php$/", "", make_self_url_path());

				$errors[] = "Please set SELF_URL_PATH to the correct value for your server (possible value: <b>$urlpath</b>)";
			}

			if (!is_writable(ICONS_DIR)) {
				$errors[] = "ICONS_DIR defined in config.php is not writable (chmod -R 777 ".ICONS_DIR.").\n";
			}

			if (!is_writable(LOCK_DIRECTORY)) {
				$errors[] = "LOCK_DIRECTORY defined in config.php is not writable (chmod -R 777 ".LOCK_DIRECTORY.").\n";
			}

			if (ini_get("open_basedir")) {
				$errors[] = "PHP configuration option open_basedir is not supported. Please disable this in PHP settings file (php.ini).";
			}

			if (!function_exists("curl_init") && !ini_get("allow_url_fopen")) {
				$errors[] = "PHP configuration option allow_url_fopen is disabled, and CURL functions are not present. Either enable allow_url_fopen or install PHP extension for CURL.";
			}

			if (!function_exists("json_encode")) {
				$errors[] = "PHP support for JSON is required, but was not found.";
			}

			if (DB_TYPE == "mysql" && !function_exists("mysql_connect")) {
				$errors[] = "PHP support for MySQL is required for configured DB_TYPE in config.php.";
			}

			if (DB_TYPE == "pgsql" && !function_exists("pg_connect")) {
				$errors[] = "PHP support for PostgreSQL is required for configured DB_TYPE in config.php";
			}

			if (!function_exists("mb_strlen")) {
				$errors[] = "PHP support for mbstring functions is required but was not found.";
			}

			if (!function_exists("hash")) {
				$errors[] = "PHP support for hash() function is required but was not found.";
			}

			if (!function_exists("ctype_lower")) {
				$errors[] = "PHP support for ctype functions are required by HTMLPurifier.";
			}

			if (!function_exists("iconv")) {
				$errors[] = "PHP support for iconv is required to handle multiple charsets.";
			}

			/* if (ini_get("safe_mode")) {
				array_push($errors, "PHP safe mode setting is not supported.");
			} */

			if ((PUBSUBHUBBUB_HUB || PUBSUBHUBBUB_ENABLED) && !function_exists("curl_init")) {
				$errors[] = "PHP support for CURL is required for PubSubHubbub.";
			}

			if (!class_exists("DOMDocument")) {
				$errors[] = "PHP support for DOMDocument is required, but was not found.";
			}
		}

		if (count($errors) > 0 && $_SERVER['REQUEST_URI']) { ?>
			<html>
			<head>
			<title>Startup failed</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
				<link rel="stylesheet" type="text/css" href="utility.css">
			</head>
		<body>
		<div class="floatingLogo"><img src="images/logo_small.png"></div>
			<div class="content">

			<h1>Startup failed</h1>

			<p><?php echo Config::PROGRAM_NAME; ?> was unable to start properly. This usually means a misconfiguration or an incomplete upgrade. Please fix
			errors indicated by the following messages:</p>

			<?php foreach ($errors as $error) { echo format_error($error); } ?>

			<p>You might want to check tt-rss <a href="http://tt-rss.org/wiki">wiki</a> or the
				<a href="http://tt-rss.org/forum">forums</a> for more information. Please search the forums before creating new topic
				for your question.</p>

		</div>
		</body>
		</html>

		<?php
			die;
		} else if (count($errors) > 0) {
			echo Config::PROGRAM_NAME . ' was unable to start properly. This usually means a misconfiguration or an incomplete upgrade.\n';
			echo "Please fix errors indicated by the following messages:\n\n";

			foreach ($errors as $error) {
				echo " * $error\n";
			}

			echo "\nYou might want to check tt-rss wiki or the forums for more information.\n";
			echo "Please search the forums before creating new topic for your question.\n";

			exit(-1);
		}
	}

	initial_sanity_check($link);
