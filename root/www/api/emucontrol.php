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
		$timeout = $time;
		sleep(2);
		while (trim(@file_get_contents(STATS_DIR . '/breadmaker_skipl')) != '0')
		{
			sleep(1);
			$timeout--;
			if ($timeout <= 0) error(ERROR_TIMEOUT);
		}
		$result['result'] = true;
	}

	function emu_reset()
	{
		global $result;
		$time = time();
		if (isset($_REQUEST['time']))
			$time = (int)$_REQUEST['time'];
		bmsend("EMURESET $time");
		sleep(2);
		$result['result'] = true;
	}
?>