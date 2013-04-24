<?php
set_include_path(dirname(__FILE__) . '/include' . PATH_SEPARATOR . get_include_path());

require_once 'functions.php';

$ERRORS = array();

$ERRORS[] = '';

$ERRORS[] = __('This program requires XmlHttpRequest ' .
		       'to function properly. Your browser doesn\'t seem to support it.');

$ERRORS[] = __('This program requires cookies ' .
		       'to function properly. Your browser doesn\'t seem to support them.');

$ERRORS[] = __('Backend sanity check failed.');

$ERRORS[] = __('Frontend sanity check failed.');

$ERRORS[] = __('Incorrect database schema version. &lt;a href="db-updater.php"&gt;Please update&lt;/a&gt;.');

$ERRORS[] = __('Request not authorized.');

$ERRORS[] = __('No operation to perform.');

$ERRORS[] = __('Could not display feed: query failed. Please check label match syntax or local configuration.');

$ERRORS[] = __('Denied. Your access level is insufficient to access this page.');

$ERRORS[] = __('Configuration check failed');

$ERRORS[] = __('Your version of MySQL is not currently supported. Please see official site for more information.');

$ERRORS[] = '[This error is not returned by server]';

$ERRORS[] = __('SQL escaping test failed, check your database and PHP configuration');

if ($_REQUEST['mode'] == 'js')
{
	header('Content-Type: text/plain; charset=UTF-8');

	echo 'var ERRORS = [];' . PHP_EOL;

	foreach ($ERRORS as $id => $error)
	{
		$error = preg_replace("/\n/", '', $error);
		$error = preg_replace("/\"/", "\\\"", $error);

		echo 'ERRORS[$id] = "' . $error . '";'' . PHP_EOL;
	}
}