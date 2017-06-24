<?php
	header('Content-Type: application/json');
	error_reporting(E_ALL);
	ini_set('display_errors', '1');

	require_once('auth.php');

	$methods = array(
		'auth.login' => array('auth', 'auth'),
		'auth.logout' => array('auth', 'logout'),
		'auth.passwd' => array('auth', 'passwd'),
		'stats' => array('stats', 'stats', 'noauth' => true),
		'wifi.status' => array('wifi', 'wifi_status', 'noauth' => true),
		'wifi.scan' => array('wifi', 'wifi_scan'),
		'wifi.restart' => array('wifi', 'wifi_restart'),
		'config.wifi.apkey.get' => array('wifi', 'wifi_ap_key_get'),
		'config.wifi.apkey.set' => array('wifi', 'wifi_ap_key_set'),
		'config.wifi.aps.get' => array('wifi', 'wifi_client_aps_get'),
		'config.wifi.aps.add' => array('wifi', 'wifi_client_aps_add'),
		'config.wifi.aps.edit' => array('wifi', 'wifi_client_aps_edit'),
		'config.wifi.aps.delete' => array('wifi', 'wifi_client_aps_delete'),
		'config.timezone.get' =>  array('misc', 'timezone_get'),
		'config.timezone.set' =>  array('misc', 'timezone_set'),
		'config.baking.global.get' => array('programs', 'global_config_get'),
		'config.baking.global.set' => array('programs', 'global_config_set'),
		'config.baking.stages.get' => array('programs', 'stages_get'),
		'config.baking.stages.set' => array('programs', 'stages_set'),
		'config.baking.stages.get.all' => array('programs', 'stages_get_all'),
		'baking.bake' => array('commands', 'bake'),
		'baking.abort' => array('commands', 'abort'),
		'noerr' =>  array('commands', 'dismiss_error'),
		'firmware.flash' =>  array('firmware', 'flash'),
		'emu.temp' =>  array('emucontrol', 'emu_set_temp'),
		'emu.time' =>  array('emucontrol', 'emu_skip_time'),
		'emu.reset' =>  array('emucontrol', 'emu_reset'),
	);

	if (!isset($_REQUEST['method'])) error(ERROR_NO_METHOD);
	$method = strtolower($_REQUEST['method']);

	if (!array_key_exists($method, $methods))
		error(ERROR_INVALID_METHOD, $method);
	if (!isset($methods[$method]['noauth']) || !$methods[$method]['noauth'])
		auth_check();
	
	$result = array();

	require_once($methods[$method][0] . '.php');
	$methods[$method][1]();

	echo(json_encode($result));
?>
