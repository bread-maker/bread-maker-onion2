<?php
	require_once('consts.php');
	require_once('config.php');
	require_once('programs.php');
	require_once('misc.php');
	require_once('stats.php');

	function bake()
	{
		global $result;
		if (!isset($_REQUEST['program_id']))
			error(ERROR_MISSED_ARGUMENT, 'program_id');
		else 
			$program_id = (int)$_REQUEST['program_id'];		
		if (!isset($_REQUEST['crust_id']))
			error(ERROR_MISSED_ARGUMENT, 'crust_id');
		else
			$crust_id = (int)$_REQUEST['crust_id'];
		if (isset($_REQUEST['timer']))
			$timer = (int)$_REQUEST['timer'];

		$config = global_config_get();
		$program = program_get($program_id);

		if (!$program) error(ERROR_INVALID_ARGUMENT, 'program_id');
		if ($crust_id >= CRUSTS_COUNT) error(ERROR_INVALID_ARGUMENT, 'crust_id');
		if ($timer < 0) error(ERROR_INVALID_ARGUMENT, 'timer');

		$max_temp_a = (int)$config['max_temp_a'];
		$max_temp_b = (int)$config['max_temp_b'];
		$warm_temp = (int)$config['warm_temp'];
		$max_warm_time = (int)$config['max_warm_time'];
		
		if (!isset($program->max_temp_a) || ($program->max_temp_a === null) || ($program->max_temp_a <= 0))
			$program->max_temp_a = $max_temp_a;
		if (!isset($program->max_temp_b) || ($program->max_temp_b === null) || ($program->max_temp_b <= 0))
			$program->max_temp_b = $max_temp_b;
		if (!isset($program->warm_temp) || ($program->warm_temp === null) || ($program->warm_temp < 0))
			$program->warm_temp = $warm_temp;
		if (!isset($program->max_warm_time) || ($program->max_warm_time === null) || ($program->max_warm_time < 0))
			$program->max_warm_time = $max_warm_time;
		$program->max_warm_time *= 60;

		$stats = get_stats();
		if ($stats['last_status']->state == 'error')
			error(ERROR_REMOTE_ERROR, REMOTE_ERRORS[$stats['last_status']->error_code], $stats['last_status']->error_code);
		if ($stats['last_status']->state != 'idle')
			error(ERROR_INVALID_STATE, $stats['last_status']->state);

		bmsend("NEW");
		bmsend("MAXTEMPA {$program->max_temp_a}");
		bmsend("MAXTEMPB {$program->max_temp_b}");
		$verify = array(
			'program_id' => $program_id,
			'crust_id' => $crust_id,
			'max_temp_a' => $program->max_temp_a,
			'max_temp_b' => $program->max_temp_b,
			'stages' => array()
		);
		$stage_id = 0;
		foreach($program->stages as $stage)
		{
			$motor = 0;
			switch ($stage->motor)
			{
				case "off": $motor = 0; break;
				case "onoff": $motor = 1; break;
				case "on": $motor = 2; break;
			}
			$temp = 0;
			switch ($crust_id)
			{
				case 0: $temp = $stage->temp; break;
				case 1: if (isset($stage->temp_b)) $temp = $stage->temp_b; break;
				case 2: if (isset($stage->temp_c)) $temp = $stage->temp_c; break;
			}
			$verify_stage = array(
				'temp' => $temp,
				'motor' => $stage->motor,
				'duration' => $stage->duration
			);
			bmsend("STAGE $temp $motor {$stage->duration}");
			if (isset($stage->beeps) && $stage->beeps > 0)
			{
				bmsend("BEEP $stage_id {$stage->beeps_time} {$stage->beeps}");
				$verify_stage['beeps'] = $stage->beeps;
				$verify_stage['beeps_time'] = $stage->beeps_time;
			}
			$verify['stages'][] = $verify_stage;
			$stage_id++;
		}
		$verify['warm_temp'] = $program->warm_temp;
		$verify['max_warm_time'] = $program->max_warm_time;
		bmsend("WARMTEMP {$program->warm_temp}");
		bmsend("WARMTIME {$program->max_warm_time}");
		bmsend("RUN $program_id $crust_id $timer");

		$timeout = 5000000;
		do
		{
			usleep(100000);
			$timeout -= 100000;
			$stats = get_stats();
		} while (($stats['last_status']->state == 'idle') && $timeout > 0);

		if ($stats['last_status']->state == 'idle') error(ERROR_COMMUNICATION_TIMEOUT);
		if ($stats['last_status']->state == 'error')
			error(ERROR_REMOTE_ERROR, $stats['last_status']->error_code . ' - ' . REMOTE_ERRORS[$stats['last_status']->error_code]);

		usleep(100000);
		$stats = get_stats();
		$last_program = get_current_program();

		// Need to verify that our baking program is not corrupted
		$program_need = json_encode($verify);
		$program_actual = json_encode($last_program);
		if (strcmp($program_need, $program_actual) != 0)
		{
			usleep(1500000);
			abort(true);
			error(ERROR_PROGRAM_CORRUPTED/*, $program_need . ' vs ' . $program_actual*/);
		}

		$result['result'] = true;
	}

	function abort($ignore_result = false)
	{
		global $result;
		bmsend("ABORT");
		$timeout = 5000000;
		require_once('stats.php');
		do
		{
			usleep(100000);
			$timeout -= 100000;
			$stats = get_stats();
		} while (($stats['last_status']->state != 'idle') && $timeout > 0);
		if ($stats['last_status']->state == 'error')
			error(ERROR_REMOTE_ERROR, $stats['last_status']->error_code . ' - ' . REMOTE_ERRORS[$stats['last_status']->error_code]);
		if ($stats['last_status']->state != 'idle') error(ERROR_COMMUNICATION_TIMEOUT);
		if (!$ignore_result)
			$result['result'] = true;
	}

	function duration($programs_count = -1)
	{
		global $result;
		if ($programs_count < 0 && !isset($_REQUEST['programs_count']))
			error(ERROR_MISSED_ARGUMENT, 'programs_count');
		else if (isset($_REQUEST['programs_count']))
			$programs_count = (int)$_REQUEST['programs_count'];

		for($program_id = 0; $program_id < $programs_count; $program_id++)
		{
			$program = program_get($program_id);		
			$d = 0;
			foreach($program->stages as $stage)
			{
				$d += $stage->duration;
			}
			$d = floor($d / 60);
			bmsend("DURATION $program_id $d");
		}
		$result['result'] = true;
	}

	function dismiss_error()
	{
		global $result;
		bmsend("NOERR");
		$timeout = 15000000;
		require_once('stats.php');
		do
		{
			usleep(100000);
			$timeout -= 100000;
			$stats = get_stats();
		} while (($stats['last_status']->state != 'idle') && $timeout > 0);
		if ($stats['last_status']->state == 'error')
			error(ERROR_REMOTE_ERROR, $stats['last_status']->error_code . ' - ' . REMOTE_ERRORS[$stats['last_status']->error_code]);
		if ($stats['last_status']->state != 'idle') error(ERROR_COMMUNICATION_TIMEOUT);
		$result['result'] = true;
	}
?>
