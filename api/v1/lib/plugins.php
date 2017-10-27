<?php
	$plugins = array();
	function registerPlugin($event, $callback) {
		global $plugins;

		if (!array_key_exists($event, $plugins))
			$plugins[$event] = array($callback);
		else
			array_push($plugins[$event], $callback);
	}

	function triggerEvent($event, ...$args) {
		global $plugins;

		if (!array_key_exists($event, $plugins))
			return;

		$returns = array();
		foreach ($plugins[$event] as $evt)
			array_push($returns, $evt($event, ...$args));

		return $returns;
	}

	function checkEventValues(&$values) {
		return !in_array(false, $values);
	}

	foreach (glob(dirname(__FILE__) . '/plugins-enabled/*.php') as $plugin) {
		require_once($plugin);
		$init_function = 'plugin_init_' . basename(realpath($plugin), '.php');
		$plugin_filename = basename($plugin);
		$plugin_realpath = basename(realpath($plugin));
		$init_function($plugin_filename, $plugin_realpath);
	}
?>
