<?php
function __autoload($class) 
{
	$class_file = str_replace('_', '/', strtolower(basename($class)));

	$file = dirname(__FILE__) . '/../classes/' . $class . '_file.php';
	
	if (file_exists($file)) 
	{
		require_once $file;
	}
	else
	{
	    $file = dirname(__FILE__) . '/../classes/' . strtolower($class) . '.php';
	    
	    if (file_exists($file))
	    {
	        require_once $file;
	    }
	    else 
	    {
	        $file = dirname(__FILE__) . '/../classes/' . $class_file . '.php';
	        
	        if (file_exists($file))
	        {
	            require_once $file;
	        }
	    }
	}
}