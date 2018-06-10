	var programs_edit_mode = false;
	var edit_stage_id = -1;
	var t_stages = [];
	var overrides = {};
	var start_program = 0;
	var start_duration = 0;
	var start_delay = 0;
	var ls_state = '';

	function programs_init()
	{
		sync_state();
		setInterval(function(){
			if (programs_edit_mode && edit_stage_id >= 0) validate_program(false, false);
			update_baking_button();
			sync_state();
		}, 1500);
		document.getElementById("loading").style.display = 'none';
	}

	function sync_state()
	{
		if (ls_state != current_state)
		{
			ls_state = current_state;
			if (current_state == 'idle')
			{
				show_programs();
				show_stages();
			} else {
				show_status();
			}
			window.scrollTo(0,0);
		}
	}

	function show_status()
	{
		document.getElementById("status").style.display = '';
		document.getElementById("error").style.display = current_state == 'error' ? '' : 'none';
		document.getElementById("abort_button").value = " \u26D4 "+
			((current_state == "baking")
			? lng.abort : ((current_state == "timer") ? lng.cancel : lng.stop)) + ' ';
		document.getElementById("abort").style.display = 
			(current_state == "timer" || current_state == "baking" || current_state == "warming")
			? "" : "none";
		document.getElementById("programs").style.display = 'none';
		document.getElementById("program_editor").style.display = 'none';
		document.getElementById("program_starter").style.display = 'none';
	}

	function show_programs()
	{
		document.getElementById("status").style.display = 'none';
		document.getElementById("program_starter").style.display = 'none';
		var p = document.getElementById("programs");
		if (edit_stage_id >= 0)
		{
			p.style.display = 'none';
			return;
		}
		var tbl = '<table id="programs_table" class="border" style="width: 100%; max-width: 700px;"><tr><th style="width: 10%">#</th><th>Name</th><th style="width: 20%">Duration</th><th style="width: 25%"></th></tr>';
		for (var i = 0; i < programs.length; i++)
		{
			tbl += '<tr><td>'+(i+1).toString()+'</td><td>'+programs[i].program_name+'</td>';
			tbl += '<td>'+get_program_duration(programs[i])+'</td>';
			tbl += '<td style="vertical-align: bottom">';
			if (!programs_edit_mode)
				tbl += '<input type="button" value=" &#127838; '+lng.start_baking+' " onclick="start_baking('+i.toString()+')"/>';
			else {
				tbl += '<input type="button" value=" &#9999; '+lng.edit+' " onclick="edit_program('+i.toString()+')"/>';
				tbl += ' <input type="button" value=" &#8673; " '+(i>0 ? '' : 'disabled')+' onclick="move_program_up('+i.toString()+')"/>';
				tbl += ' <input type="button" value=" &#8675; " '+(i+1<programs.length ? '' : 'disabled')+' onclick="move_program_down('+i.toString()+')"/>';
				tbl += ' <input type="button" value=" &#10060; " onclick="delete_program('+i.toString()+')"/>';
			}
			tbl += '</td></tr>';
		}
		tbl += '</table>';
		tbl += '<div align="right" style="width: 100%; max-width: 650px;">';
		if (!programs_edit_mode)
			tbl += '<br/><br/><input type="button" value="&#9999; '+lng.edit+' " onclick="programs_edit_start()" style="width: 150px">';
		else
			tbl += '<br/><input type="button" value=" &#10010; " onclick="add_program()" style="width: 35px">'+
				'<br/><br/><input type="button" value=" '+lng.cancel+' " onclick="revert_all()" style="width: 100px"> '+
				'<input type="button" value=" '+lng.save+' " onclick="programs_save()" style="width: 100px">';
		tbl += '</div>';
		p.innerHTML = tbl;
		p.style.display = '';
	}

	function edit_program(id)
	{
		if (current_state != 'idle')
		{
			alert(lng.only_idle_state);
			return;
		}
		edit_stage_id = id;
		var program = programs[edit_stage_id];
		var stages = program.stages;
		t_stages = [];
		for(var i = 0; i < stages.length; i++)
		{
			var stage = stages[i];
			t_stages.push({
				"stage_name": stage.stage_name,
				"duration_mins": stage.duration ? Math.floor(stage.duration/60).toString() : "0",
				"duration_secs": stage.duration ? (stage.duration%60).toString() : "0",
				"temp": stage.temp ? stage.temp.toString() : "0",
				"temp_b": stage.temp_b ? stage.temp_b.toString() : (stage.temp ? stage.temp.toString() : "0"),
				"temp_c": stage.temp_c ? stage.temp_c.toString() : (stage.temp ? stage.temp.toString() : "0"),
				"motor": stage.motor ? stage.motor : lng.off,
				"beeps": stage.beeps ? stage.beeps.toString() : "0",
				"beeps_mins": stage.beeps_time ? Math.floor(stage.beeps_time/60).toString() : "0",
				"beeps_secs": stage.beeps_time ? (stage.beeps_time%60).toString() : "0"
			});
		}

		if (program.max_temp_a && program.max_temp_a > 0) overrides.max_temp_a = program.max_temp_a.toString();
		if (program.max_temp_b && program.max_temp_b > 0) overrides.max_temp_b = program.max_temp_b.toString();
		if (program.max_warm_time && program.max_warm_time >= 0) overrides.max_warm_time = program.max_warm_time.toString();
		if (program.warm_temp && program.warm_temp > 0) overrides.warm_temp = program.warm_temp.toString();

		show_stages();
		window.scrollTo(0,0);
	}

	function program_edit_cancel()
	{
		edit_stage_id = -1;
		for (var i = programs.length-1; i >= 0; i--)
			if (programs[i].stages.length == 0)
				programs.splice(i, 1);
		show_programs();
		show_stages();
		window.scrollTo(0,0);
	}

	function program_edit_save()
	{
		if (current_state != 'idle')
		{
			alert(lng.only_idle_state);
			return;
		}
		var program = validate_program(true, true);
		if (program != false)
		{
			programs[edit_stage_id] = program;
			edit_stage_id = -1;
			show_programs();
			show_stages();
		}
		window.scrollTo(0,0);
	}

	function show_stages()
	{
		var p = document.getElementById("program_editor");
		if (edit_stage_id < 0)
		{
			p.style.display = 'none';
			return;
		}
		document.getElementById("programs").style.display = 'none';
		var program = programs[edit_stage_id];

		var tbl = lng.program_name+': <input id="program_name" type="text" style="width: 350px" value="'+program.program_name.replace(/"/g, '&quot;')+'"/><br/><br/>';
		tbl += '<table id="stages_table" class="border" style="width: 100%; max-width: 700px;"><tr><th colspan="3">'+lng.stages+'</th></tr>';
		for (var i = 0; i < t_stages.length; i++)
		{
			var stage = t_stages[i];
			tbl += '<tr><td style="width: 10%">'+(i+1).toString()+'</td><td>';
			tbl += '<table class="noborder" style="width: 100%">';
			tbl += '<tr><td style="width: 1%">'+lng.stage_name+': </td>';
			tbl += '<td style="width: 70%" colspan="2"><input id="stage_name'+i.toString()+'" type="text" style="width: 100%" value="'+stage.stage_name.replace(/"/g, '&quot;')+'"/></td></tr>';
			tbl += '<tr><td colspan="3"><hr/></td></tr>';
			tbl += '<tr><td>'+lng.duration+':</td>';
			tbl += '<td colspan="2"><input id="mins'+i.toString()+'" type="number" min="0" max="'+max_duration_mins.toString()+'" style="width: 40px" value="'+stage.duration_mins.replace(/"/g, '&quot;')+'"/> '+lng.minutes+' ';
			tbl += '<input id="secs'+i.toString()+'" type="number" min="0" max="59" style="width: 40px" value="'+stage.duration_secs.replace(/"/g, '&quot;')+'"/> '+lng.seconds+'</td></tr>';
			tbl += '<tr><td colspan="3"><hr/></td></tr>';
			tbl += '<tr><td rowspan="3">'+lng.target_br_temperature+':</td><td style="width: 1%">';
			tbl += lng.crust_a + ':</td><td><input id="temp'+i.toString()+'" type="number" min="0" max="'+max_temp.toString()+'" style="width: 50px" value="'+stage.temp.replace(/"/g, '&quot;')+'" onfocus="load_stages_data()" oninput="sync_temp('+i.toString()+',this.value)"/> &#176;C</td></tr>';
			tbl += '<tr><td>'+lng.crust_b+':</td><td><input id="temp_b'+i.toString()+'" type="number" min="0" max="'+max_temp.toString()+'" style="width: 50px" value="'+stage.temp_b.replace(/"/g, '&quot;')+'" onfocus="load_stages_data()"/> &#176;C</td></tr>';
			tbl += '<tr><td>'+lng.crust_c+':</td><td><input id="temp_c'+i.toString()+'" type="number" min="0" max="'+max_temp.toString()+'" style="width: 50px" value="'+stage.temp_c.replace(/"/g, '&quot;')+'" onfocus="load_stages_data()"/> &#176;C</td></tr></td>';
			tbl += '<tr><td colspan="3"><hr/></td></tr>';
			tbl += '<tr><td>'+lng.motor_mode+':</td><td colspan="2"><select id="motor'+i.toString()+'">';
			tbl += '<option value="off" '+(stage.motor == 'off' ? 'selected' : '')+'>'+lng.off+'</option>';
			tbl += '<option value="onoff" '+(stage.motor == 'onoff' ? 'selected' : '')+'>'+lng.onoff+'</option>';
			tbl += '<option value="on" '+(stage.motor == 'on' ? 'selected' : '')+'>'+lng.on+'</option>';
			tbl += '</select></td></tr>';
			tbl += '<tr><td colspan="3"><hr/></td></tr>';
			tbl += '<tr><td rowspan="3">'+lng.beeps+':</td><td colspan="2">'+lng.beep+' <input id="beeps'+i.toString()+'" type="number" min="0" max="9" style="width: 30px" value="'+stage.beeps.replace(/"/g, '&quot;')+'" oninput="set_beep_timer_enabled('+i.toString()+', this.value)"/> '+lng.times_after+'</td></tr>';
			tbl += '<tr><td colspan="2"><input id="beeps_mins'+i.toString()+'" type="number" min="0" max="999" style="width: 40px" value="'+stage.beeps_mins.replace(/"/g, '&quot;')+'" '+(parseInt(stage.beeps)>0 ? '' : 'DISABLED')+'/> '+lng.minutes+' ';
			tbl += '<input id="beeps_secs'+i.toString()+'" type="number" min="0" max="59" style="width: 40px" value="'+stage.beeps_secs.replace(/"/g, '&quot;')+'" '+(parseInt(stage.beeps)>0 ? '' : 'DISABLED')+'/> '+lng.seconds+'</td></tr>';
			tbl += '<tr><td colspan="2">'+lng.from_stage_start+'</td></tr>';
			tbl += '</table>';

			tbl += '</td><td style="width: 1%">';
			tbl += ' <input type="button" value=" &#8673; " '+(i>0 ? '' : 'disabled')+' onclick="move_stage_up('+i.toString()+')"/>';
			tbl += ' <input type="button" value=" &#8675; " '+(i+1<t_stages.length ? '' : 'disabled')+' onclick="move_stage_down('+i.toString()+')"/>';
			tbl += ' <input type="button" value=" &#10060; " '+(t_stages.length > 1 ? '' : 'disabled')+' onclick="delete_stage('+i.toString()+')"/>';
			tbl += '</td></tr>';
		}
		tbl += '</table>';
		tbl += '<br/>';
		tbl += '<div align="right" style="width: 100%; max-width: 650px;"><input type="button" value=" &#10010; " onclick="add_stage()" style="width: 35px"></div>';
		tbl += '<br/><br/>';

		tbl += '<table id="stages_table" class="settings" style="width: 100%; max-width: 700px;"><tr><th colspan="2">'+lng.advanced_settings+'</th></td>'
		tbl += '<tr><td><div style="width: 80%"><input id="override_max_temp_a" type="checkbox" '+(overrides.max_temp_a ? 'CHECKED' : '')+' onchange="check_overrides_data()" /><label for="override_max_temp_a"> '+lng.override_temp_a+'</label>:</div></td>'+
			'<td style="width: 1%"><input id="max_temp_a" type="number" min="0" max="'+max_temp.toString()+'" style="width: 50px" value="'+(overrides.max_temp_a ? overrides.max_temp_a : global_config.max_temp_a.toString()).replace(/"/g, '&quot;')+'" '+(overrides.max_temp_a ? '' : 'DISABLED')+'/> &#176;C</td></tr>';
		tbl += '<tr><td><div style="width: 80%"><input id="override_max_temp_b" type="checkbox" '+(overrides.max_temp_b ? 'CHECKED' : '')+' onchange="check_overrides_data()" /><label for="override_max_temp_b"> '+lng.override_temp_b+'</label>:</div></td>'+
			'<td style="width: 1%"><input id="max_temp_b" type="number" min="0" max="'+max_temp.toString()+'" style="width: 50px" value="'+(overrides.max_temp_b ? overrides.max_temp_b : global_config.max_temp_b.toString()).replace(/"/g, '&quot;')+'" '+(overrides.max_temp_b ? '' : 'DISABLED')+'/> &#176;C</td></tr>';
		tbl += '<tr><td><div style="width: 80%"><input id="override_warm_temp" type="checkbox" '+(overrides.warm_temp ? 'CHECKED' : '')+' onchange="check_overrides_data()" /><label for="override_warm_temp"> '+lng.override_warm_temp+'</label>:</div></td>'+
			'<td style="width: 1%"><input id="warm_temp" type="number" min="0" max="'+max_temp.toString()+'" style="width: 50px" value="'+(overrides.warm_temp ? overrides.warm_temp : global_config.warm_temp.toString()).replace(/"/g, '&quot;')+'" '+(overrides.warm_temp ? '' : 'DISABLED')+'/> &#176;C</td></tr>';
		tbl += '<tr><td><div style="width: 80%"><input id="override_max_warm_time" type="checkbox" '+(overrides.max_warm_time ? 'CHECKED' : '')+' onchange="check_overrides_data()" /><label for="override_max_warm_time"> '+lng.override_warm_time+'</label>:</div></td>'+
			'<td style="width: 1%"><input id="max_warm_time" type="number" min="0" max="999" style="width: 50px" value="'+(overrides.max_warm_time ? overrides.max_warm_time : global_config.max_warm_time.toString()).replace(/"/g, '&quot;')+'" '+(overrides.max_warm_time ? '' : 'DISABLED')+'/> minutes</td></tr>';
		tbl += '</table>';
		tbl += '<br/>';
		tbl += '<div align="right" style="width: 100%; max-width: 650px;">';
		tbl += '<input type="button" value=" '+lng.cancel+' " onclick="program_edit_cancel()" style="width: 100px"> ';
		tbl += '<input type="button" value=" '+lng.ok+' " onclick="program_edit_save()" style="width: 100px">';
		tbl += '</div>';
		p.innerHTML = tbl;
		p.style.display = '';
	}

	function set_beep_timer_enabled(i, v)
	{
		document.getElementById('beeps_mins'+i.toString()).disabled =
		document.getElementById('beeps_secs'+i.toString()).disabled = !(parseInt(v) > 0);
	}

	function load_stages_data()
	{
		var c = t_stages.length;
		t_stages = [];
		for(var i = 0; i < c; i++)
		{
			t_stages.push({
				"stage_name": document.getElementById('stage_name'+i.toString()).value,
				"duration_mins": document.getElementById('mins'+i.toString()).value,
				"duration_secs": document.getElementById('secs'+i.toString()).value,
				"temp": document.getElementById('temp'+i.toString()).value,
				"temp_b": document.getElementById('temp_b'+i.toString()).value,
				"temp_c": document.getElementById('temp_c'+i.toString()).value,
				"motor":  document.getElementById('motor'+i.toString()).value,
				"beeps": document.getElementById('beeps'+i.toString()).value,
				"beeps_mins": document.getElementById('beeps_mins'+i.toString()).value,
				"beeps_secs": document.getElementById('beeps_secs'+i.toString()).value
			});
		}

		var new_overrides = {};
		if (document.getElementById('override_max_temp_a').checked)
			new_overrides.max_temp_a = document.getElementById('max_temp_a').value;
		if (document.getElementById('override_max_temp_b').checked)
			new_overrides.max_temp_b = document.getElementById('max_temp_b').value;
		if (document.getElementById('override_max_warm_time').checked)
			new_overrides.max_warm_time = document.getElementById('max_warm_time').value;
		if (document.getElementById('override_warm_temp').checked)
			new_overrides.warm_temp = document.getElementById('warm_temp').value;
		overrides = new_overrides;
	}

	function check_overrides_data()
	{
		load_stages_data();
		show_stages();
	}

	function validate_program(show_alert, scroll)
	{
		var t_program = {};
		var problems = '';
		var ok = [];
		var err = [];
		var p = document.getElementById('program_name');
		if (p.value == '')
		{
			problems += lng.program_name_cant_empty+'\r\n';
			err.push(p);
		} else {
			t_program.program_name = p.value;
			ok.push(p);
		}

		t_program.stages = [];
		var current_max_temp = 0;
		for(var i = 0; i < t_stages.length; i++)
		{
			var stage = {};
			var p = document.getElementById('stage_name'+i.toString());
			if (p.value == '')
			{
				problems += lng.stage_name_cant_empty+'\r\n';
				err.push(p);
			} else {
				stage.stage_name = p.value;
				ok.push(p);
			}
			p = document.getElementById('mins'+i.toString());
			var mins = parseInt(p.value);
			if (isNaN(mins) || mins > max_duration_mins)
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				ok.push(p);
			}
			var p2 = document.getElementById('secs'+i.toString());
			var secs = parseInt(p2.value);
			if (isNaN(secs) || secs > 59)
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p2);
			} else if (mins == 0 && secs == 0)
			{
				problems += lng.stage_duration_cant_zero+'\r\n';
				err.push(p);
				err.push(p2);
			} else {
				stage.duration = mins*60+secs;
				ok.push(p2);
			}
			p = document.getElementById('temp'+i.toString());
			var temp = parseInt(p.value);
			if (isNaN(temp) || temp < 0 || temp > max_temp)
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				stage.temp = temp;
				if (temp > current_max_temp) current_max_temp = temp;
				ok.push(p);
			}
			p = document.getElementById('temp_b'+i.toString());
			var temp_b = parseInt(p.value);
			if (isNaN(temp_b) || temp_b < 0 || temp_b > max_temp)
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				if (temp_b != temp) stage.temp_b = temp_b;
				if (temp_b > current_max_temp) current_max_temp = temp_b;
				ok.push(p);
			}
			p = document.getElementById('temp_c'+i.toString());
			var temp_c = parseInt(p.value);
			if (isNaN(temp_c) || temp_c < 0 || temp_c > max_temp)
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				if (temp_c != temp) stage.temp_c = temp_c;
				if (temp_c > current_max_temp) current_max_temp = temp_c;
				ok.push(p);
			}
			p = document.getElementById('motor'+i.toString());
			var motor = p.value;
			if ((motor == 'onoff' || motor == 'on') && current_max_temp > baking_temp)
			{
				problems += lng.motor_not_safe+'\r\n';
				err.push(p);
			} else {
				stage.motor = motor;
				ok.push(p);
			}
			p = document.getElementById('beeps'+i.toString());
			var beeps = parseInt(p.value);
			if (isNaN(beeps))
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				ok.push(p);
				if (beeps > 0) stage.beeps = beeps;
			}
			p = document.getElementById('beeps_mins'+i.toString());
			var beeps_mins = parseInt(p.value);
			if (beeps > 0 && (isNaN(beeps_mins) || beeps_mins > max_duration_mins))
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				ok.push(p);
			}
			var p2 = document.getElementById('beeps_secs'+i.toString());
			var beeps_secs = parseInt(p2.value);
			if (beeps > 0 && (isNaN(beeps_secs) || beeps_secs > 59))
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p2);
			} else if (beeps > 0 && (beeps_mins*60 + beeps_secs >= mins*60 + secs))
			{
				problems += lng.invalid_beep_time+'\r\n';
				err.push(p);
				err.push(p2);
			} else {
				if (beeps > 0) stage.beeps_time = beeps_mins*60+beeps_secs;
				ok.push(p2);
			}
			t_program.stages.push(stage);
		}

		if (document.getElementById('override_max_temp_a').checked)
		{
			p = document.getElementById('max_temp_a');
			var max_temp_a = parseInt(p.value);
			if (isNaN(max_temp_a) || max_temp_a > max_temp)
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				t_program.max_temp_a = max_temp_a;
				ok.push(p);
			}
		}
		if (document.getElementById('override_max_temp_b').checked)
		{
			p = document.getElementById('max_temp_b');
			var max_temp_b = parseInt(p.value);
			if (isNaN(max_temp_b) || max_temp_b > max_temp)
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				t_program.max_temp_b = max_temp_b;
				ok.push(p);
			}
		}
		if (document.getElementById('override_max_warm_time').checked)
		{
			p = document.getElementById('max_warm_time');
			var max_warm_time = parseInt(p.value);
			if (isNaN(max_warm_time))
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				t_program.max_warm_time = max_warm_time;
				ok.push(p);
			}
		}
		if (document.getElementById('override_warm_temp').checked)
		{
			p = document.getElementById('warm_temp');
			var warm_temp = parseInt(p.value);
			if (isNaN(warm_temp) || warm_temp > max_temp)
			{
				problems += lng.invalid_integer+'\r\n';
				err.push(p);
			} else {
				t_program.warm_temp = warm_temp;
				ok.push(p);
			}
		}

		if (t_program.stages.length == 0)
		{
			problems += lng.program_cant_empty+'\r\n';
		}

		for(var i = 0; i < ok.length; i++)
			ok[i].style.backgroundColor = '';
		for(var i = 0; i < err.length; i++)
			err[i].style.backgroundColor = 'pink';
		if (scroll && err.length > 0) err[0].scrollIntoView();
		if (show_alert && problems.length > 0) alert(problems);
		return problems.length == 0 ? t_program : false;
	}

	function sync_temp(i, v)
	{
		if (t_stages[i].temp == t_stages[i].temp_b)
			document.getElementById('temp_b'+i.toString()).value = v;
		if (t_stages[i].temp == t_stages[i].temp_c)
			document.getElementById('temp_c'+i.toString()).value = v;
		load_stages_data();
	}

	function move_program_up(id)
	{
		var tmp = programs[id-1];
		programs[id-1] = programs[id];
		programs[id] = tmp;
		show_programs();
	}

	function move_program_down(id)
	{
		var tmp = programs[id+1];
		programs[id+1] = programs[id];
		programs[id] = tmp;
		show_programs();
	}

	function programs_edit_start()
	{
		if (current_state != 'idle')
		{
			alert(lng.only_idle_state);
			return;
		}
		programs_edit_mode = true;
		show_programs();
	}

	function programs_edit_end()
	{
		programs_edit_mode = false;
		show_programs();
	}

	function programs_save()
	{
		if (current_state != 'idle')
		{
			alert(lng.only_idle_state);
			return;
		}
		var form = document.createElement("form");
		form.setAttribute("method", "POST");
		var hiddenField = document.createElement("input");
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "new_programs");
		hiddenField.setAttribute("value", JSON.stringify(programs));
		form.appendChild(hiddenField);
		document.body.appendChild(form);
		orig_programs = programs;
		form.submit();
	}

	function revert_all()
	{
		programs = orig_programs;
		programs_edit_end();
	}

	function get_program_duration_secs(program)
	{
		var total_duration = 0;
		if (program && program.stages)
		{
			for (var i = 0; i < program.stages.length; i++)
				total_duration += program.stages[i].duration;
		}
		return total_duration;
	}

	function secs_to_time(secs)
	{
		var left_h = Math.floor(secs / 3600).toString();
		var left_m = (Math.floor(secs / 60) % 60).toString();
		while (left_m.length < 2) left_m = "0" + left_m;
		//var left_s = (secs % 60).toString();
		//while (left_s.length < 2) left_s = "0" + left_s;
		return left_h + ":" + left_m /*+ ":" + left_s*/;
	}

	function get_program_duration(program)
	{
		var total_duration = get_program_duration_secs(program);
		return secs_to_time(total_duration);
	}

	function delete_program(id)
	{
		if (!confirm(lng.are_you_sure_delete_program)) return;
		programs.splice(id, 1);
		show_programs();
	}

	function add_program()
	{
		programs.push({"program_name": lng.new_baking_program_title, "stages": []});
		edit_program(programs.length-1);
		add_stage();
	}

	function move_stage_up(id)
	{
		load_stages_data();
		var tmp = t_stages[id-1];
		t_stages[id-1] = t_stages[id];
		t_stages[id] = tmp;
		show_stages();
	}

	function move_stage_down(id)
	{
		load_stages_data();
		var tmp = t_stages[id+1];
		t_stages[id+1] = t_stages[id];
		t_stages[id] = tmp;
		show_stages();
	}

	function delete_stage(id)
	{
		if (!confirm(lng.are_you_sure_delete_stage)) return;
		load_stages_data();
		t_stages.splice(id, 1);
		show_stages();
	}

	function add_stage()
	{
		load_stages_data();
		t_stages.push({
			"stage_name": lng.new_baking_stage_title,
			"duration_mins": "0",
			"duration_secs": "0",
			"temp": "0",
			"temp_b": "0",
			"temp_c": "0",
			"motor": "off",
			"beeps": "0",
			"beeps_mins": "0",
			"beeps_secs": "0"
		});
		show_stages();
	}

	function start_baking(id)
	{
		if (current_state != 'idle')
		{
			alert(lng.baking_only_idle_state);
			return;
		}
		var p = document.getElementById("program_starter");
		document.getElementById("programs").style.display = 'none';
		var program = programs[id];
		start_program = id;
		start_duration = get_program_duration_secs(program);

		var tbl = '<table class="no_border" style="width: 100%; max-width: 700px;">';
		tbl += '<tr><td colspan="3" style="text-align: center; height: 50px; font-size: 20pt;">'+program.program_name.replace(/"/g, '&quot;')+'<br/></td></tr>';
		tbl += '<tr><td colspan="3"><hr></td></tr>';
		tbl += '<tr><td style="width: 50%; padding-left: 50px; padding-top: 20px; padding-bottom: 20px;">'+lng.Crust+': <select id="start_crust" style="width: 170px"><option value="0">'+lng.crust_a+'</option><option value="1">'+lng.crust_b+'</option><option value="2">'+lng.crust_c+'</option></select></td>';
		tbl += '<td style="width: 50%; padding-right: 50px; padding-top: 20px; padding-bottom: 20px; text-align: right;">'+lng.start_time+': <input type="time" id="baking_start_time" oninput="start_check_time_start()">';
		tbl += '<br/>'+lng.end_time+': <input type="time" id="baking_end_time" oninput="start_check_time_end()"></td></tr>';
		tbl += '<tr><td colspan="3"><hr></td></tr>';
		tbl += '</table>'
		tbl += '<br/><br/>';
		tbl += '<input type="button" value=" '+lng.cancel+' " onclick="show_programs()" style="width: 100px"> ';
		tbl += '<input type="button" id="start_baking_button" value="" onclick="start_baking_go()" style="width: 200px">';

		p.innerHTML = tbl;
		update_baking_button();
		p.style.display = '';
	}

	function start_check_time_start()
	{
		var start = document.getElementById("baking_start_time");
		var end = document.getElementById("baking_end_time");
		var sv = start.value;
		if (sv && sv.length == 5)
		{
			var secs = parseInt(sv.substring(0,2))*3600+parseInt(sv.substring(3,5))*60;
			var end_time = secs + Math.floor(start_duration/60)*60;
			while (end_time > 86400) end_time -= 86400;
			var ev = secs_to_time(end_time);
			while (ev.length < 5) ev = "0"+ev;
			end.value = ev;
		} else end.value = null;
		update_baking_button();
	}

	function start_check_time_end()
	{
		var start = document.getElementById("baking_start_time");
		var end = document.getElementById("baking_end_time");
		var ev = end.value;
		if (ev && ev.length == 5)
		{
			var secs = parseInt(ev.substring(0,2))*3600+parseInt(ev.substring(3,5))*60;
			var start_time = secs - Math.floor(start_duration/60)*60;
			while (start_time < 0) start_time += 86400;
			var sv = secs_to_time(start_time);
			while (sv.length < 5) sv = "0"+sv;
			start.value = sv;
		} else start.value = null;
		update_baking_button();
	}

	function update_baking_button()
	{
		var b = document.getElementById("start_baking_button");
		if (!b) return;
		var v = lng.start_now;
		start_delay = 0;
		var start = document.getElementById("baking_start_time");
		var end = document.getElementById("baking_end_time");
		var sv = start.value;
		if (sv && sv.length == 5)
		{
			var t = new Date();			
			var secs_start = parseInt(sv.substring(0,2))*3600+parseInt(sv.substring(3,5))*60;
			var secs_now = t.getHours()*3600+t.getMinutes()*60+t.getSeconds();
			if (Math.abs(secs_start - secs_now) > 60)
			{
				start_delay = secs_start - secs_now;
				while (start_delay < 0) start_delay += 86400;
				v = lng.start_after+' '+secs_to_time(start_delay);
			}
		}
		b.value = " "+v+" ";
	}

	function start_baking_go()
	{
		var form = document.createElement("form");
		form.setAttribute("method", "POST");
		var hiddenField = document.createElement("input");
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "start_program");
		hiddenField.setAttribute("value", "1");
		form.appendChild(hiddenField);
		var hiddenField = document.createElement("input");
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "program_id");
		hiddenField.setAttribute("value", start_program);
		form.appendChild(hiddenField);
		hiddenField = document.createElement("input");
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "crust_id");
		hiddenField.setAttribute("value", document.getElementById("start_crust").value);
		form.appendChild(hiddenField);
		hiddenField = document.createElement("input");
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "timer");
		hiddenField.setAttribute("value", start_delay.toString());
		form.appendChild(hiddenField);
		document.body.appendChild(form);
		form.submit();
	}

	function abort_baking()
	{
		if (!confirm(lng.are_you_sure)) return;
		var form = document.createElement("form");
		form.setAttribute("method", "POST");
		var hiddenField = document.createElement("input");
		hiddenField.setAttribute("type", "hidden");
		hiddenField.setAttribute("name", "abort");
		hiddenField.setAttribute("value", "1");
		form.appendChild(hiddenField);
		document.body.appendChild(form);
		form.submit();
	}

	window.onbeforeunload = function() {
		if (JSON.stringify(programs) != JSON.stringify(orig_programs))
		{
			return confirm(lng.you_have_unsaved_changes+'. '+lng.are_you_sure);
		}
	}
