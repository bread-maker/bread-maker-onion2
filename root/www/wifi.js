	var ignore_offline = 0;
	var fail_count = 0;
	var update_wifi_status_timer;

	function wifi_init()
	{
		wifi_status_request();
		wifi_scan();
	}

	function wifi_status_request()
	{
		var p = document.getElementById("wifi_status");
		var r = new XMLHttpRequest();
		var url = '/?page=wifi.status';
		r.open('GET', url, true);

		r.onreadystatechange = function()
		{
			if (r.readyState == 4) {
				var data = null;
				if (r.status == 200)
				{
					try
					{
						data = eval('(' + r.responseText + ')');
					}
					catch(e) {							
					}
				}
				if (data)
				{
					var tbl = '<table class="border" style="width: 100%; max-width: 700px;"><th>'+lng.wifi_status+'</th><th>SSID</th><th>'+lng.ipv4_address+'</th><th>'+lng.ipv6_address+'</th></tr><tr>';
					tbl += '<td>'+(data.wifi_status.up ? lng.connected : lng.not_connected)+'</td>';
					tbl += '<td>'+data.ssid+'</td>';
					tbl += '<td>'+((data['wifi_status']['ipv4-address'] && data['wifi_status']['ipv4-address'].length > 0) ? data['wifi_status']['ipv4-address'][0].address : '---')+'</td>';
					tbl += '<td>'+((data['wifi_status']['ipv6-address'] && data['wifi_status']['ipv6-address'].length > 0) ? data['wifi_status']['ipv6-address'][0].address : '---')+'</td>';
					tbl += '</tr></table>';
					if (ignore_offline > 0)
						ignore_offline--;
					else
						p.innerHTML = tbl;
					fail_count = 0;
				} else {
					if (ignore_offline == 0)
						p.innerHTML = lng.breadmaker_offline;
					else if (++fail_count >= 10) 
						ignore_offline = 0;
				}
				update_wifi_status_timer = window.setTimeout(wifi_status_request, 1500);
			}
		}
		r.send(null);
	}	

	function wifi_scan()
	{
		var p = document.getElementById("wifi_scan");
		var r = new XMLHttpRequest();
		var url = '/?page=wifi.scan';
		r.open('GET', url, true);

		r.onreadystatechange = function()
		{
			if (r.readyState == 4) {
				var data = null;
				if (r.status == 200)
				{
					try
					{
						data = eval('(' + r.responseText + ')');
					}
					catch(e) {							
					}
				}
				if (data)
				{
					var tbl = '<table class="border" style="width: 100%; max-width: 700px;"><th>SSID</th><th>'+lng.signal_strength+'</th><th></th></tr>';
					for (var i = 0; i < data.length; i++)
					{
						tbl += '<tr>';
						tbl += '<td>'+data[i].ssid+(data[i].encryption == 'NONE' ? '' : ' &#128274;')+'</td>';
						//tbl += '<td>'+data[i].bssid+'</td>';
						tbl += '<td>'+data[i].signalStrength+'</td>';
						//tbl += '<td>'+data[i].encryption+'</td>';
						tbl += '<td><input type="submit" value=" &#128268; '+lng.connect+' " onclick="connect_ap(' + "'"+data[i].ssid + "','" + data[i].encryption + "'" +')" /></td>';
						tbl += '</tr>';
					}
					tbl += '</table>';
					p.innerHTML = tbl;
				} else {
					window.setTimeout(wifi_scan, 5000);
				}
			}
		}
		r.send(null);
	}	

	function connect_ap(ssid, encryption)
	{
		var key = '';
		if (encryption != 'NONE')
		{
			key = prompt(lng.enter_key);
			if (key == null) return;
		}

		var r = new XMLHttpRequest();
		var url = '/?page=wifi.connect_ap&ssid='+encodeURIComponent(ssid)+'&encryption='+encryption+'&key='+encodeURIComponent(key);
		r.open('GET', url, true);

		r.onreadystatechange = function()
		{
			if (r.readyState == 4) {
				var data = null;
				if (r.status == 200)
				{
					try
					{
						data = eval('(' + r.responseText + ')');
					}
					catch(e) {
					}
				}
				if (data && data.result == true)
				{
					ignore_offline = 5;
					var p = document.getElementById("wifi_status");
					p.innerHTML = lng.restarting_wifi;
				} else alert(lng.oops);
			}
		}
		r.send(null);
	}
