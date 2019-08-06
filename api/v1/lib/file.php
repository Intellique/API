<?php

function normalizePath($path) {
	$path = explode('/', $path);
	foreach ($path as $key => $value) {
		switch ($value) {
			case '..':
				unset($path[$key]);
				unset($path[$key - 1]);
				break;
			case '':
			case '.':
				unset($path[$key]);
				break;
		}
	}
	$path = array_filter($path);
	$path = '/' . implode('/', $path);
	return $path;
}

?>

