<?php
	require_once('config.php');
	require_once('consts.php');
	require_once('misc.php');

	function program_get($program_id = -1)
	{
		global $result;
		if ($program_id < 0 && !isset($_REQUEST['program_id']))
			error(ERROR_MISSED_ARGUMENT, 'program_id');
		else if (isset($_REQUEST['program_id']))
			$program_id = (int)$_REQUEST['program_id'];
		$programs_file_name = SETTINGS_DIR . "/programs.json";
		$programs_json = @file_get_contents($programs_file_name);
		$programs = json_decode($programs_json);
		if (!isset($programs[$program_id]))
			error(ERROR_INVALID_ARGUMENT, 'program_id');
		$result['program'] = $programs[$program_id];
		return $programs[$program_id];
	}

	function programs_get_all()
	{
		global $result;
		$programs_file_name = SETTINGS_DIR . "/programs.json";
		$programs_json = @file_get_contents($programs_file_name);
		$programs = json_decode($programs_json);
		$result['programs'] = $programs;
		return $programs;
	}

	function programs_set_all($programs)
	{
		$programs_file_name = SETTINGS_DIR . "/programs.json";
		$programs_json = json_encode($programs);
		file_put_contents($programs_file_name, $programs_json);
	}

	function global_config_get()
	{
		global $result;
		$config_file_name = SETTINGS_DIR . "/global_config.json";
		$config_json = @file_get_contents($config_file_name);
		$config = json_decode($config_json);
		$keys = array('max_temp_a', 'max_temp_b', 'warm_temp', 'max_warm_time');
		$result_c = array();
		foreach($keys as $key)
		{
			$result_c[$key] = $config->$key;
		}
		$result['config'] = $result_c;
		return $result_c;
	}

	function global_config_set()
	{
		global $result;
		if (isset($_REQUEST['config']))
			$new_config_json = $_REQUEST['config'];
		else
			$new_config_json = '{}';
		$new_config = json_decode($new_config_json, true);
		$config_file_name = SETTINGS_DIR . "/global_config.json";
		$old_config_json = @file_get_contents($config_file_name);
		$old_config = json_decode($old_config_json, true);
		$keys = array('max_temp_a', 'max_temp_b', 'warm_temp', 'max_warm_time');
		foreach($keys as $key)
		{
			if (isset($new_config[$key]))
			{
				$old_config[$key] = $new_config[$key];
			}
			if (isset($_REQUEST[$key]))
			{
				$old_config[$key] = (int)$_REQUEST[$key];
			}
		}
		file_put_contents($config_file_name, json_encode($old_config));
		global_config_get();
	}
?>
