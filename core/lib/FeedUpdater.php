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
	public function __construct()
	{
	
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
}