	var svg;
	var G;
	var update_timer;

	var step   = 5;
	var interval = 5;
	var req_interval = "last";
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
	var label_program_name;
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
	var current_program;
	var current_state = '';
	var current_stage = -1;

	function status_init()
	{
		last_stats.ts = (new Date()).getTime() / 1000;
		label_header_program = document.getElementById('lb_header_program');
		label_header_state = document.getElementById('lb_header_state');
		label_scale = document.getElementById('scale');
		label_program_name = document.getElementById('lb_program_name');
		label_temperature = document.getElementById('lb_temperature');
		label_target_temperature = document.getElementById('lb_target_temperature');
		label_pwm = document.getElementById('lb_pwm');
		label_heat = document.getElementById('lb_heat');
		label_motor = document.getElementById('lb_motor');
		label_state = document.getElementById('lb_state');
		label_time_start = document.getElementById('lb_time_start');
		label_time_left = document.getElementById('lb_time_left');
		label_time_end = document.getElementById('lb_time_end');
		parse_stats();
		draw_data();
		status_request();
	}

	function graph_init()
	{
		req_interval = "5sec";
		status_init();
		svg = document.getElementById('bwsvg');

		try {
			G = svg.getSVGDocument
				? svg.getSVGDocument() : svg.contentDocument;
		}
		catch(e) {
			G = document.embeds['bwsvg']
				? document.embeds['bwsvg'].getSVGDocument() : null;
		}

		if (G)
			clean_svg = G.getElementById('temperature').parentNode.innerHTML;

		clean_data();

		if (!G)
		{
			window.setTimeout(graph_init, 1000);
		} else {
			reinit();
		}
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

	function graph_resize()
	{
		if (width != svg.offsetWidth  - 2) reinit();
		if (update_timer) window.clearTimeout(update_timer);
		status_request();
	}
	
	function reinit()
	{
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

		label_scale.innerHTML = lng.window_size +": "+ Math.round(width / step * interval / 60);

		//draw_data();
	}
	
	function status_request()
	{
		var r = new XMLHttpRequest();
		var url;
		if (req_interval != 'last')
		{
			var count = data_stamp ? Math.round(Date.now() / 1000 - data_stamp + 5) : data_wanted;
			var last_i = req_interval;
			url = '/api/?method=stats&interval='+req_interval+'&count='+count;
		} else {
			url = '/api/?method=stats&interval='+req_interval;
		}
		r.open('GET', url, true);

		r.onreadystatechange = function()
		{
			if (r.readyState == 4) {
				if (r.status == 200)
				{
					try
					{
						if (req_interval != 'last' && last_i != req_interval) return;
						var data = eval('(' + r.responseText + ')');
						last_stats.ts = (new Date()).getTime() / 1000;
						last_stats.last_status = data.last_status;
						last_stats.last_program = data.last_program;
						parse_stats();

						if (data.stats)
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
					        
						/* cut off outdated entries */
						temperature = temperature.slice(temperature.length - data_wanted, temperature.length);
						target_temperature = target_temperature.slice(target_temperature.length - data_wanted, target_temperature.length);
						heat = heat.slice(heat.length - data_wanted, heat.length);
						motor = motor.slice(motor.length - data_wanted, motor.length);

						/* remember current timestamp */
						if (data.stats) data_stamp = data.stats[data.stats.length-1].time;

						//draw_data();
					}
					catch(e) {							
					}
				}
				update_timer = window.setTimeout(status_request, 3000);
			}
		}
		r.send(null);
	}	

	function parse_stats()
	{
		if (!last_stats.last_status.state != 'error')
		{
			current_temperature = last_stats.last_status.temp;
			current_target_temperature = last_stats.last_status.target_temp;
			current_pwm = last_stats.last_status.pwm;
			current_heat = last_stats.last_status.heat;
			current_motor = last_stats.last_status.motor;
			current_program = last_stats.last_program;
			current_state = last_stats.last_status.state;
			current_stage = last_stats.last_status.stage;
			current_time_passed = last_stats.last_status.passed ? last_stats.last_status.passed : 0;
			current_time_left = last_stats.last_status.left ? last_stats.last_status.left : 0;
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
	}
	
	function draw_data()
	{
		var st = lng[current_state];
		if (current_state == "baking" && current_program.stages[current_stage].stage_name) st += " (\"" + current_program.stages[current_stage].stage_name.toLowerCase() + "\")";
		if (current_state == "error") st += " (" + last_stats.last_status.error_text + ")";
		label_header_state.innerHTML = st;
		label_header_state.parentNode.style.display = '';
                
		if (label_state) label_state.innerHTML = st;
		if (label_temperature) label_temperature.innerHTML = Math.round(current_temperature) + "&#8451;";
		if (label_target_temperature) label_target_temperature.innerHTML = current_target_temperature > 0 ? (current_target_temperature + "&#8451;") : "-";
		if (label_pwm) label_pwm.innerHTML = Math.round(current_pwm * 100 / 255).toString() + "%";
		if (label_heat) label_heat.innerHTML = current_heat ? lng.on : lng.off;
		if (label_motor) label_motor.innerHTML = lng[current_motor];

		var program_name = '-';
		if (current_state != "idle" && current_state != "error")
		{
			program_name = current_program.program_name + " ("+lng.crust+" \""+String.fromCharCode(65+current_program.crust_id)+"\")"; 
			label_header_program.innerHTML = program_name;
			label_header_program.parentNode.style.display = '';
		} else {
			label_header_program.parentNode.style.display = 'none';
		}
		if (label_program_name) label_program_name.innerHTML = program_name;
		var total_length = 0;
		if (current_program && current_program.stages)
		{
			for (var i = 0; i < current_program.stages.length; i++)
				total_length += current_program.stages[i].duration;
		}
		var now = (new Date()).getTime() / 1000;
		var current_time_passed = last_stats.last_status.passed ? last_stats.last_status.passed : 0;
		var current_time_left = last_stats.last_status.left ? last_stats.last_status.left : 0;
		current_time_passed += now - last_stats.ts;
		current_time_left -= now - last_stats.ts;
		if (current_time_left < 0) current_time_left = 0;
		if (current_state == "baking")
		{
			var t = new Date((now - current_time_passed)*1000);
			if (label_time_start) label_time_start.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
			var left_h = Math.floor(current_time_left / 3600).toString();
			var left_m = (Math.floor(current_time_left / 60) % 60).toString();
			while (left_m.length < 2) left_m = "0" + left_m;
			var left_s = (Math.floor(current_time_left) % 60).toString();
			while (left_s.length < 2) left_s = "0" + left_s;
			if (label_time_left) label_time_left.innerHTML = left_h + ":" + left_m + ":" + left_s;
			t = new Date((now + current_time_left)*1000);
			if (label_time_end) label_time_end.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
		} else if (current_state == "timer")
		{
			var t = new Date((now + current_time_left)*1000);
			if (label_time_start) label_time_start.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
			var left_h = Math.floor((current_time_left + total_length) / 3600).toString();
			var left_m = (Math.floor((current_time_left + total_length) / 60) % 60).toString();
			while (left_m.length < 2) left_m = "0" + left_m;
			var left_s = (Math.floor(current_time_left + total_length) % 60).toString();
			while (left_s.length < 2) left_s = "0" + left_s;
			if (label_time_left) label_time_left.innerHTML = left_h + ":" + left_m + ":" + left_s;
			t = new Date((now + current_time_left + total_length)*1000);
			if (label_time_end) label_time_end.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
		} else if (current_state == "warming")
		{
			var t = new Date((now - current_time_passed - total_length)*1000);
			label_time_start.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
			label_time_left.innerHTML = "-";
			t = new Date((now - current_time_passed)*1000);
			if (label_time_end) label_time_end.innerHTML = t.toTimeString().replace(/.*(\d{2}:\d{2})(:\d{2}).*/, "$1");
		} else {
			if (label_time_start) label_time_start.innerHTML = "-";
			if (label_time_left) label_time_left.innerHTML = "-";
			if (label_time_end) label_time_end.innerHTML = "-";
		}

		if (typeof(svg) != "undefined" && svg)
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
			label_loading.setAttribute('display', 'none');
		}

		window.setTimeout(draw_data, 500);
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
		if (update_timer) window.clearTimeout(update_timer);
		status_request();
	}
