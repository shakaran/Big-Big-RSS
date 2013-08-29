<?php
	error_reporting(E_ERROR | E_WARNING | E_PARSE);

	header('Content-Type: text/html; charset=utf-8');

	define('MOBILE_VERSION', true);

	$basedir = dirname(dirname(dirname(__FILE__)));

	set_include_path(
		dirname(__FILE__) . PATH_SEPARATOR .
		$basedir . PATH_SEPARATOR .
		"$basedir/include" . PATH_SEPARATOR .
		get_include_path());

	require_once "config.php";
	require_once "mobile-functions.php";

	$link = db_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

	init_plugins($link);

	login_sequence($link, true);

	$use_cats = mobile_get_pref($link, 'ENABLE_CATS');
	$offset = (int) db_escape_string($link, $_REQUEST["skip"]);

	if ($use_cats) {
		render_categories_list($link);
	} else {
		render_flat_feed_list($link, $offset);
	}
