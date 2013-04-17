#!/usr/bin/env php
<?php
	set_include_path(dirname(__FILE__) ."/include" . PATH_SEPARATOR .
		get_include_path());

	define('DISABLE_SESSIONS', true);

	chdir(dirname(__FILE__));

	require_once 'conf/Config.php';
	
	require_once "functions.php";
	require_once "rssfuncs.php";
	require_once "config.php";
	require_once "sanity_check.php";
	require_once "db.php";
	require_once "db-prefs.php";
	
	/** @todo Loader (Autoloader class) */
	require_once 'core/lib/FeedUpdater.php';
	require_once 'core/lib/Lock.php';
	require_once 'core/lib/Stamp.php';
	require_once "errorhandler.php";

	// Create a database connection.
	$link = db_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

	init_connection($link);

	$feed_updater = new FeedUpdater();
	$options = $feed_updater->fetchOptions()->parseOptions();

	$feed_updater->checkUsage();

	if (!isset($options['update-schema'])) {
		$schema_version = get_schema_version($link);

		if ($schema_version != SCHEMA_VERSION) {
			die("Schema version is wrong, please upgrade the database.\n");
		}
	}

	define('QUIET', isset($options['quiet']));

	$feed_updater->logOption();

	$lock_filename = $feed_updater->getLockFileName();

	$lock_handle = Lock::create($lock_filename);

	// Try to lock a file in order to avoid concurrent update.
	if (!$lock_handle) {
		die("error: Can't create lockfile ($lock_filename). ".
			"Maybe another update process is already running.\n");
	}

	$feed_updater->forceUpdateOption();
	$feed_updater->feedsOption();
	$feed_updater->feedBrowserOption();
	$feed_updater->daemonOption();
	$feed_updater->daemonLoopOption();
	$feed_updater->cleanupTagsOption();
	$feed_updater->indexesOption();

	if (isset($options["convert-filters"])) {
		_debug("WARNING: this will remove all existing type2 filters.");
		_debug("Type 'yes' to continue.");

		if (read_stdin() != 'yes')
			exit;

		_debug("converting filters...");

		db_query($link, "DELETE FROM ttrss_filters2");

		$result = db_query($link, "SELECT * FROM ttrss_filters ORDER BY id");

		while ($line = db_fetch_assoc($result)) {
			$owner_uid = $line["owner_uid"];

			// date filters are removed
			if ($line["filter_type"] != 5) {
				$filter = array();

				if (sql_bool_to_bool($line["cat_filter"])) {
					$feed_id = "CAT:" . (int)$line["cat_id"];
				} else {
					$feed_id = (int)$line["feed_id"];
				}

				$filter["enabled"] = $line["enabled"] ? "on" : "off";
				$filter["rule"] = array(
					json_encode(array(
						"reg_exp" => $line["reg_exp"],
						"feed_id" => $feed_id,
						"filter_type" => $line["filter_type"])));

				$filter["action"] = array(
					json_encode(array(
						"action_id" => $line["action_id"],
						"action_param_label" => $line["action_param"],
						"action_param" => $line["action_param"])));

				// Oh god it's full of hacks

				$_REQUEST = $filter;
				$_SESSION["uid"] = $owner_uid;

				$filters = new Pref_Filters($link, $_REQUEST);
				$filters->add();
			}
		}

	}

	if (isset($options["update-schema"])) {
		_debug("checking for updates (" . DB_TYPE . ")...");

		$updater = new DbUpdater($link, DB_TYPE, SCHEMA_VERSION);

		if ($updater->isUpdateRequired()) {
			_debug("schema update required, version " . $updater->getSchemaVersion() . " to " . SCHEMA_VERSION);
			_debug("WARNING: please backup your database before continuing.");
			_debug("Type 'yes' to continue.");

			if (read_stdin() != 'yes')
				exit;

			for ($i = $updater->getSchemaVersion() + 1; $i <= SCHEMA_VERSION; $i++) {
				_debug("performing update up to version $i...");

				$result = $updater->performUpdateTo($i);

				_debug($result ? "OK!" : "FAILED!");

				if (!$result) return;

			}
		} else {
			_debug("update not required.");
		}

	}

	if (isset($options["list-plugins"])) {
		$tmppluginhost = new PluginHost($link);
		$tmppluginhost->load_all($tmppluginhost::KIND_ALL);
		$enabled = array_map("trim", explode(",", PLUGINS));

		echo "List of all available plugins:\n";

		foreach ($tmppluginhost->get_plugins() as $name => $plugin) {
			$about = $plugin->about();

			$status = $about[3] ? "system" : "user";

			if (in_array($name, $enabled)) $name .= "*";

			printf("%-50s %-10s v%.2f (by %s)\n%s\n\n",
				$name, $status, $about[0], $about[2], $about[1]);
		}

		echo "Plugins marked by * are currently enabled for all users.\n";

	}

	$pluginhost->run_commands($options);

	db_close($link);

	if ($lock_handle != false) {
		fclose($lock_handle);
	}

	if (file_exists(LOCK_DIRECTORY . "/$lock_filename"))
		unlink(LOCK_DIRECTORY . "/$lock_filename");
