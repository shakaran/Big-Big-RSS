<?php
/**
 * Feed
 * 
 * Class for handle feed operations.
 * 
 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
 * @since 0.1
 */
class Feed
{
	private static $table_name = 'ttrss_feeds';
	
	public function __construct()
	{
		
	}
	
	/**
	 * Check if the feeds table exist and it is available.
	 * 
	 * It uses a legacy method with SHOW TABLES or a simple
	 * select (limited) if the first method is not available.
	 * 
	 * @param string $database_driver The driverdatabase type for queries
	 * @return boolean TRUE if the table exists, or FALSE on error
	 */
	public static function existLegacyTable($database_driver = NULL)
	{
		global $link;

		// Try to search the table with first legacy detection method
		$result = db_query($link, "SHOW TABLES LIKE '" . self::table_name . "'", $database_driver, FALSE);
		
		if(db_num_rows($result) == 1)
		{
			return TRUE;
		}
		else 
		{
			// If there are restrictions or some problem, try a simple select method (with limited rows for avoid overhead)
			$result = db_query($link, "SELECT TRUE FROM " . self::table_name . " LIMIT 1", $database_driver, FALSE);
			
			return db_num_rows($result) == 1;
		}
	}
}