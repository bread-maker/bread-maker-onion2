<?php
	require_once('config.php');
	require_once('consts.php');
	require_once('misc.php');

	function get_program()
	{
		$program_file_name = STATS_DIR . '/breadmaker_program.json';
		if (!file_exists($program_file_name)) return NULL;
		$program_json = @file_get_contents($program_file_name);
		return json_decode($program_json);		
	}

	function get_stats($interval = 'last', $count = 100)
	{
		global $remote_errors;

		$program_r = get_program();
		if ($program_r != NULL)
		{
			$program_id = $program_r->program_id;
	                $crust_id = $program_r->crust_id;

			$program_file_name = SETTINGS_DIR . "/program.$program_id.$crust_id.json";
			$program_json = @file_get_contents($program_file_name);
			$program = json_decode($program_json);
			$program->program_id = $program_r->program_id;
			$program->crust_id = $program_r->crust_id;
			$program->max_temp_a = $program_r->max_temp_a;
			$program->max_temp_b = $program_r->max_temp_b;
	                $program->warm_temp = $program_r->warm_temp;
			$program->max_warm_time = $program_r->max_warm_time;
		} else $program = NULL;

		if ($interval != 'last')
		{
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
				if ($stats[$k]->target_temp == 0)
					$stats[$k]->target_temp = null;
			}
		} else $stats = null;

		$stats_file_name = STATS_DIR . '/breadmaker_stats_last.json';
		$stats_json = trim(@file_get_contents($stats_file_name));
		if ($stats_json)
		{
			if ($stats_json[strlen($stats_json)-1] == ',') $stats_json[strlen($stats_json)-1] = ' ';
		} else {
			$stats_json = '';
		}
		$last = json_decode($stats_json);

		if ($last->state == 'error')
			$last->error_text = $remote_errors[$last->error_code];
		if ($last->target_temp == 0)
			$last->target_temp = null;

		return array('last_program' => $program, 'stats' => $stats, 'last_status' => $last);
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
			case '5min':
			case 'last':
				break;
			default:
				error(ERROR_INVALID_ARGUMENT, 'interval');
				break;
		}

		$stats = get_stats($interval, $count);
		
		$result['last_program'] = $stats['last_program'];
		$result['stats'] = $stats['stats'];
		$result['last_status'] = $stats['last_status'];
	}
?>
