<?php
$autoPath = __DIR__;
$path = get_include_path();

if (strpos($path.PATH_SEPARATOR, $autoPath.PATH_SEPARATOR) === false)
	set_include_path($path.PATH_SEPARATOR.$autoPath);

spl_autoload_register();