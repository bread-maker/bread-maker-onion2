	var svg;
	var G;
	var update_timer;

	var step   = 5;
	var interval = 5;
	var req_interval = "5sec";
	var grid_step = 1;

	var data_wanted = 500;
	var data_stamp  = 0;
	
	var temperature_scale = 3;
	var temperature_min = 20;

	var width = 0;
	var height = 0;

	var temperature = [ ];
	var line_temperature;
	var target_temperature = [ ];
	var line_target_temperature;
	var heat = [ ];
	var line_heat;
	var motor = [ ];
	var line_motor;
	var label_loading;

	var label_header_program;
	var label_header_state;
	var label_scale;
	var label_temperature;
	var label_target_temperature;
	var label_pwm;
	var label_heat;
	var label_motor;
	var label_stage;
	var label_time_start;
	var label_time_left;
	var label_time_end;
	var clean_svg;
	var current_temperature = 0;
	var current_target_temperature = 0;
	var current_pwm = 0;
	var current_heat = false;
	var current_motor = "off";
	var current_stage = "idle";
	var current_program;
	var current_state = '';
	var current_stage = -1;
	var current_time_passed;
	var current_time_left;

	function init()
	{
		svg = document.getElementById('bwsvg');

		try {
			G = svg.getSVGDocument
				? svg.getSVGDocument() : svg.contentDocument;
		}
		catch(e) {
			G = document.embeds['bwsvg'].getSVGDocument();
		}

		clean_svg = G.getElementById('temperature').parentNode.innerHTML;

		label_header_program = document.getElementById('lb_header_program');
		label_header_state = document.getElementById('lb_header_state');
		label_scale = document.getElementById('scale');
		label_temperature = document.getElementById('lb_temperature');
		label_target_temperature = document.getElementById('lb_target_temperature');
		label_pwm = document.getElementById('lb_pwm');
		label_heat = document.getElementById('lb_heat');
		label_motor = document.getElementById('lb_motor');
		label_state = document.getElementById('lb_state');
		label_time_start = document.getElementById('lb_time_start');
		label_time_left = document.getElementById('lb_time_left');
		label_time_end = document.getElementById('lb_time_end');

		clean_data();

		if (!G)
		{
			window.setTimeout(init, 1000);
		} else {
			request();
			reinit();
		}

		load_wifi_stats();
	}

	function load_wifi_stats()
	{
		var r = new XMLHttpRequest();
		var count = data_stamp ? Math.round(Date.now() / 1000 - data_stamp + 5) : data_wanted;
		var last_i = req_interval;
		var url = '/api/?method=wifi.status';
		r.open('GET', url, true);

		var label_header_ip = document.getElementById('lb_header_ip');

		r.onreadystatechange = function()
		{
			if (r.readyState == 4) {
				if (r.status == 200)
				{
					try
					{
						var data = eval('(' + r.responseText + ')');
						if (data.wifi_status.up)
						{
							label_header_ip.innerHTML = data.wifi_status["ipv4-address"][0].address;
							if (data.wifi_status["ipv6-address"].length > 0) label_header_ip.innerHTML += " / "+ data.wifi_status["ipv6-address"][0].address;
							label_header_ip.parentNode.style.display = '';
						} else 	label_header_ip.parentNode.style.display = 'none';
					}
					catch(e) {							
					}
				}
			}
		}
		window.setTimeout(load_wifi_stats, 60000);
		r.send(null);
	}

	function clean_data()
	{
		data_stamp = 0;
		/* prefill datasets */
		for (var i = 0; i < data_wanted; i++)
		{
			temperature[i] = 0;
			target_temperature[i] = 0;
			heat[i] = false;
			motor[i] = "off";
		}
	}

	function resize()
	{
		if (width != svg.offsetWidth  - 2) reinit();
	}
	
	function reinit() {
		/* find sizes */
		width       = svg.offsetWidth  - 2;
		height      = svg.offsetHeight - 2;

		/* Reload SVG */
		G.getElementById('temperature').parentNode.innerHTML = clean_svg;

		/* find svg elements */
		line_temperature = G.getElementById('temperature');
		line_target_temperature = G.getElementById('target_temperature');
		line_heat = G.getElementById('heat');
		line_motor = G.getElementById('motor');
		label_loading = G.getElementById('loading');
		
		/* plot horizontal time interval lines */
		var t = grid_step;
		for (var i = width - grid_step * step * 60 / interval; i >= 0; i -= grid_step * step * 60 / interval)
		{
			var line = G.createElementNS('http://www.w3.org/2000/svg', 'line');
				line.setAttribute('x1', i);
				line.setAttribute('y1', 0);
				line.setAttribute('x2', i);
				line.setAttribute('y2', '100%');
				line.setAttribute('style', 'stroke:black;stroke-width:0.1');

			var text = G.createElementNS('http://www.w3.org/2000/svg', 'text');
				text.setAttribute('x', i + 5);
				text.setAttribute('y', 15);
				text.setAttribute('style', 'fill:#999999; font-size:9pt');
				text.appendChild(G.createTextNode(grid_step < 60 ? (t + 'm') : ((t/60) + "h")));
			line_temperature.parentNode.appendChild(line);
			line_temperature.parentNode.appendChild(text);
			t += grid_step;
		}

		label_scale.innerHTML = Math.round(width / step * interval / 60) + " minute(s) window";

		//draw_data();
	}
	
	function request()
	{
		var r = new XMLHttpRequest();
		var count = data_stamp ? Math.round(Date.now() / 1000 - data_stamp + 5) : data_wanted;
		var last_i = req_interval;
		var url = '/api/?method=stats&interval='+req_interval+'&count='+count;
		r.open('GET', url, true);

		r.onreadystatechange = function()
		{
			if (r.readyState == 4) {
				if (r.status == 200)
				{
					try
					{
						if (last_i != req_interval) return;
						var data = eval('(' + r.responseText + ')');

						for (var i = 0; i < data.stats.length; i++)
						{
							/* skip overlapping entries */
							if (data.stats[i].time <= data_stamp)
								continue;
							if (!data.stats[i].state != 'error')
							{
								temperature.push(data.stats[i].temp);
								target_temperature.push(data.stats[i].target_temp);
								heat.push(data.stats[i].heat);
								motor.push(data.stats[i].motor);
							} else {
								temperature.push(0);
								target_temperature.push(0);
								heat.push(false);
								heat.push("off");
							}
						}			
						if (!data.last_status.state != 'error')
						{
							current_temperature = data.last_status.temp;
							current_target_temperature = data.last_status.target_temp;
							current_pwm = data.last_status.pwm;
							current_heat = data.last_status.heat;
							current_motor = data.last_status.motor;
							current_program = data.last_program;
							current_state = data.last_status.state;
							current_stage = data.last_status.stage;
							current_time_passed = data.last_status.passed ? data.last_status.passed : 0;
							current_time_left = data.last_status.left ? data.last_status.left : 0;
						} else {
							current_temperature = 0;
							current_target_temperature = 0;
							current_pwm = 0;
							current_heat = false;
							current_motor = "off";
							current_program = [];
							current_state = "error";
							current_stage = -1;
							current_time_passed = 0;
							current_time_left = 0;
						}
        
						/* cut off outdated entries */
						temperature = temperature.slice(temperature.length - data_wanted, temperature.length);
						target_temperature = target_temperature.slice(target_temperature.length - data_wanted, target_temperature.length);
						heat = heat.slice(heat.length - data_wanted, heat.length);
						motor = motor.slice(motor.length - data_wanted, motor.length);

						/* remember current timestamp */
						data_stamp = data.stats[data.stats.length-1].time;

						draw_data();					
					}
					catch(e) {							
					}
				}
				update_timer = window.setTimeout(request, 5000);
			}
		}
		r.send(null);
	}	
	
	function draw_data()
	{
		/* plot data */
		var pt_temperature = '0,' + height;
		var y_temperature = 0;
		var pt_target_temperature = '0,' + height;
		var y_target_temperature = 0;
		var pt_heat = '0,' + height;
		var y_heat = 0;
		var pt_motor = '0,' + height;
		var y_motor = 0;

		for (var i = 0; i < temperature.length; i++)
		{
			var x = i * step + width - temperature.length * step;
			y_temperature = height - Math.floor((temperature[i] - temperature_min) * temperature_scale);
			pt_temperature += ' ' + x + ',' + y_temperature;
			
			y_target_temperature = height - Math.floor((target_temperature[i] - temperature_min) * temperature_scale);
			if (y_target_temperature > height) y_target_temperature = height + 1;
			pt_target_temperature += ' ' + x + ',' + y_target_temperature;
			
			y_heat = height - (heat[i] ? 5 : -1);
			pt_heat += ' ' + x + ',' + y_heat;
			
			y_motor = height+1;
			if (motor[i] == "onoff") y_motor = height-1;
			if (motor[i] == "on") y_motor = height-2;
			pt_motor += ' ' + x + ',' + y_motor;
		}

		pt_temperature += ' ' + width + ',' + y_temperature + ' ' + width + ',' + height;
		pt_target_temperature += ' ' + width + ',' + y_target_temperature + ' ' + width + ',' + height;
		pt_heat += ' ' + width + ',' + y_heat + ' ' + width + ',' + height;
		pt_motor += ' ' + width + ',' + y_motor + ' ' + width + ',' + height;

		line_temperature.setAttribute('points', pt_temperature);
		line_target_temperature.setAttribute('points', pt_target_temperature);
		line_heat.setAttribute('points', pt_heat);
		line_motor.setAttribute('points', pt_motor);

		label_temperature.innerHTML = Math.round(current_temperature) + "°C";
		label_target_temperature.innerHTML = current_target_temperature > 0 ? (current_target_temperature + "°C") : "-";
		label_pwm.innerHTML = Math.round(current_pwm * 100 / 255).toString() + "%";
		label_heat.innerHTML = current_heat;
		label_motor.innerHTML = current_motor == "onoff" ? "on/off" : current_motor;
		var st = current_state;
		if (st == "baking") st += " / '" + current_program.stages[current_stage].stage_name.toLowerCase() + "' stage";
		label_state.innerHTML = st;
		label_header_state.innerHTML = st;
		label_header_state.parentNode.style.display = '';
		if (current_state != "idle" && current_state != "error")
		{
			label_header_program.innerHTML = current_program.program_name + 
				" (#" + (current_program.program_id+1) + " / crust '" + String.fromCharCode(65 + current_program.crust_id) + "')";
			label_header_program.parentNode.style.display = '';
		} else label_header_program.parentNode.style.display = 'none';
		var total_length = 0;
		if (current_program && current_program.stages)
		{
			for (var i = 0; i < current_program.stages.length; i++)
				total_length += current_program.stages[i].duration;
		}
		if (current_state == "baking")
		{
			var t = new Date();
			t.setSeconds(t.getSeconds() - current_time_passed);
			label_time_start.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
			var left_h = Math.floor(current_time_left / 3600).toString();
			var left_m = (Math.floor(current_time_left / 60) % 60).toString();
			while (left_m.length < 2) left_m = "0" + left_m;
			var left_s = (current_time_left % 60).toString();
			while (left_s.length < 2) left_s = "0" + left_s;
			label_time_left.innerHTML = left_h + ":" + left_m + ":" + left_s;
			t = new Date();
			t.setSeconds(t.getSeconds() + current_time_left);
			label_time_end.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
		} else if (current_state == "timer")
		{
			var t = new Date();
			t.setSeconds(t.getSeconds() + current_time_left);
			label_time_start.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
			var left_h = Math.floor((current_time_left + total_length) / 3600).toString();
			var left_m = (Math.floor((current_time_left + total_length) / 60) % 60).toString();
			while (left_m.length < 2) left_m = "0" + left_m;
			var left_s = ((current_time_left + total_length) % 60).toString();
			while (left_s.length < 2) left_s = "0" + left_s;
			label_time_left.innerHTML = left_h + ":" + left_m + ":" + left_s;
			t = new Date();
			t.setSeconds(t.getSeconds() + current_time_left + total_length);
			label_time_end.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
		} else if (current_state == "warming")
		{
			var t = new Date();
			t.setSeconds(t.getSeconds() - current_time_passed - total_length);
			label_time_start.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
			label_time_left.innerHTML = "-";
			t = new Date();
			t.setSeconds(t.getSeconds() - current_time_passed);
			label_time_end.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
		} else {
			label_time_start.innerHTML = "-";
			label_time_left.innerHTML = "-";
			label_time_end.innerHTML = "-";
		}

		label_loading.setAttribute('display', 'none');
	}
	
	function set_interval(i, s, r, g)
	{
		//if (interval == i) return;
		document.getElementById('interval_'+req_interval).classList.remove('active');
		req_interval = r;
		document.getElementById('interval_'+req_interval).classList.add('active');
		interval = i;
		step = s;
		grid_step = g;
		clean_data();
		reinit();
	}
