<?php
	require_once('config.php');
	require_once('consts.php');
	require_once('misc.php');

	function stage_get($program_id, $crust_id)
	{
		if ($program_id >= PROGRAMS_COUNT) error(ERROR_INVALID_ARGUMENT, 'program');
		if ($crust_id >= CRUSTS_COUNT) error(ERROR_INVALID_ARGUMENT, 'crust');
		
		$program_file_name = SETTINGS_DIR . "/program.$program_id.$crust_id.json";
		$program_json = @file_get_contents($program_file_name);
		$program = json_decode($program_json);

		$result = array();
		$result['program_id'] = $program_id;
		$result['crust_id'] = $crust_id;
		$result['program_name'] = '';
		$result['max_temp_a'] = -1;
		$result['max_temp_b'] = -1;
		$result['stages'] = array();
		$result['beeps'] = array();
		$result['warm_temp'] = -1;
		$result['max_warm_time'] = -1;

		if (isset($program->program_name))
			$result['program_name'] = $program->program_name;
		if (isset($program->max_temp_a))
			$result['max_temp_a'] = $program->max_temp_a;
		if (isset($program->max_temp_b))
			$result['max_temp_b'] = $program->max_temp_b;
		if (isset($program->stages))
		{
			$id = 0;
			foreach($program->stages as $stage)
			{
				//$result['stages'][$id]['stage_id'] = $id;
				$result['stages'][$id]['stage_name'] = $stage->stage_name;
				$result['stages'][$id]['temp'] = $stage->temp;
				$result['stages'][$id]['motor'] = $stage->motor;
				$result['stages'][$id]['duration'] = $stage->duration;
				$id++;
			}
		}
		if (isset($program->beeps))
		{
			foreach($program->beeps as $beep)
				$result['beeps'][] = array(
					'stage' => $beep->stage,
					'time' => $beep->time,
					'count' => $beep->count
				);
		}
		if (isset($program->warm_temp))
			$result['warm_temp'] = $program->warm_temp;
		if (isset($program->max_warm_time))
			$result['max_warm_time'] = $program->max_warm_time;

		return $result;
	}

	function stages_get()
	{
		global $result;
		if (!isset($_REQUEST['program_id']))
			error(ERROR_MISSED_ARGUMENT, 'program_id');
		if (!isset($_REQUEST['crust_id']))
			error(ERROR_MISSED_ARGUMENT, 'crust_id');
		$program_id = (int)$_REQUEST['program_id'];
		$crust_id = (int)$_REQUEST['crust_id'];
		if ($program_id >= PROGRAMS_COUNT) error(ERROR_INVALID_ARGUMENT, 'program');
		if ($crust_id >= CRUSTS_COUNT) error(ERROR_INVALID_ARGUMENT, 'crust');
		$result['program'] = stage_get($program_id, $crust_id);
	}

	function stages_get_all()
	{
		global $result;
		$programs = array();
		for($program_id = 0; $program_id < PROGRAMS_COUNT; $program_id++)
			for($crust_id = 0; $crust_id < CRUSTS_COUNT; $crust_id++)
				$programs[] = stage_get($program_id, $crust_id);
		$result['programs'] = $programs;
	}

	function stages_set()
	{
		global $result;
		if (!isset($_REQUEST['program_id']))
			error(ERROR_MISSED_ARGUMENT, 'program_id');
		if (!isset($_REQUEST['crust_id']))
			error(ERROR_MISSED_ARGUMENT, 'crust_id');
		if (!isset($_REQUEST['program']))
			error(ERROR_MISSED_ARGUMENT, 'program');
		$program_id = (int)$_REQUEST['program_id'];
		$crust_id = (int)$_REQUEST['crust_id'];
		if ($program_id >= PROGRAMS_COUNT) error(ERROR_INVALID_ARGUMENT, 'program');
		if ($crust_id >= CRUSTS_COUNT) error(ERROR_INVALID_ARGUMENT, 'crust');
		$program = json_decode($_REQUEST['program']);
		$program_out = array();
		$program_out['program_name'] = '';
		$program_out['max_temp_a'] = -1;
		$program_out['max_temp_b'] = -1;
		$program_out['stages'] = array();
		$program_out['beeps'] = array();
		$program_out['warm_temp'] = -1;
		$program_out['max_warm_time'] = -1;

		if (isset($program->program_name))
			$program_out['program_name'] = $program->program_name;
		if (isset($program->max_temp_a))
			$program_out['max_temp_a'] = $program->max_temp_a;
		if (isset($program->max_temp_b))
			$program_out['max_temp_b'] = $program->max_temp_b;
		if (isset($program->stages))
		{
			$id = 0;
			foreach($program->stages as $stage)
			{
				$program_out['stages'][$id]['stage_id'] = $id;
				$program_out['stages'][$id]['stage_name'] = $stage->stage_name;
				$program_out['stages'][$id]['temp'] = $stage->temp;
				$program_out['stages'][$id]['motor'] = $stage->motor;
				$program_out['stages'][$id]['duration'] = $stage->duration;
				$id++;
			}
		}
		if (isset($program->beeps))
		{
			foreach($program->beeps as $beep)
				$program_out['beeps'][] = array(
					'stage' => $beep->stage,
					'time' => $beep->time,
					'count' => $beep->count
				);
		}
		if (isset($program->warm_temp))
			$program_out['warm_temp'] = $program->warm_temp;
		if (isset($program->max_warm_time))
			$program_out['max_warm_time'] = $program->max_warm_time;

		$program_file_name = SETTINGS_DIR . "/program.$program_id.$crust_id.json";
		file_put_contents($program_file_name, json_encode($program_out));

		$result['result'] = true;
		$result['program_id'] = $program_id;
		$result['crust_id'] = $crust_id;
		$result['program'] = $program_out;
	}

	function global_config_get()
	{
		global $result;
		$config_file_name = SETTINGS_DIR . "/global_config.json";
		$config_json = @file_get_contents($config_file_name);
		$config = json_decode($config_json);
		$keys = array('max_temp_a', 'max_temp_b', 'warm_temp', 'max_warm_time');
		$result['config'] = array();
		foreach($keys as $key)
		{
//			if (isset($config->$key))
			{
				$result['config'][$key] = $config->$key;
			}
		}
	}

	function global_config_set()
	{
		global $result;
		if (isset($_REQUEST['config']))
			$new_config_json = $_REQUEST['config'];
		else
			$new_config_json = '{}';
		$new_config = json_decode($new_config_json);
		$config_file_name = SETTINGS_DIR . "/global_config.json";
		$old_config_json = @file_get_contents($config_file_name);
		$old_config = json_decode($old_config_json);
		$keys = array('max_temp_a', 'max_temp_b', 'warm_temp', 'max_warm_time');
		foreach($keys as $key)
		{
			if (isset($new_config->$key))
			{
				$old_config->$key = $new_config->$key;
			}
			if (isset($_REQUEST[$key]))
			{
				$old_config->$key = $_REQUEST[$key];
			}
		}
		file_put_contents($config_file_name, json_encode($old_config));
		global_config_get();
	}
?>
