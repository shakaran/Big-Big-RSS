<?php
<<<<<<< HEAD
set_include_path(dirname(__FILE__) . '/include' . PATH_SEPARATOR . get_include_path());

/* remove ill effects of magic quotes */

if (get_magic_quotes_gpc())
{
    function stripslashes_deep($value)
    {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }

    $_POST    = array_map('stripslashes_deep', $_POST);
    $_GET     = array_map('stripslashes_deep', $_GET);
    $_COOKIE  = array_map('stripslashes_deep', $_COOKIE);
    $_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

require_once "autoload.php";
require_once 'sessions.php';
require_once 'functions.php';
require_once 'sanity_check.php';
require_once 'config.php';
require_once 'db.php';
require_once 'db-prefs.php';

startup_gettext();

$script_started = microtime(TRUE);

$link = db_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!init_plugins($link)) return;

if (ENABLE_GZIP_OUTPUT && function_exists('ob_gzhandler'))
{
    ob_start('ob_gzhandler');
}

$method = isset($_REQUEST['op']) ? $_REQUEST['op'] : NULL;

global $pluginhost;
$override = $pluginhost->lookup_handler('public', $method);

$handler = $override ? $override : new Handler_Public($link, $_REQUEST);

if (implements_interface($handler, 'IHandler') && $handler->before($method))
{
    if ($method && method_exists($handler, $method))
    {
        $handler->$method();
    }
    else if (method_exists($handler, 'index'))
    {
        $handler->index();
    }
    $handler->after();

    return;
}

// We close the connection to database.
db_close($link);

header('Content-Type: text/plain');
$json_response = array('error' => array('code' => 7));

echo json_encode($json_response);