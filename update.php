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
	
	// Create a database connection.
	$link = db_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

	init_connection($link);

	$feed_updater = new FeedUpdater();
	$options = $feed_updater->fetchOptions()->parseOptions();

	$feed_updater->checkUsage();

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

	if (isset($options["feeds"])) {
		// Update all feeds needing a update.
		update_daemon_common($link);

		// Update feedbrowser
		$count = update_feedbrowser_cache($link);
		_debug("Feedbrowser updated, $count feeds processed.");

		// Purge orphans and cleanup tags
		purge_orphans($link, true);

		$rc = cleanup_tags($link, 14, 50000);
		_debug("Cleaned $rc cached tags.");

		global $pluginhost;
		$pluginhost->run_hooks($pluginhost::HOOK_UPDATE_TASK, "hook_update_task", $op);
	}

	if (isset($options["feedbrowser"])) {
		$count = update_feedbrowser_cache($link);
		print "Finished, $count feeds processed.\n";
	}

	if (isset($options["daemon"])) {
		while (true) {
			$quiet = (isset($options["quiet"])) ? "--quiet" : "";

			passthru(Config::PHP_EXECUTABLE . " " . $argv[0] ." --daemon-loop $quiet");
			_debug("Sleeping for " . Config::DAEMON_SLEEP_INTERVAL . " seconds...");
			sleep(Config::DAEMON_SLEEP_INTERVAL);
		}
	}

	if (isset($options["daemon-loop"])) {
		if (!Stamp::create('update_daemon.stamp')) 
		{
			_debug('Warning: unable to create stampfile' . PHP_EOL);
		}

		// Call to the feed batch update function
		// or regenerate feedbrowser cache

		if (rand(0,100) > 30) {
			update_daemon_common($link);
		} else {
			$count = update_feedbrowser_cache($link);
			_debug("Feedbrowser updated, $count feeds processed.");

			purge_orphans($link, true);

			$rc = cleanup_tags($link, 14, 50000);

			_debug("Cleaned $rc cached tags.");

			global $pluginhost;
			$pluginhost->run_hooks($pluginhost::HOOK_UPDATE_TASK, "hook_update_task", $op);
		}

	}

	$feed_updater->cleanupTagsOption();

	if (isset($options["indexes"])) {
		_debug("PLEASE BACKUP YOUR DATABASE BEFORE PROCEEDING!");
		_debug("Type 'yes' to continue.");

		if (read_stdin() != 'yes')
			exit;

		_debug("clearing existing indexes...");

		if (DB_TYPE == "pgsql") {
			$result = db_query($link, "SELECT relname FROM
				pg_catalog.pg_class WHERE relname LIKE 'ttrss_%'
					AND relname NOT LIKE '%_pkey'
				AND relkind = 'i'");
		} else {
			$result = db_query($link, "SELECT index_name,table_name FROM
				information_schema.statistics WHERE index_name LIKE 'ttrss_%'");
		}

		while ($line = db_fetch_assoc($result)) {
			if (DB_TYPE == "pgsql") {
				$statement = "DROP INDEX " . $line["relname"];
				_debug($statement);
			} else {
				$statement = "ALTER TABLE ".
					$line['table_name']." DROP INDEX ".$line['index_name'];
				_debug($statement);
			}
			db_query($link, $statement, false);
		}

		_debug("reading indexes from schema for: " . DB_TYPE);

		$fp = fopen("schema/ttrss_schema_" . DB_TYPE . ".sql", "r");
		if ($fp) {
			while ($line = fgets($fp)) {
				$matches = array();

				if (preg_match("/^create index ([^ ]+) on ([^ ]+)$/i", $line, $matches)) {
					$index = $matches[1];
					$table = $matches[2];

					$statement = "CREATE INDEX $index ON $table";

					_debug($statement);
					db_query($link, $statement);
				}
			}
			fclose($fp);
		} else {
			_debug("unable to open schema file.");
		}
		_debug("all done.");
	}

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