<?php
	require_once('config.php');
	require_once('consts.php');
	require_once('misc.php');

	function emu_set_temp()
	{
		global $result;
		if (!isset($_REQUEST['temp']))
			error(ERROR_MISSED_ARGUMENT, 'temp');
		$temp = (int)$_REQUEST['temp'];
		bmsend("EMUTEMP $temp");
		$result['result'] = true;
	}

	function emu_skip_time()
	{
		global $result;
		if (!isset($_REQUEST['time']))
			error(ERROR_MISSED_ARGUMENT, 'time');
		$time = (int)$_REQUEST['time'];
		bmsend("EMUTIME $time");
		$result['result'] = true;
	}
?>