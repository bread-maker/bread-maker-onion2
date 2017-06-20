<?php

$fifo_dir = '/tmp/';
$fifo_out_path = "$fifo_dir/breadmaker_from_device";
$fifo_in_path = "$fifo_dir/breadmaker_to_device";

define('STATE_IDLE', 0);
define('STATE_TIMER', 1);
define('STATE_BAKING', 2);
define('STATE_WARMING', 3);
define('STATE_ERROR', 4);

define('MOTOR_STOPPED', 'off');
define('MOTOR_IMPULSE', 'onoff');
define('MOTOR_RUNNING', 'on');

echo("Staring emulation...\n");
//if (file_exists($fifo_out_path)) unlink($fifo_out_path);
//if (file_exists($fifo_in_path)) unlink($fifo_in_path);
posix_mkfifo($fifo_out_path, 0666);
posix_mkfifo($fifo_in_path, 0666);
$fifo_out = fopen($fifo_out_path, 'w');
$fifo_in = fopen($fifo_in_path, 'r+');
stream_set_blocking($fifo_in, false);

$motor_state = MOTOR_STOPPED;
$current_state = STATE_IDLE;
$last_error = 0;
$baking_program = array();
$baking_beeps = array();
//$baking_stage_count = 0;
$baking_current_stage = 0;
//$baking_beeps_count = 0;
$current_stage_time = 0;
$last_stuff_time = 0;
$program_number = 0;
$crust_number = 0;
$delayed_secs = 0;
$passed_secs = 0;
$cmd_start = 0;
$cmd_abort = 0;
$cmd_abort_err = 0;
$warming_temperature = 50;
$warming_max_time = 10800;
$max_temperature_before_timer = 40;
$max_temperature_before_baking = 40;
$target_temperature = 0.0;
$current_temperature = 26.0;
$heat_speed = 0;
$pullup=false;
$adc = 100;
$res = 50000;
$pwm = 0;
$heat = false;
$emu_time_skip = 0;
$emu_time_skipped = 0;

function send($str)
{
	global $fifo_out;
	fwrite($fifo_out, "$str\n");
}

function add_stage($temperature, $motor_code, $duration)
{
	global $baking_program;
	switch ($motor_code)
	{
		case 0:
		default:
			$motor = MOTOR_STOPPED;
			break;	
		case 1:
			$motor = MOTOR_IMPULSE;
			break;	
		case 2:
			$motor = MOTOR_RUNNING;
			break;	
	}
	$baking_program[] = array('temp' => $temperature, 'motor' => $motor, 'duration' => $duration);
}

function add_beep($stage, $time, $count)
{
	global $baking_beeps;
	$baking_beeps[] = array('stage' => $stage, 'time' => $time, 'count' => $count);
}

$recv_buffer = '';

function recv_routine()
{
	global $fifo_in, $recv_buffer, $current_state, $baking_program, $baking_program, $baking_beeps, $max_temperature_before_timer, $max_temperature_before_baking, $warming_temperature, $warming_max_time, $cmd_start, $cmd_abort, $cmd_abort_err, $program_number, $crust_number, $delayed_secs, $passed_secs, $current_temperature, $heat_speed, $emu_time_skip;
	$data = fread($fifo_in, 1024);
	if ($data)
	{
		$recv_buffer .= $data;
		while (($pos = strpos($recv_buffer, "\n")) >= 1)
		{
			$command = trim(substr($recv_buffer, 0, $pos));
			echo("<- $command\n");
			$recv_buffer = trim(substr($recv_buffer, $pos));
			$args = explode(' ', $command);
			$command = $args[0];
			foreach($args as $k => $v)
				$args[$k] = (int)$args[$k];
			switch ($command)
			{
				case 'TIME':
					break;
				case 'NEW':
					if ($current_state != STATE_IDLE) $cmd_abort = 1;
					//$baking_stage_count = $baking_beeps_count = 0;
					$baking_program = array();
					$baking_beeps = array();
					break;
				case 'STAGE':
					if ($current_state != STATE_IDLE) $cmd_abort = 1;
					add_stage($args[1], $args[2], $args[3]);
					break;
				case 'BEEP':
					if ($current_state != STATE_IDLE) $cmd_abort = 1;
					add_beep($args[1], $args[2], $args[3]);
					break;
				case 'MAXTEMPA':
					$max_temperature_before_timer = $args[1];
					break;
				case 'MAXTEMPB':
					$max_temperature_before_baking = $args[1];
					break;
				case 'WARMTEMP':
					$warming_temperature = $args[1];
					break;
				case 'WARMTIME':
					$warming_max_time = $args[1];
					break;
				case 'RUN':
					if ($current_state != STATE_IDLE) $cmd_abort = 1;
					$program_number = $args[1];
					$crust_number = $args[2];
					$delayed_secs = $args[3];
					$cmd_start = 1;
					break;
				case 'ABORT':
					$cmd_abort = 1;
					break;
				case 'NORTT':
					$cmd_abort_err = 1;
					break;
				case 'EMUTEMP':
					$current_temperature = $args[1];
					$heat_speed = 0;
					break;
				case 'EMUTIME':
					$emu_time_skip = $args[1];
					break;
			}
		}
	}
}

// Sends current baking program
function send_program()
{
	global $program_number, $crust_number, $max_temperature_before_timer, $max_temperature_before_baking, $baking_program, $baking_beeps, $warming_temperature, $warming_max_time;
	$progr = array();
	$progr['program_id'] = $program_number;
	$progr['crust_id'] = $crust_number;
	$progr['max_temp_a'] = $max_temperature_before_timer;
	$progr['max_temp_b'] = $max_temperature_before_baking;
	$progr['stages'] = $baking_program;
	$progr['beeps'] = $baking_beeps;
	$progr['warm_temp'] = $warming_temperature;
	$progr['max_warm_time'] = $warming_max_time;
	send('PROGR ' . json_encode($progr));
}

// Sends current stats
function send_stats()
{
	global $current_state, $passed_secs, $delayed_secs, $program_number, $crust_number, $baking_current_stage, $current_stage_time, $motor_state, $last_error, $target_temperature, $current_temperature, $pullup, $adc, $res, $pwm, $heat;
	//printf("TIMER %02d:%02d:%02d\n", hour, min, sec);

	$stats = array();
	switch ($current_state)
	{
	case STATE_IDLE:
		$stats['state'] = 'idle';
		break;
	case STATE_TIMER:
		$stats['state'] = 'timer';
		$stats['passed'] = $passed_secs;
		$stats['left'] = $delayed_secs;
		break;
	case STATE_BAKING:
		$stats['state'] = 'baking';
		$stats['program_id'] = $program_number;
		$stats['crust_id'] = $crust_number;
		$stats['stage'] = $baking_current_stage;
		$stats['stage_time'] = $current_stage_time;
		$stats['passed'] = $passed_secs;
		$stats['left'] = $delayed_secs;
		break;
	case STATE_WARMING:
		$stats['state'] = 'warming';
		$stats['passed'] = $passed_secs;
		$stats['left'] = $delayed_secs;
		break;
	case STATE_ERROR:
		$stats['state'] = 'error';
		$stats['error_code'] = $last_error;
		break;
	}

	$stats['target_temp'] = $target_temperature;
	$stats['temp'] = $current_temperature;
	$stats['motor'] = $motor_state;
	$stats['pullup'] = $pullup;
	$stats['adc'] = $adc;
	$stats['res'] = $res;
	$stats['pwm'] = $pwm;
	$stats['heat'] = $heat;
	$stats_json = json_encode($stats);
	$stats_json = substr($stats_json, 1);
	$stats_json = substr($stats_json, 0, strlen($stats_json)-1);
	send("STATS $stats_json");
}

function manage_heater()
{
	global $target_temperature, $current_temperature, $heat_speed, $pwm, $heat, $pullup, $adc, $res;
	if ($target_temperature > $current_temperature)
	{
		if ($target_temperature - $current_temperature > 5) $pwm = 192;
			else $pwm = (int)(($target_temperature - $current_temperature) * 32);
		if ($heat_speed < 0.5) $heat_speed += 0.005 * $pwm / 192;
		$heat = (time() % 32) < (int)($pwm/8);
	} else {
		$heat_speed -= 0.005;
		if ($heat_speed < 0) $heat_speed = 0;
		$pwm = 0;
		$heat = false;
	}
	//echo("heat_speed: $heat_speed\n");
	$current_temperature += $heat_speed;
	if ($current_temperature > 25)
		$current_temperature -= ($current_temperature-25)/1000;
	if ($current_temperature >= 90) $pullup = true;
	if ($current_temperature <= 80) $pullup = false;
	$res = (int)(100000 - ($current_temperature - 25) * 800);
	if (!$pullup)
		$adc = (int)(0x3FF * $res / 105000);
	else
		$adc = (int)(0x3FF * $res / 60000);
}

function do_stuff()
{
	global $last_stuff_time, $current_state, $delayed_secs, $passed_secs, $emu_time_skip, $emu_time_skipped;
	recv_routine();
	if ($emu_time_skip > 0)
	{
		$emu_time_skipped++;
		$emu_time_skip--;
		send("SKIPT $emu_time_skipped");
	}
	$seconds = time() + $emu_time_skipped;
	if ($seconds - $last_stuff_time <= 0) return;

	manage_heater();
	send_stats();
	if ($current_state == STATE_TIMER ||
		$current_state == STATE_BAKING ||
		$current_state == STATE_WARMING)
	{
		if ($delayed_secs) $delayed_secs--;
		$passed_secs++;
	}
	
	$last_stuff_time = $seconds;
}

function show_error($errno)
{
	global $current_state, $last_error, $cmd_abort_err, $target_temperature, $motor_state, $cmd_abort;
	$current_state = STATE_ERROR;
	$last_error = $errno;
	$target_temperature = 0;
	$motor_state = MOTOR_STOPPED;
	while (!$cmd_abort_err)
	{
		sleep(1);
		do_stuff();
	}
	$cmd_abort = 1;
}

function baking()
{
	global $current_temperature, $max_temperature_before_timer, $max_temperature_before_baking, $current_state, $delayed_secs, $passed_secs, $cmd_abort, $baking_program, $baking_beeps, $current_stage_time, $target_temperature, $warming_temperature, $motor_state, $baking_current_stage;

	$total_time = 0;
	$baking_current_stage = 0;
	if ($current_temperature > $max_temperature_before_timer) show_error(7);
	send_program();
	if ($delayed_secs)
	{
		$current_state = STATE_TIMER;
		$passed_secs = 0;
		send_stats();
		while ($delayed_secs)
		{
			if ($cmd_abort) return;
			do_stuff();
			usleep(1000);
		}
	}
	for ($i = 0; $i < count($baking_program); $i++)
		$total_time += $baking_program[$i]['duration'];
	$delayed_secs = $total_time;
	$passed_secs = 0;
	$current_state = STATE_BAKING;
	send_stats();
	if ($current_temperature > $max_temperature_before_baking) show_error(7);
	while ($delayed_secs)
	{
		if ($cmd_abort) return;
		$time_passed = $total_time - $delayed_secs;
		for ($i = 0; ($i < count($baking_program)) && ($time_passed >= $baking_program[$i]['duration']); $i++)
		{
			$time_passed -= $baking_program[$i]['duration'];
		}
		if ($i < count($baking_program))
		{
			$baking_current_stage = $i;
			$current_stage_time = $time_passed;
			$target_temperature = $baking_program[$i]['temp'];
			$motor_state = $baking_program[$i]['motor'];
		}
		do_stuff();
		usleep(1000);
	}
	$target_temperature = $warming_temperature;
	$motor_state = MOTOR_STOPPED;
	$delayed_secs = $warming_max_time;
	$passed_secs = 0;
	$current_state = STATE_WARMING;
	send("BAKED");
	while ($delayed_secs)
	{
		if ($cmd_abort) return;
		do_stuff();
		usleep(1000);
	}	
}

echo("Emulation started\n");

while (1)
{
	if ($cmd_start)
	{
		$cmd_abort = 0;
		$cmd_start = 0;
		//eeprom_write_byte(EEPROM_ADDR_WAS_IDLE, 0);
		baking();
		//eeprom_write_byte(EEPROM_ADDR_WAS_IDLE, 0xff);
	}
	$target_temperature = 0;
	$motor_state = MOTOR_STOPPED;
	if ($cmd_abort)
	{
		$cmd_abort = 0;
		/*
		wdt_reset();
		beeper_set_freq(1000);
		_delay_ms(200);
		beeper_set_freq(200);
		_delay_ms(500);
		beeper_set_freq(0);
		*/
	}
	$current_state = STATE_IDLE;
//	$display_mode = DISPLAY_TIME;
//	$display[4] = 0;
	do_stuff();
	usleep(1000);
}

?>
