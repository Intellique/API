<?php
	$plugins = array();
	function registerPlugin($event, $callback) {
		global $plugins;

		if (array_key_exists($event, $plugins))
			array_push($plugins[$event], $callback);
		else
			$plugins[$event] = array($callback);
	}

	function triggerEvent($event, ...$args) {
		global $plugins;

		$returns = array();
		if (array_key_exists($event, $plugins))
			foreach ($plugins[$event] as $evt)
				array_push($returns, $evt($event, ...$args));

		return $returns;
	}

	function checkEventValues(&$values) {
		return !in_array(false, $values);
	}

	foreach (glob(dirname(__FILE__) . '/plugins-enabled/*.php') as $plugin) {
		require_once($plugin);
		$init_function = 'plugin_init_' . str_replace('-', '_', basename(realpath($plugin), '.php'));
		$plugin_filename = $plugin;
		$plugin_realpath = realpath($plugin);
		$init_function($plugin_filename, $plugin_realpath);
	}
?>
