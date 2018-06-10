<?php
	require_once('config.php');
	require_once('consts.php');

	function bmsend($command)
	{
		shell_exec("sh -c 'printf \"$command\n\" >> " . UART_OUT . "'");
		usleep(50000);
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
		if (!defined("BREADMAKER_WEB_API"))
			throw new Exception($error['error_text']);
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
		return $result['timezone'];
	}

	function timezone_set()
	{
		global $result;
		if (!isset($_REQUEST['timezone']))
			error(ERROR_MISSED_ARGUMENT, 'timezone');
		$timezone = $_REQUEST['timezone'];
		
		if (!EMULATION)
		{
			shell_exec("uci set system.@system[0].timezone=".escapeshellarg(trim($timezone)));
			shell_exec('uci commit system.@system[0].timezone');
			shell_exec('/etc/init.d/system reload');
			bmsend('TIME 255 0 0'); // Reset time
		} else {
			$tz_file_name = SETTINGS_DIR . "/tz";
			@file_put_contents($tz_file_name, $timezone);
		}
		$result['result'] = true;
	}
?>
