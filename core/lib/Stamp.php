<?php
/**
 * Stamp
 * 
 * Class for create and handle stamp files.
 * 
 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
 * @since 0.1
 */
class Stamp
{
	/**
	 * Create a stamp.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @param string $stamp_filename The name of stamp file.
	 * @return boolean TRUE on success, or FALSE on error
	 */
	public static function create($stamp_filename = NULL)
	{
		$fp = fopen(LOCK_DIRECTORY . '/' . $stamp_filename, 'w');
		
		if ($fp && flock($fp, LOCK_EX | LOCK_NB)) 
		{
			fwrite($fp, time() . PHP_EOL);
			flock($fp, LOCK_UN);
			fclose($fp);
			
			return TRUE;
		} 
		else 
		{
			return FALSE;
		}
	}
}