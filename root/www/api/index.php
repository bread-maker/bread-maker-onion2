<?php
	define("BREADMAKER_WEB_API", 1);
	header('Content-Type: application/json');
	header("access-control-allow-origin: *");
	error_reporting(0);
	require_once('auth.php');	

	$postdata = json_decode(file_get_contents("php://input"));
	foreach($postdata as $k => $v)
	{
		if ((gettype($v) == 'array') || (gettype($v) == 'object'))
			$_REQUEST[$k] = json_encode($v);
		else
			$_REQUEST[$k] = $v;
	}

	foreach($_REQUEST as $k => $v)
	{
		if (gettype($v) == 'array')
			$_REQUEST[$k] = json_encode($v);
	}

	$methods = array(
		'auth.login' => array('auth', 'auth'),
		'auth.logout' => array('auth', 'logout'),
		'auth.passwd' => array('auth', 'passwd'),
		'stats' => array('stats', 'stats', 'noauth' => true),
		'wifi.status' => array('wifi', 'wifi_status'),
		'wifi.scan' => array('wifi', 'wifi_scan'),
		'wifi.restart' => array('wifi', 'wifi_restart'),
		'config.wifi.apkey.set' => array('wifi', 'wifi_ap_key_set'),
		'config.wifi.ap.set' => array('wifi', 'wifi_ap_connect'),
		'config.timezone.get' =>  array('misc', 'timezone_get', 'noauth' => true),
		'config.timezone.set' =>  array('misc', 'timezone_set'),
		'config.baking.global.get' => array('programs', 'global_config_get'),
		'config.baking.global.set' => array('programs', 'global_config_set'),
		'config.baking.stages.get' => array('programs', 'program_get'),
//		'config.baking.stages.set' => array('programs', 'stages_set'),
		'config.baking.stages.get.all' => array('programs', 'programs_get_all'),
		'baking.bake' => array('commands', 'bake'),
		'baking.abort' => array('commands', 'abort'),
		'noerr' =>  array('commands', 'dismiss_error'),
		'firmware.flash' =>  array('firmware', 'flash'),
		'emu.temp' =>  array('emucontrol', 'emu_set_temp'),
		'emu.time' =>  array('emucontrol', 'emu_skip_time'),
		'emu.run' =>  array('emucontrol', 'emu_skip_time'),
		'emu.autorun' =>  array('emucontrol', 'emu_autorun'),
		'emu.reset' =>  array('emucontrol', 'emu_reset'),
		'emu.error' =>  array('emucontrol', 'emu_error'),
	);

	if (!isset($_REQUEST['method'])) error(ERROR_NO_METHOD);
	$method = strtolower($_REQUEST['method']);

	if (!array_key_exists($method, $methods))
		error(ERROR_INVALID_METHOD, $method);
	if (!isset($methods[$method]['noauth']) || !$methods[$method]['noauth'])
		auth_check();
	
	$result = array();

	try
	{
		require_once($methods[$method][0] . '.php');
		$methods[$method][1]();
	}
	catch (Exception $e)
	{
		error(ERROR_INTERNAL_EXCEPTION, $e->getMessage());
	}

	echo(json_encode($result));
?>
