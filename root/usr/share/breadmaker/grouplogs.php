<?php
	$api_path = $argv[1];
	$count = $argv[2];
	require_once($api_path . '/config.php');
	require_once($api_path . '/consts.php');

	$stats_file_name = STATS_DIR . '/breadmaker_stats_sec.json';
	$stats_json = trim(@file_get_contents($stats_file_name));
	if ($stats_json)
	{
		if ($stats_json[strlen($stats_json)-1] == ',') $stats_json[strlen($stats_json)-1] = ' ';
	} else {
		$stats_json = '';
	}
	$stats_json = '[' . $stats_json . ']';
	$stats = json_decode($stats_json);
	if ($count < count($stats))
	{
		$stats = array_slice($stats, count($stats) - $count);
	}

	$temperature = 0;
	$target_temperature = 0;
	$motor = 'off';
	$heat = false;
	foreach ($stats as $stat)
	{
		$temperature += $stat->temp;
		if ($stat->motor == 'on') $motor = 'on';
			else if ($stat->motor == 'onoff' && $motor == 'off') $motor = 'onoff';
		$heat = $heat | $stat->heat;
		$last = $stat;
	}
	$last->temp = count($stats) > 0 ? ($temperature / count($stats)) : 0;
	$last->motor = $motor;
	$last->heat = $heat;
	echo(json_encode($last) . ",\n");
?>
