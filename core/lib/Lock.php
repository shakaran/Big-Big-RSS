<?php
/**
 * Lock
 * 
 * Class for create and handle locks.
 * 
 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
 * @since 0.1
 */
class Lock
{
	/**
	 * Create a lock.
	 *
	 * Additionally fetch the options in plugin hooks.
	 *
	 * @author Ángel Guzmán Maeso <shakaran@gmail.com>
	 * @param string $lock_filename The name of lock file.
	 * @return resource A file pointer resource on success, or FALSE on error
	 */
	public static function create($lock_filename = NULL)
	{
		$fp = fopen(LOCK_DIRECTORY . '/' . $lock_filename, 'w');
		
		if ($fp && flock($fp, LOCK_EX | LOCK_NB)) 
		{
			if (function_exists('posix_getpid')) 
			{
				fwrite($fp, posix_getpid() . PHP_EOL);
			}
			
			return $fp;
		} 
		else 
		{
			return FALSE;
		}
	}
}