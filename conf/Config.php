<?php 
class Config
{
	const PROGRAM_NAME          = 'Big Big RSS';
	
	// Operate in single user mode, disables all functionality related to
	// multiple users and authentication. Enabling this assumes you have
	// your tt-rss directory protected by other means (e.g. http auth).
	const SINGLE_USER_MODE  = False;
	
	// Daemon
	const PURGE_INTERVAL        = 3600; // seconds @fixme not used?
	const MAX_CHILD_RUNTIME     = 600;  // seconds
	const MAX_JOBS              = 2;
	const DAEMON_SLEEP_INTERVAL = 60; // seconds
	
	// Files and directories
	
	const CACHE_DIR             = 'cache'; // Local cache directory for RSS feed content (default: cache).
	
	
	// Path to PHP executable, used for various command-line programs
	const PHP_EXECUTABLE        = '/usr/bin/php';
}