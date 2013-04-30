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
	
	/** string $lock_filename The file name for update lock */
	private $lock_filename = 'update.lock';
	
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
	 * @access private
	 * @return void
	 */
	private static function showUsage()
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
	 * @access private
	 * @return void
	 */
	private function showHtmlUsage()
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
	
	/**
	 * Check if it is needed display the usage and exits.
	 * 
	 * Display the usage in STDIN and Web mode or if the
	 * flag helps is enabled.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return void
	 */
	public function checkUsage()
	{
		if((count($this->longopts) == 0 && !is_array($this->longopts)) || isset($this->longopts['help']))
		{
			if(!defined('STDIN'))
			{
				$this->showHtmlUsage();
			}
			else
			{
				$this->showUsage();
				
				global $pluginhost;
				
				foreach($pluginhost->get_commands() as $command => $data) 
				{
					printf(" --%-19s - %s\n", $command . ' ' . $data['arghelp'], $data['description']);
				}
			}
			
			exit;
		}
	}
	
	/**
	 * Detect log option.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return void
	 */
	public function logOption()
	{
		if(isset($this->longopts['log'])) 
		{
			_debug('Logging to ' . $this->longopts['log']);
			define('LOGFILE', $this->longopts['log']);
		}
	}
	
	/**
	 * Detect cleanup tags option.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return void
	 */
	public function cleanupTagsOption()
	{
		if(isset($this->longopts['cleanup-tags']))
		{
			$this->cleanupTagsDefault();
		}
	}
	
	/**
	 * Clean the remaining tags cached.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @access private
	 * @return void
	 */
	private function cleanupTagsDefault()
	{
		global $link; /** FIXME */
			
		$limit = 50000;
		$days  = 14;
			
		$number_tags_deleted = cleanup_tags($link, $days, $limit);
		_debug($number_tags_deleted . ' tags deleted.' . PHP_EOL);
	}
	
	/**
	 * Detect task option.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @access private
	 * @return void
	 */
	private function taskOption()
	{
		if (isset($this->longopts['task']))
		{
			_debug('Using task id ' . $this->longopts['task']);
	
			$this->lock_filename = $this->lock_filename . '-task_' . $this->longopts['task'];
		}
	}
	
	/**
	 * Detect force update option.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return void
	 */
	public function forceUpdateOption()
	{
		if (isset($this->longopts['force-update'])) 
		{
			_debug('Marking all feeds as needing update...');
		
			db_query($link, "UPDATE ttrss_feeds 
							 SET last_update_started = '1970-01-01',
					             last_updated = '1970-01-01'");
		}
	}
	
	/**
	 * Get the lock filename.
	 * 
	 * The filename depends if is daemon mode running or not
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return string The lock filename
	 */
	public function getLockFileName()
	{
		if(isset($this->longopts['daemon'])) 
		{
			$this->lock_filename = 'update_daemon.lock';
		} 
		
		$this->taskOption();
		
		return $this->lock_filename;
	}
	
	/**
	 * Detect feeds option.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return void
	 */
	public function feedsOption()
	{
		global $pluginhost, $link; /** FIXME */
		
		if(isset($this->longopts['feeds']))
		{
			// Update all feeds needing a update.
			update_daemon_common($link);
		
			// Update feedbrowser
			$count = update_feedbrowser_cache($link);
			_debug('Feedbrowser updated, ' . $count . ' feeds processed.');
		
			// Purge orphans and cleanup tags
			purge_orphans($link, TRUE);
		
			$this->cleanupTagsDefault();

			$pluginhost->run_hooks($pluginhost::HOOK_UPDATE_TASK, 'hook_update_task', $this->longopts);
		}
	}
	
	/**
	 * Detect feedbrowser option.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @return void
	 */
	public function feedBrowserOption()
	{
		if(isset($this->longopts['feedbrowser']))
		{
			$count = update_feedbrowser_cache($link);
			echo'"Finished, ' . $count . ' feeds processed.' . PHP_EOL;
		}
	}
}