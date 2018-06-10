<?php
	function header_status()
	{
		echo('<body class="lang_en" onload="graph_init()" onresize="graph_resize()">');
	}

	function status()
	{
		global $lng;
?>
<h2><?=$lng['baking_status']?></h2>
<ul class="tabmenu l1">		
	<li id="interval_sec">
		<a href="#" onclick="set_interval(1, 5, 'sec', 1);"><?=$lng['interval_1_sec']?></a>
	</li>		
	<li class="active" id="interval_5sec">
		<a href="#" onclick="set_interval(5, 5, '5sec', 1);"><?=$lng['interval_5_sec']?></a>
	</li>		
	<li id="interval_15sec">
		<a href="#" onclick="set_interval(15, 5, '15sec', 10);"><?=$lng['interval_15_sec']?></a>
	</li>		
	<li id="interval_30sec">
		<a href="#" onclick="set_interval(30, 5, '30sec', 10);"><?=$lng['interval_30_sec']?></a>
	</li>		
	<li id="interval_min">
		<a href="#" onclick="set_interval(60, 5, 'min', 10);"><?=$lng['interval_1_min']?></a>
	</li>		
	<li id="interval_5min">
		<a href="#" onclick="set_interval(300, 5, '5min', 60);"><?=$lng['interval_5_min']?></a>
	</li>		
</ul>

<embed id="bwsvg" style="width:100%; height: 360px; border:1px solid #000000; background-color:#FFFFFF" src="graph.svg"/>
<div style="text-align:right"><small id="scale">-</small></div>
<br />

<table style="width:100%; table-layout:fixed; border-collapse: separate; border-spacing: 5px;">
	<tr>
		<td style="text-align:right; vertical-align:center"><strong style="border-bottom:2px solid #C00000"><?=$lng['current_temperature']?>:</strong></td>
		<td id="lb_temperature">-</td>

		<td style="text-align:right; vertical-align:center"><strong style="border-bottom:1px solid #000000"><?=$lng['target_temperature']?>:</strong></td>
		<td id="lb_target_temperature">-</td>

		<td style="text-align:right; vertical-align:center"><strong style="border-bottom:2px solid #FF0000"><?=$lng['heat']?>:</strong></td>
		<td id="lb_heat">-</td>
	</tr>
	<tr>
		<td style="text-align:right; vertical-align:center"><strong><?=$lng['state']?>:</strong></td>
		<td id="lb_state">-</td>

		<td style="text-align:right; vertical-align:center"><strong style="border-bottom:2px solid #0000FF"><?=$lng['motor']?>:</strong></td>
		<td id="lb_motor">-</td>

		<td style="text-align:right; vertical-align:center"><strong><?=$lng['pwm']?>:</strong></td>
		<td id="lb_pwm">-</td>
	</tr>

	<tr>
		<td style="text-align:right; vertical-align:center"><strong><?=$lng['start_time']?>:</strong></td>
		<td id="lb_time_start">-</td>

		<td style="text-align:right; vertical-align:center"><strong><?=$lng['time_left']?>:</strong></td>
		<td id="lb_time_left">-</td>

		<td style="text-align:right; vertical-align:center"><strong><?=$lng['end_time']?>:</strong></td>
		<td id="lb_time_end">-</td>
	</tr>
</table>
<?php
	}
?>
