<?php
	require_once('config.php');
	require_once('consts.php');

	function bmsend($command)
	{
		shell_exec("sh -c 'echo $command > " . UART_OUT . "'");
	}

	function error($error, $what = '', $uc_error_code = -1)
	{
		if (strlen($what) > 0) $error['error_text'] .= ': ' . $what; 
		if (isset($error['http_response_code']))
		{
			http_response_code($error['http_response_code']);
			unset($error['http_response_code']);
		}
		if ($uc_error_code >= 0)
		{
			$error['uc_error_code'] = $uc_error_code;
		}
		die(json_encode(array('error' => $error)));
	}

	function timezone_get()
	{
		global $result;
		if (!EMULATION)
		{
			$result['timezone'] = trim(shell_exec('uci get system.@system[0].timezone'));
		} else {
			$tz_file_name = SETTINGS_DIR . "/tz";
			$result['timezone'] =  @file_get_contents($tz_file_name);
			if ($result['timezone'] == '') $result['timezone'] = 'GMT-3';
		}
	}

	function timezone_set()
	{
		global $result;
		if (!isset($_REQUEST['timezone']))
			error(ERROR_MISSED_ARGUMENT, 'timezone');
		$timezone = $_REQUEST['timezone'];
		
		if (!EMULATION)
		{
			shell_exec("uci get system.@system[0].timezone='$timezone'");
			shell_exec('uci commit system.@system[0].timezone');
			file_put_contents('/etc/TZ', $timezone);
			bmsend('TIME 255 0 0'); // Reset time
		} else {
			$tz_file_name = SETTINGS_DIR . "/tz";
			@file_put_contents($tz_file_name, $timezone);
		}
		$result['result'] = true;
	}

	function config_misc_get()
	{
		global $result;
		$settings_file_name = SETTINGS_DIR . "/settings.json";
		$settings_json = @file_get_contents($settings_file_name);
		$settings = json_decode($settings_json);
		$result['config'] = $settings;
	}

	function config_misc_set()
	{
		global $result;
		if (!isset($_REQUEST['key']))
			error(ERROR_MISSED_ARGUMENT, 'key');
		$key = $_REQUEST['key'];
		if (!isset($_REQUEST['value']))
			error(ERROR_MISSED_ARGUMENT, 'value');
		$value = $_REQUEST['value'];

		$settings_file_name = SETTINGS_DIR . "/settings.json";
		$settings_json = @file_get_contents($settings_file_name);
		$settings = json_decode($settings_json);
		if ($value)
			$settings->$key = $value;
		else
			unset($settings->$key);
		file_put_contents($settings_file_name, json_encode($settings));
		$result['config'] = $settings;
	}
?>
