<?php
	require_once('config.php');
	require_once('consts.php');
	require_once('misc.php');

	function bake($program_id = -1, $crust_id = -1, $timer = 0)
	{
		global $result, $remote_errors;
		if ($program_id < 0 && !isset($_REQUEST['program_id']))
			error(ERROR_MISSED_ARGUMENT, 'program_id');
		else if (isset($_REQUEST['program_id']))
			$program_id = (int)$_REQUEST['program_id'];		
		if ($crust_id < 0 && !isset($_REQUEST['crust_id']))
			error(ERROR_MISSED_ARGUMENT, 'crust_id');
		else if (isset($_REQUEST['crust_id']))
			$crust_id = (int)$_REQUEST['crust_id'];
		if (isset($_REQUEST['timer']))
			$timer = (int)$_REQUEST['timer'];
		if ($program_id >= PROGRAMS_COUNT) error(ERROR_INVALID_ARGUMENT, 'program_id');
		if ($crust_id >= CRUSTS_COUNT) error(ERROR_INVALID_ARGUMENT, 'crust_id');
		if ($timer < 0) error(ERROR_INVALID_ARGUMENT, 'timer');

		require_once('stats.php');
		$stats = get_stats('last');
		if ($stats['stats'][0]->state == 'error')
			error(ERROR_REMOTE_ERROR, $stats['stats'][0]->error_code . ' - ' . $remote_errors[$stats['stats'][0]->error_code]);
		if ($stats['stats'][0]->state != 'idle')
			error(ERROR_INVALID_STATE, $stats['stats'][0]->state);

		$config_file_name = SETTINGS_DIR . "/global_config.json";
		$config_json = @file_get_contents($config_file_name);
		$config = json_decode($config_json);

		$max_temp_a = $config->max_temp_a;
		$max_temp_b = $config->max_temp_b;
		$warm_temp = $config->warm_temp;
		$max_warm_time = $config->max_warm_time;
		
		$program_file_name = SETTINGS_DIR . "/program.$program_id.$crust_id.json";
		$program_json = @file_get_contents($program_file_name);
		$program = json_decode($program_json);

		if (!isset($program->max_temp_a) || ($program->max_temp_a <= 0))
			$program->max_temp_a = $max_temp_a;
		if (!isset($program->max_temp_b) || ($program->max_temp_b <= 0))
			$program->max_temp_b = $max_temp_b;
		if (!isset($program->warm_temp) || ($program->warm_temp < 0))
			$program->warm_temp = $warm_temp;
		if (!isset($program->max_warm_time) || ($program->max_warm_time < 0))
			$program->max_warm_time = $max_warm_time;
		bmsend("NEW");
		bmsend("MAXTEMPA {$program->max_temp_a}");
		bmsend("MAXTEMPB {$program->max_temp_b}");
		foreach($program->stages as $stage)
		{
			$motor = 0;
			if ($stage->motor == "on") $motor = 2;
			if ($stage->motor == "onoff") $motor = 1;
			bmsend("STAGE {$stage->temp} {$motor} {$stage->duration}");
		}
		foreach($program->beeps as $beep)
		{
			bmsend("BEEP {$beep->stage} {$beep->time} {$beep->count}");
		}
		bmsend("WARMTEMP {$program->warm_temp}");
		bmsend("WARMTIME {$program->max_warm_time}");
		bmsend("RUN $program_id $crust_id $timer");

		$timeout = 3000000;
		do
		{
			usleep(100000);
			$timeout -= 100000;
			$stats = get_stats('last');
		} while (($stats['stats'][0]->state == 'idle') && $timeout > 0);

		if ($stats['stats'][0]->state == 'idle') error(ERROR_COMMUNICATION_TIMEOUT);
		if ($stats['stats'][0]->state == 'error')
			error(ERROR_REMOTE_ERROR, $stats['stats'][0]->error_code . ' - ' . $remote_errors[$stats['stats'][0]->error_code]);
	
		unset($program->program_name);
		$program->program_id = $program_id;
		$program->crust_id = $crust_id;
		$t = $stats['last_program']->program_id; unset($stats['last_program']->program_id); $stats['last_program']->program_id = $t;
		$t = $stats['last_program']->crust_id; unset($stats['last_program']->crust_id); $stats['last_program']->crust_id = $t;
		foreach($program->stages as $k => $state)
		{
			unset($program->stages[$k]->stage_name);
			unset($program->stages[$k]->stage_id);
		}

		// Need to verify that our baking program is not corrupted
		$program_need = json_encode($program);
		$program_actual = json_encode($stats['last_program']);
		if (strcmp($program_need, $program_actual) != 0)
		{
			usleep(1500000);
			abort(true);
			error(ERROR_PROGRAM_CORRUPTED, $program_need . ' vs ' . $program_actual);
		}

		$result['result'] = true;
	}

	function abort($ignore_result = false)
	{
		global $result, $remote_errors;
		bmsend("ABORT");
		$timeout = 3000000;
		require_once('stats.php');
		do
		{
			usleep(100000);
			$timeout -= 100000;
			$stats = get_stats('last');
		} while (($stats['stats'][0]->state != 'idle') && $timeout > 0);
		if ($stats['stats'][0]->state == 'error')
			error(ERROR_REMOTE_ERROR, $stats['stats'][0]->error_code . ' - ' . $remote_errors[$stats['stats'][0]->error_code]);
		if ($stats['stats'][0]->state != 'idle') error(ERROR_COMMUNICATION_TIMEOUT);
		if (!$ignore_result)
			$result['result'] = true;
	}

	function dismiss_error()
	{
		global $result, $remote_errors;
		bmsend("NOERR");
		$timeout = 15000000;
		require_once('stats.php');
		do
		{
			usleep(100000);
			$timeout -= 100000;
			$stats = get_stats('last');
		} while (($stats['stats'][0]->state != 'idle') && $timeout > 0);
		if ($stats['stats'][0]->state == 'error')
			error(ERROR_REMOTE_ERROR, $stats['stats'][0]->error_code . ' - ' . $remote_errors[$stats['stats'][0]->error_code]);
		if ($stats['stats'][0]->state != 'idle') error(ERROR_COMMUNICATION_TIMEOUT);
		$result['result'] = true;
	}
?>
