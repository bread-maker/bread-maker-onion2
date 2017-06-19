<?php
	require_once('config.php');
	require_once('consts.php');
	require_once('misc.php');

	function get_stats($interval = 'last', $count = 300)
	{
		global $remote_errors;

		$program_file_name = STATS_DIR . '/breadmaker_program.json';
		$program_json = @file_get_contents($program_file_name);
		$program = json_decode($program_json);

		$stats_file_name = STATS_DIR . '/breadmaker_stats_' . $interval . '.json';
		$stats_json = trim(@file_get_contents($stats_file_name));
		if ($stats_json)
		{
			if ($stats_json[strlen($stats_json)-1] == ',') $stats_json[strlen($stats_json)-1] = ' ';
		} else {
			$stats_json = '';
		}
		$stats_json = '[' . $stats_json . ']';
		$stats = json_decode($stats_json);
		if (isset($_REQUEST['count']))
			$count = (int)$_REQUEST['count'];
		if ($count < count($stats))
		{
			$stats = array_slice($stats, count($stats) - $count);
		}
		foreach($stats as $k => $stat)
		{
			if ($stats[$k]->state == 'error')
				$stats[$k]->error_text = $remote_errors[$stats[$k]->error_code];
		}
		return array('last_program' => $program, 'stats' => $stats);
	}

	function stats()
	{
		global $result;
		$interval = 'last';
		if (isset($_REQUEST['interval']))
			$interval = $_REQUEST['interval'];
		$count = 1000;
		switch ($interval)
		{
			case 'sec':
			case '5sec':
			case '15sec':
			case '30sec':
			case 'min':
			case 'last':
				break;
			default:
				error(ERROR_INVALID_ARGUMENT, 'interval');
				break;
		}

		$stats = get_stats($interval, $count);
		
		$result['last_program'] = $stats['last_program'];
		$result['stats'] = $stats['stats'];
	}
?>
