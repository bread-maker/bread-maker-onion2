<?php
	function preexec_control()
	{
		global $alert, $lng;
		if (isset($_REQUEST['new_programs']))
		{
			require_once('api/programs.php');
			programs_set_all(json_decode($_REQUEST['new_programs']));
			header('Location: ' . $_SERVER['REQUEST_URI']);
			die();
		}
		else if (isset($_REQUEST['start_program']))
		{
			require_once('api/commands.php');
			$program_id = (int)$_REQUEST['start_program'];
			$crust_id = (int)$_REQUEST['crust'];
			$timer = (int)$_REQUEST['timer'];
			try {
				bake($program_id, $crust_id, $timer);
				header('Location: ' . $_SERVER['REQUEST_URI']);
				die();
			}
			catch (Exception $e) {
				$alert = "{$lng['error']}: {$e->getMessage()}";
			}
		}
		else if (isset($_REQUEST['abort']))
		{
			require_once('api/commands.php');
			try {
				abort();
				header('Location: ' . $_SERVER['REQUEST_URI']);
				die();
			}
			catch (Exception $e) {
				$alert = "{$lng['error']}: {$e->getMessage()}";
			}
		}
	}

	function script_control()
	{
		require_once('bm_consts.php');
		require_once('api/programs.php');
?>
		<script>
		var orig_programs = <?= json_encode(programs_get_all()) ?>;
		var programs = JSON.parse(JSON.stringify(orig_programs));
		var global_config = <?= json_encode(global_config_get()) ?>;
		var max_temp = <?= MAX_TEMP ?>;
		var baking_temp = <?= BAKING_TEMP ?>;
		var max_duration_mins = <?= MAX_DURATION_MINS ?>;
		</script>
		<script src="programs.js"></script>
<?php
	}

	function header_control()
	{
		echo('<body class="lang_en" onload="status_init(); programs_init();">');
	}

	function control()
	{
		global $lng;
?>
	<h2><?=$lng['programs_and_control']?></h2>
	<br/>
	<div id="loading" align="center">
	<?=$lng['loading']?>
	</div>
	<div id="status" align="center" style="display: none">
	<table class="border" style="width: 100%; max-width: 700px;">
		<tr>
			<td style="text-align:right; vertical-align:center; width: 30%;"><strong><?=$lng['program_name']?>:</strong></td>
			<td id="lb_program_name">-</td>
		</tr>
		<tr>
			<td style="text-align:right; vertical-align:center; width: 30%;"><strong><?=$lng['state']?>:</strong></td>
			<td id="lb_state">-</td>
		</tr>
		<tr>
			<td style="text-align:right; vertical-align:center; width: 30%;"><strong><?=$lng['current_temperature']?>:</strong></td>
			<td id="lb_temperature">-</td>
		</tr>
		<tr>
			<td style="text-align:right; vertical-align:center; width: 30%;"><strong><?=$lng['start_time']?>:</strong></td>
			<td id="lb_time_start">-</td>
		</tr>
		<tr>
			<td style="text-align:right; vertical-align:center; width: 30%;"><strong><?=$lng['time_left']?>:</strong></td>
			<td id="lb_time_left">-</td>
		</tr>
		<tr>
			<td style="text-align:right; vertical-align:center; width: 30%;"><strong><?=$lng['end_time']?>:</strong></td>
			<td id="lb_time_end">-</td>
		</tr>
		</table>
		<div id="error" style="display: none"><br/><strong style="color: red"><?=$lng['critical_error']?></strong></div>
		<div id="abort" align="right" style="width: 100%; max-width: 650px;"><br/>
			<input id="abort_button" style="width: 150px; color: red;" type="button" onclick="abort_baking()">
		</div>
	</div>
	<div id="programs" align="center">
	</div>
	<div id="program_editor" align="center">
	</div>
	<div id="program_starter" align="center">
	</div>
	<br/><br/>
<?php
	}
?>
