<?php
/**
 * FeedUpdater
 * 
 * Script for update the feeds.
 * 
 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
 * @since 0.1
 */
class FeedUpdater
{
	private $longopts = array('feeds',
							  'feedbrowser',
							  'daemon',
							  'daemon-loop',
							  'task:',
							  'cleanup-tags',
							  'quiet',
							  'log:',
							  'indexes',
							  'update-schema',
							  'convert-filters',
							  'force-update',
							  'list-plugins',
							  'help'
							 );
	
	public function __construct()
	{
	
	}
	
	/**
	 * Fetch the options for feed updater.
	 * 
	 * Additionally fetch the options in plugin hooks.
	 * 
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return FeedUpdater A FeedUpdater instance
	 */
	public function fetchOptions()
	{
		global $pluginhost;
		
		// Run plugin hook for get more commands from plugins
		foreach ($pluginhost->get_commands() as $command => $data) 
		{
			$this->longopts[] = $command . $data['suffix'];
		}
		
		return $this;
	}
	
	/**
	 * Parse the options for feed updater.
	 *
	 * It uses getopt default php's native implementation.
	 * 
	 * @todo In future use a better approach with GetOpt.PHP library
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return FeedUpdater A FeedUpdater instance
	 */
	public function parseOptions()
	{
		return getopt('', $this->longopts);
	}
	
	/**
	 * Show program usage
	 * 
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return void
	 */
	public static function showUsage()
	{
		echo Config::PROGRAM_NAME . ' data update script.' . PHP_EOL . PHP_EOL .
		     'Options:\n' . PHP_EOL .
		     '  --feeds              - update feeds' . PHP_EOL .
		     '  --feedbrowser        - update feedbrowser' . PHP_EOL .
		     '  --daemon             - start single-process update daemon' . PHP_EOL .
		     '  --task N             - create lockfile using this task id' . PHP_EOL .
		     '  --cleanup-tags       - perform tags table maintenance' . PHP_EOL .
		     '  --quiet              - don\'t output messages to stdout' . PHP_EOL .
		     '  --log FILE           - log messages to FILE' . PHP_EOL .
		     '  --indexes            - recreate missing schema indexes' . PHP_EOL .
		     '  --update-schema      - update database schema' . PHP_EOL .
		     '  --convert-filters    - convert type1 filters to type2' . PHP_EOL .
		     '  --force-update       - force update of all feeds' . PHP_EOL .
		     '  --list-plugins       - list all available plugins' . PHP_EOL .
		     '  --help               - show this help' . PHP_EOL .
		     'Plugin options:' . PHP_EOL;
	}
	
	/**
	 * Show program html usage
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return void
	 */
	public static function showHtmlUsage()
	{
		echo '<html>
		<head>
			<title>' . Config::PROGRAM_NAME . ' data update script.</title>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
			<link rel="stylesheet" type="text/css" href="utility.css">
		</head>

		<body>
			<div class="floatingLogo"><img src="images/logo_small.png"></div>
			<h1>' . __(Config::PROGRAM_NAME . " data update script.") . '</h1>';

			print_error("Please run this script from the command line. Use option \"-help\" to display command help if this error is displayed erroneously.");

		echo '</body>
		</html>';
	}
}