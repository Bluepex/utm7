<?php
/*
 * foot.inc
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2019 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
 * We put jquery in the footer; it is the preferred position for speed
 * and helps developers to use events[] instead of executing scripts directly.
 */
?>
	</div>
	<div><!-- END page-wrapper -->
	<footer class="footer">
		<div class="content col-sm-12 text-center">
			<div class="copyright mt-5 col-12 text-center">
				<a target="_blank" href="<?=$g["product_copyright_url"];?>" style="color: #666">
					<i>&copy; <?=date("Y");?> BluePex S.A. - <?=gettext("All rights reserved.")?></i>
				</a>
			</div>
		</div>
	</footer>
	</div><!-- END wrapper -->

	<!-- This use of filemtime() is intended to fool the browser into reloading the file (not using the cached version) if the file is changed -->

	<script src="/vendor/jquery/jquery-1.12.0.min.js?v=<?=filemtime('/usr/local/www/vendor/jquery/jquery-1.12.0.min.js')?>"></script>
	<script src="/vendor/jquery/jquery-ui-1.11.4.min.js?v=<?=filemtime('/usr/local/www/vendor/jquery/jquery-ui-1.11.4.min.js')?>"></script>
	<script src="/vendor/bootstrap/js/bootstrap.min.js?v=<?=filemtime('/usr/local/www/vendor/bootstrap/js/bootstrap.min.js')?>"></script>
	<script src="/js/pfSense.js?v=<?=filemtime('/usr/local/www/js/pfSense.js')?>"></script>
	<script src="/js/pfSenseHelpers.js?v=<?=filemtime('/usr/local/www/js/pfSenseHelpers.js')?>"></script>
	<script src="/js/polyfills.js?v=<?=filemtime('/usr/local/www/js/polyfills.js')?>"></script>
	<script src="/vendor/sortable/sortable.js?v=<?=filemtime('/usr/local/www/vendor/sortable/sortable.js')?>"></script>
	<!--<script src="/vendor/bp-dictionary/dictionary.js?v=<?=filemtime('/usr/local/www/vendor/bp-dictionary/dictionary.js')?>"></script>-->
	<script src="/js/BluePexUTM.js?v=<?=filemtime('/usr/local/www/js/BluePexUTM.js')?>"></script>
	<script src="/vendor/bootstrap/js/bootstrap-switch.min.js"></script>

	<script type="text/javascript">

	function showAdvancedForm(gid, sid) {
		// Show the modal dialog with rule text
		$('#rulesviewer_text').text("...Loading...");
		$('#rulesviewer').modal('show');
		$('#modal_rule_category').html($('#selectbox').val());
		loadComplete;
	}	

	function loadComplete(req) {
		$('#rulesviewer_text').text(atob(req.responseText));
		$('#rulesviewer_text').attr('readonly', true);
	}

	<?php if (!in_array($_SERVER['PHP_SELF'], ['/status_graph.php'])): ?>
	function ajax_requests_icons_menubar() {
		$('#ajax_requests_icons_menubar').html('<img style="width:16px;height:16px;" src="../images/loading-sniffer.gif"></img>');
		$.ajax({
			method: 'GET',
			data: {
				ajax_requests_icons_menubar: 'true',
			},
			success: function(data) {
				$("#ajax_requests_icons_menubar").html(data);
			},
			error: function(data) {
				$("#ajax_requests_icons_menubar").html('');
			},
		});
	}

	setTimeout(() => {
		ajax_requests_icons_menubar();
	}, 100);
	<?php endif; ?>

	//<![CDATA[
		// Un-hide the "Top of page" icons if the page is larger than the window
		if ($(document).height() > $(window).height()) {
			$('[id^=tp]').show();
		}
	//]]>
	<?php if($_SERVER['REQUEST_URI'] == '/index.php' || $_SERVER['REQUEST_URI'] == '/active_protection/ap_services.php' || $_SERVER['REQUEST_URI'] == '/firewallapp/consumo_de_aplicacoes.php' ) { ?>

	var token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6Ijk1OTQzZGJkNzMwM2JkNDZiOTE2NDViNjUzMmJhMTU5YjJhNmY5YzZjZmRjYjQ3NTEyMjhlYTAzYWY5MWE2OTYzYmZkODQ4Nzg4MTAzM2I0In0.eyJhdWQiOiIxIiwianRpIjoiOTU5NDNkYmQ3MzAzYmQ0NmI5MTY0NWI2NTMyYmExNTliMmE2ZjljNmNmZGNiNDc1MTIyOGVhMDNhZjkxYTY5NjNiZmQ4NDg3ODgxMDMzYjQiLCJpYXQiOjE1ODA0OTk0MzksIm5iZiI6MTU4MDQ5OTQzOSwiZXhwIjo0NzM2MTczMDM5LCJzdWIiOiIxIiwic2NvcGVzIjpbXX0.e8fBNcvjKLx4U_zmCy0GO0GiV-CyHvf5_zUx9YSUcc4RnWGD_338Whbj9dgx6xbJo6LxazBgDNBrKH4Do_aK9szuxHGdVmnw94ZYWqUnji_mW4mci30gaZ2bvRp5zfHSxetCjCC9NW48mqWEYaCTAv9gzNYUqg9X1-c9YYb80adOnrcxfW56dZe6cltsndZ2GuMiu69R0zxbPSWaiWTtvJ3KDmuDUu1aYePOh-ny-xaRFXp9g7jPCfJg-zJ60DW_kYZvi5SSsPSXCgGzBXqjWcZc7aAlcT7wzUYjIKLE0A-qIsSFMV3WnKwv78zgD_bHc9rnuGQrgaSusMc8kq1Y5gX7cGILgH84yATq7HJ-VdRuFNm1guu2Z_CAFwnZPlhjGBxZj4ay2-UAXvMz0id-MGozJS4-cu7RlyAhgYrFyCjl2qgeXXclVd4EKz4ES_1ge0DTdmMaEckwXKF4BxcVtbCrz4fsiHO-Zc2Y20QlXLu3RUGnQEkaaxQEEzuw9icrysyz3ACEkgsRfJtk3LZ9g-h5T1RNeb1kGpWPf4YoceF1aWWOBWgef7nRH0QhoWL_9qjHFVJyrV7yEDE2cekBQ8WdLTYS7CeTA2hHo8GJD461_8aMk2bfIpfhTbNCMXYVXtJhgUKXNOiqzcTaVf5V-sAOE359MMxA4v0sHJ2xFzM";

	$.ajax({
		url: 'https://suiteapi.bluepex.com.br/api/utm/latest-backup',
		method: 'POST',
		data: {
			serial: '<?php echo file_exists('/etc/serial') ? trim(file_get_contents('/etc/serial')) : '';?>',
			device_id: '<?php echo empty(getProductKey()) ? "undefined" : getProductKey();?>'
		},
		success: function(data, status) {
			if (data.response.status != 'warning') {
				$('#last-backup').html(data.response.data.created_at.replace(/(\d{4})-(\d{2})-(\d{2})/, "$3/$2/$1"));
				$('#status-backup').show();
			}else{
				$('#last-backup').html('<?=gettext('No backup was found on the device')?>');
			}
		},
		beforeSend: function(xhr, settings) {
			xhr.setRequestHeader('Authorization','Bearer ' + token);
		},
		error: function(){
			$('#last-backup').html('<?=gettext('No backup was found on the device')?>');
		}
	});
	<?php
		$cpdb = array();
		if (isset($config['captiveportal'])) {
			if (!empty($config['captiveportal'])) {
				foreach ($config['captiveportal'] as $cpkey => $cp) {
					$cpzone = $cpkey;
					$cpdb = array_merge($cpdb, captiveportal_read_db());
				}
			}
		}
	?>

	function searchData() {
		var $rows = $('#table-access #geral-table tr');
		var val = $.trim($('#search-firewall-app').val()).replace(/ +/g, ' ').toLowerCase();

		$rows.show().filter(function() {
			var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
			return !~text.indexOf(val);
		}).hide();
	}

	function searchData1() {
		var $rows = $('#table-access #tls-table tr');
		var val = $.trim($('#search-firewall-app1').val()).replace(/ +/g, ' ').toLowerCase();

		$rows.show().filter(function() {
			var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
			return !~text.indexOf(val);
		}).hide();
	}

	function searchData2() {
		var $rows = $('#table-access #http-table tr');
		var val = $.trim($('#search-firewall-app2').val()).replace(/ +/g, ' ').toLowerCase();

		$rows.show().filter(function() {
			var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
			return !~text.indexOf(val);
		}).hide();
	}

	/*
	function get_virus_alerts_ajax() {
		var url = "/firewallapp/ajax_alerts_virus.php";
		var filter = $('#filterlogentries_sourceipaddress').val();

		$.ajax(
			url,
			{
				type: 'post',
				data: { 'ip': filter },
				dataType: 'html',
				success: function (data) {
					$('#table-virus').prepend(data);
					$('#table-virus > tr').each( function( key, value ) {
						if (key > 6)
							$(value).remove();
					});
					searchData1();
				},
			}
		);
	}
	*/
	
	function get_tls_alerts_ajax(interface) {
		var url = "/firewallapp/ajax_alerts_tls.php";
		var filter = $('#filterlogentries_sourceipaddress').val();

		$.ajax(
			url,
			{
				type: 'post',
				data: {
					ip: filter,
					interface: interface,
					hosts: <?=json_encode($cpdb)?>
				},
				dataType: 'html',
				success: function (data) {
					if (data.length > 0) {
						$("#tls-table").find("tr:gt(1)").remove();
						$('#tls-table').prepend(data);
						$('#tls-table > tr').each( function( key, value ) {
							if (key > 20)
								$(value).remove();
						});
						searchData1();
					} else {
						$("#tls-table").html('');
					}
				},
			}
		);
	}

	function get_http_alerts_ajax(interface) {
		var url = "/firewallapp/ajax_alerts_http.php";
		var filter = $('#filterlogentries_sourceipaddress').val();

		$.ajax(
			url,
			{
				type: 'post',
				data: {
					ip: filter,
					interface: interface,
					hosts: <?=json_encode($cpdb)?>
				},
				dataType: 'html',
				success: function (data) {
					if (data.length > 0) {
						$("#http-table").find("tr:gt(1)").remove();
						$('#http-table').prepend(data);
						$('#http-table > tr').each( function( key, value ) {
							if (key > 20)
								$(value).remove();
						});
						searchData2();
					} else {
						$("#http-table ").html('');
					}
				},
			}
		);
	}

	function get_geral_alerts_ajax(interface) {
		var url = "/firewallapp/ajax_alerts_geral.php";
		var filter = $('#filterlogentries_sourceipaddress').val();
		$.ajax(
			url,
			{
				type: 'post',
				data: {
					ip: filter,
					interface: interface,
					hosts: <?=json_encode($cpdb)?>
				},
				dataType: 'html',
				success: function (data) {
					if (data.length > 0) {
						$("#geral-table").find("tr:gt(1)").remove();
						$('#geral-table').prepend(data);
						$('#geral-table > tr').each( function( key, value ) {
							if (key > 20)
								$(value).remove();
						});
						searchData();
					} else {
						$("#geral-table").html('');
					}
				},
			}
		);
	}

	<?php if ("consumo_de_aplicacoes" == explode(".", explode("/", $_SERVER["REQUEST_URI"])[2])[0]): ?>

		$('#firewallapp_interface').change(function() {
			get_geral_alerts_ajax($('#firewallapp_interface').val());		
			get_tls_alerts_ajax($('#firewallapp_interface').val());		
			get_http_alerts_ajax($('#firewallapp_interface').val());		
		});

		<?php if (get_serial_status() == 'ok'): ?>
			setInterval(function() { get_geral_alerts_ajax($('#firewallapp_interface').val()) }, 2000);
			setInterval(function() { get_tls_alerts_ajax($('#firewallapp_interface').val()) }, 2000);
			setInterval(function() { get_http_alerts_ajax($('#firewallapp_interface').val()) }, 2000);
		<?php endif; ?>

	<?php endif; ?>

	//get_virus_alerts_ajax();

	<?php if (get_serial_status() != 'ok') { ?>
		$('#card-ameaca .p-3').css('background-color', 'rgba(0,0,0,0.8)');
		$('#card-ameaca div').css('z-index', '-1');
		$('#card-ameaca div h6').css('z-index', '-1');
		//get_virus_alerts_ajax();
	<?php } //else { ?>
		//setInterval(get_virus_alerts_ajax, 1000);
	<?php //} ?>

	/*
	function graph_virus() {
		$.ajax({
			type: 'GET',
			url: '/firewallapp/ajax_graphic_virus.php',
			dataType: 'json',
			success: function(response) {
				var ctx = document.getElementById("myChart");
				if(ctx){
					var myChart = new Chart(ctx, {
						type: 'doughnut',
						data: {
							labels: response.virus,
							datasets: [{
								label: '',
								data: response.percent,
								backgroundColor: response.color,
							}]
						},
						options: {
							legend: {
								display: false,
								labels: {
									fontColor: 'rgb(255, 99, 132)'
								}
							}
						}
					});
				}
			}
		});

	}

	graph_virus();
	*/

	<?php if (get_serial_status() != 'ok') { ?>
		localStorage.setItem("active-protection", "false");
	<?php } else { ?>
		<?php if ("consumo_de_aplicacoes" != explode(".", explode("/", $_SERVER["REQUEST_URI"])[2])[0]) { ?>
			/*setInterval(function() { get_geral_alerts_ajax($('#firewallapp_interface').val()) }, 5000);*/
			setInterval(function() { get_tls_alerts_ajax($('#firewallapp_interface1').val()) }, 5000);
			setInterval(function() { get_http_alerts_ajax($('#firewallapp_interface2').val()) }, 5000);
		<?php }
	} ?>

	if(localStorage.getItem("active-protection") == "false") {
		$('#card-ameaca .p-3').css('background-color', 'rgba(0,0,0,0.8)');
		$('#card-ameaca div').css('z-index', '-1');
		$('#card-ameaca div h6').css('z-index', '-1');
		//get_virus_alerts_ajax();
		//graph_virus();
	} //else if (localStorage.getItem("active-protection") == "true") {
		//setInterval(get_virus_alerts_ajax, 1000);
		//setInterval(graph_virus, 60000);
	//}

	let interface_ajax_consumo = {};

	function update_counter_access() {
		$.ajax({
			type: 'POST',
			data: interface_ajax_consumo,
			url: '/firewallapp/ajax_access_painel.php',
			dataType: 'json',
			success: function(response) {
				$('#access_all').html(response[0]);
				$('#access_drop').html(response[1]);
				$('#access_alerts').html(response[2]);
			}
		});
	}

	function update_desc_access_top() {

		$.ajax({
			type: 'POST',
			data: {list_top5_img:'true'},
			url: '/firewallapp/ajax_access_painel.php',
			dataType: 'json',
			success: function(response) {
				$('#list_top_val_top5_img_1').removeAttr("src").attr("src", response[0]);
				$('#list_top_val_top5_img_2').removeAttr("src").attr("src", response[1]);
				$('#list_top_val_top5_img_3').removeAttr("src").attr("src", response[2]);
				$('#list_top_val_top5_img_4').removeAttr("src").attr("src", response[3]);
				$('#list_top_val_top5_img_5').removeAttr("src").attr("src", response[4]);
			}
		});

		$.ajax({
			type: 'POST',
			data: {list_top5:'true'},
			url: '/firewallapp/ajax_access_painel.php',
			dataType: 'json',
			success: function(response) {
				$('#list_top_val_top5_text_1').html(response[0]);
				$('#list_top_val_top5_text_2').html(response[1]);
				$('#list_top_val_top5_text_3').html(response[2]);
				$('#list_top_val_top5_text_4').html(response[3]);
				$('#list_top_val_top5_text_5').html(response[4]);
			}
		});
		
	}

	function update_counter_access_top() {
		$.ajax({
			type: 'POST',
			data: {list_top_val_top5:'true'},
			url: '/firewallapp/ajax_access_painel.php',
			dataType: 'json',
			success: function(response) {
				$('#list_top_val_top5_counter_1').html("(" + response[0] + ")");
				$('#list_top_val_top5_counter_2').html("(" + response[1] + ")");
				$('#list_top_val_top5_counter_3').html("(" + response[2] + ")");
				$('#list_top_val_top5_counter_4').html("(" + response[3] + ")");
				$('#list_top_val_top5_counter_5').html("(" + response[4] + ")");
			}
		});
	}
	
	function graph_social_new() {
		$.ajax({
			type: 'POST',
			data: interface_ajax_consumo,
			url: '/firewallapp/ajax_graphic_social.php',
			dataType: 'json',
			success: function(response) {
				var facebook = typeof response[10] == 'undefined' ? 0 : response[10];
				var tiktok =  typeof response[146] == 'undefined' ? 0 : response[146];
				var twitter =  typeof response[16] == 'undefined' ? 0 : response[16];
				var instagram = typeof response[204] == 'undefined' ? 0 : response[204];
				var whatsapp = typeof response[4] == 'undefined' ? 0 : response[4];
				var telegram = typeof response[7] == 'undefined' ? 0 : response[7];
				var linkedin = typeof response[8] == 'undefined' ? 0 : response[8];
				var microsoft = typeof response[9] == 'undefined' ? 0 : response[9];//faltando
				var google = typeof response[20] == 'undefined' ? 0 : response[20];//faltando
				var amazon = typeof response[21] == 'undefined' ? 0 : response[21];//faltando
				var globo = typeof response[130] == 'undefined' ? 0 : response[130];
				var uol = typeof response[22] == 'undefined' ? 0 : response[22];//faltando
				var yahoo = typeof response[5] == 'undefined' ? 0 : response[5];
				var youtube = typeof response[11] == 'undefined' ? 0 : response[11];
				var primevideo = typeof response[23] == 'undefined' ? 0 : response[23];//faltando
				var netflix = typeof response[32] == 'undefined' ? 0 : response[32];
				var disney = typeof response[201] == 'undefined' ? 0 : response[201];
				var twitch = typeof response[24] == 'undefined' ? 0 : response[24];
				var spotify = typeof response[13] == 'undefined' ? 0 : response[13];
				var amazonmusic = typeof response[205] == 'undefined' ? 0 : response[205];
				var deezer = typeof response[25] == 'undefined' ? 0 : response[25];//faltando
				var teamviewer = typeof response[65] == 'undefined' ? 0 : response[65];
				var anydesk = typeof response[66] == 'undefined' ? 0 : response[66];
				var bittorrent = typeof response[70] == 'undefined' ? 0 : response[70];
				var utorrent = typeof response[71] == 'undefined' ? 0 : response[71];//faltando
				var pornhub = typeof response[26] == 'undefined' ? 0 : response[26];//faltando
				var xvideos = typeof response[27] == 'undefined' ? 0 : response[27];//faltando
				var outros = typeof response[1] == 'undefined' ? 0 : response[1];

				var redesocial = typeof response['redesocial'] == 'undefined' ? 0 : response['redesocial'];
				var portais = typeof response['portais'] == 'undefined' ? 0 : response['portais'];
				var stream = typeof response['stream'] == 'undefined' ? 0 : response['stream'];
				var musica = typeof response['musica'] == 'undefined' ? 0 : response['musica'];
				var remoto = typeof response['remoto'] == 'undefined' ? 0 : response['remoto'];
				var demais = typeof response['outros'] == 'undefined' ? 0 : response['outros'];	

				$('#access-facebook').html("<?=gettext("Access")?>: " + (typeof response["access_10"] == 'undefined' ? 0 : response["access_10"]));
				$('#access-tiktok').html("<?=gettext("Access")?>: " +  (typeof response["access_146"] == 'undefined' ? 0 : response["access_146"]));
				$('#access-twitter').html("<?=gettext("Access")?>: " +  (typeof response["access_16"] == 'undefined' ? 0 : response["access_16"]));
				$('#access-instagram').html("<?=gettext("Access")?>: " + (typeof response["access_204"] == 'undefined' ? 0 : response["access_204"]));
				$('#access-whatsapp').html("<?=gettext("Access")?>: " + (typeof response["access_4"] == 'undefined' ? 0 : response["access_4"]));
				$('#access-telegram').html("<?=gettext("Access")?>: " + (typeof response["access_7"] == 'undefined' ? 0 : response["access_7"]));
				$('#access-linkedin').html("<?=gettext("Access")?>: " + (typeof response["access_8"] == 'undefined' ? 0 : response["access_8"]));
				$('#access-microsoft').html("<?=gettext("Access")?>: " + (typeof response["access_9"] == 'undefined' ? 0 : response["access_9"]));//faltando
				$('#access-google').html("<?=gettext("Access")?>: " + (typeof response["access_20"] == 'undefined' ? 0 : response["access_20"]));//faltando
				$('#access-amazon').html("<?=gettext("Access")?>: " + (typeof response["access_21"] == 'undefined' ? 0 : response["access_21"]));//faltando
				$('#access-globo').html("<?=gettext("Access")?>: " + (typeof response["access_130"] == 'undefined' ? 0 : response["access_130"]));
				$('#access-uol').html("<?=gettext("Access")?>: " + (typeof response["access_22"] == 'undefined' ? 0 : response["access_22"]));//faltando
				$('#access-yahoo').html("<?=gettext("Access")?>: " + (typeof response["access_5"] == 'undefined' ? 0 : response["access_5"]));
				$('#access-youtube').html("<?=gettext("Access")?>: " + (typeof response["access_11"] == 'undefined' ? 0 : response["access_11"]));
				$('#access-primevideo').html("<?=gettext("Access")?>: " + (typeof response["access_23"] == 'undefined' ? 0 : response["access_23"]));//faltando
				$('#access-netflix').html("<?=gettext("Access")?>: " + (typeof response["access_32"] == 'undefined' ? 0 : response["access_32"]));
				$('#access-disney').html("<?=gettext("Access")?>: " + (typeof response["access_201"] == 'undefined' ? 0 : response["access_201"]));
				$('#access-twitch').html("<?=gettext("Access")?>: " + (typeof response["access_24"] == 'undefined' ? 0 : response["access_24"]));
				$('#access-spotify').html("<?=gettext("Access")?>: " + (typeof response["access_13"] == 'undefined' ? 0 : response["access_13"]));
				$('#access-amazonmusic').html("<?=gettext("Access")?>: " + (typeof response["access_205"] == 'undefined' ? 0 : response["access_205"]));
				$('#access-deezer').html("<?=gettext("Access")?>: " + (typeof response["access_25"] == 'undefined' ? 0 : response["access_25"]));//faltando
				$('#access-teamviewer').html("<?=gettext("Access")?>: " + (typeof response["access_65"] == 'undefined' ? 0 : response["access_65"]));
				$('#access-anydesk').html("<?=gettext("Access")?>: " + (typeof response["access_66"] == 'undefined' ? 0 : response["access_66"]));
				$('#access-bittorrent').html("<?=gettext("Access")?>: " + (typeof response["access_70"] == 'undefined' ? 0 : response["access_70"]));
				$('#access-utorrent').html("<?=gettext("Access")?>: " + (typeof response["access_71"] == 'undefined' ? 0 : response["access_71"]));//faltando
				$('#access-pornhub').html("<?=gettext("Access")?>: " + (typeof response["access_26"] == 'undefined' ? 0 : response["access_26"]));//faltando
				$('#access-xvideos').html("<?=gettext("Access")?>: " + (typeof response["access_27"] == 'undefined' ? 0 : response["access_27"]));//faltando
				$('#access-outros').html("<?=gettext("Access")?>: " + (typeof response["access_1"] == 'undefined' ? 0 : response["access_1"]));


				$('#redesocial-top').html(redesocial + "%");
				$('#portais-top').html(portais + "%");
				$('#stream-top').html(stream + "%");
				$('#musica-top').html(musica + "%");
				$('#remoto-top').html(remoto + "%");
				$('#outros-top').html(demais + "%");

				$('#facebook-graph').css('width', facebook + "%");
				$('#facebook-graph').html(facebook + "%");

				$('#tiktok-graph').css('width', tiktok + "%");
				$('#tiktok-graph').html(tiktok + "%");

				$('#twitter-graph').css('width', twitter + "%");
				$('#twitter-graph').html(twitter + "%");

				$('#instagram-graph').css('width', instagram + "%");
				$('#instagram-graph').html(instagram + "%");

				$('#whatsapp-graph').css('width', whatsapp + "%");
				$('#whatsapp-graph').html(whatsapp + "%");

				$('#telegram-graph').css('width', telegram + "%");
				$('#telegram-graph').html(telegram + "%");

				$('#linkedin-graph').css('width', linkedin + "%");
				$('#linkedin-graph').html(linkedin + "%");

				$('#microsoft-graph').css('width', microsoft + "%");
				$('#microsoft-graph').html(microsoft + "%");
				
				$('#google-graph').css('width', google + "%");
				$('#google-graph').html(google + "%");
				
				$('#amazon-graph').css('width', amazon + "%");
				$('#amazon-graph').html(amazon + "%");
				
				$('#globo-graph').css('width', globo + "%");
				$('#globo-graph').html(globo + "%");
				
				$('#uol-graph').css('width', uol + "%");
				$('#uol-graph').html(uol + "%");
				
				$('#yahoo-graph').css('width', yahoo + "%");
				$('#yahoo-graph').html(yahoo + "%");
				
				$('#youtube-graph').css('width', youtube + "%");
				$('#youtube-graph').html(youtube + "%");
				
				$('#primevideo-graph').css('width', primevideo + "%");
				$('#primevideo-graph').html(primevideo + "%");
				
				$('#netflix-graph').css('width', netflix + "%");
				$('#netflix-graph').html(netflix + "%");
				
				$('#disney-graph').css('width', disney + "%");
				$('#disney-graph').html(disney + "%");
				
				$('#twitch-graph').css('width', twitch + "%");
				$('#twitch-graph').html(twitch + "%");
				
				$('#spotify-graph').css('width', spotify + "%");
				$('#spotify-graph').html(spotify + "%");
				
				$('#amazonmusic-graph').css('width', amazonmusic + "%");
				$('#amazonmusic-graph').html(amazonmusic + "%");
				
				$('#deezer-graph').css('width', deezer + "%");
				$('#deezer-graph').html(deezer + "%");
				
				$('#teamviewer-graph').css('width', teamviewer + "%");
				$('#teamviewer-graph').html(teamviewer + "%");
				
				$('#anydesk-graph').css('width', anydesk + "%");
				$('#anydesk-graph').html(anydesk + "%");
				
				$('#bittorrent-graph').css('width', bittorrent + "%");
				$('#bittorrent-graph').html(bittorrent + "%");
				
				$('#utorrent-graph').css('width', utorrent + "%");
				$('#utorrent-graph').html(utorrent + "%");
				
				$('#pornhub-graph').css('width', pornhub + "%");
				$('#pornhub-graph').html(pornhub + "%");
				
				$('#xvideos-graph').css('width', xvideos + "%");
				$('#xvideos-graph').html(xvideos + "%");
				
				$('#outros-graph').css('width', outros + "%");
				$('#outros-graph').html(outros + "%");

			},
			error: function (data) {
				var facebook = 0;
				var tiktok = 0;
				var twitter = 0;
				var instagram = 0;
				var whatsapp = 0;
				var telegram = 0;
				var linkedin = 0;
				var microsoft = 0;//faltando
				var google = 0;//faltando
				var amazon = 0;//faltando
				var globo = 0;
				var uol = 0;//faltando
				var yahoo = 0;
				var youtube = 0;
				var primevideo = 0;
				var netflix = 0;
				var disney = 0;
				var twitch = 0;
				var spotify = 0;
				var amazonmusic = 0;
				var deezer = 0;
				var teamviewer = 0;
				var anydesk = 0;
				var bittorrent = 0;
				var utorrent = 0;
				var pornhub = 0;
				var xvideos = 0;
				var outros = 0;
				
				var redesocial = 0;
				var portais = 0;
				var stream = 0;
				var musica = 0;
				var remoto = 0;
				var demais = 0;

				$('#access-facebook').html("<?=gettext("Access")?>: 0");
				$('#access-tiktok').html("<?=gettext("Access")?>: 0");
				$('#access-twitter').html("<?=gettext("Access")?>: 0");
				$('#access-instagram').html("<?=gettext("Access")?>: 0");
				$('#access-whatsapp').html("<?=gettext("Access")?>: 0");
				$('#access-telegram').html("<?=gettext("Access")?>: 0");
				$('#access-linkedin').html("<?=gettext("Access")?>: 0");
				$('#access-microsoft').html("<?=gettext("Access")?>: 0");
				$('#access-google').html("<?=gettext("Access")?>: 0");
				$('#access-amazon').html("<?=gettext("Access")?>: 0");
				$('#access-globo').html("<?=gettext("Access")?>: 0");
				$('#access-uol').html("<?=gettext("Access")?>: 0");
				$('#access-yahoo').html("<?=gettext("Access")?>: 0");
				$('#access-youtube').html("<?=gettext("Access")?>: 0");
				$('#access-primevideo').html("<?=gettext("Access")?>: 0");
				$('#access-netflix').html("<?=gettext("Access")?>: 0");
				$('#access-disney').html("<?=gettext("Access")?>: 0");
				$('#access-twitch').html("<?=gettext("Access")?>: 0");
				$('#access-spotify').html("<?=gettext("Access")?>: 0");
				$('#access-amazonmusic').html("<?=gettext("Access")?>: 0");
				$('#access-deezer').html("<?=gettext("Access")?>: 0");
				$('#access-teamviewer').html("<?=gettext("Access")?>: 0");
				$('#access-anydesk').html("<?=gettext("Access")?>: 0");
				$('#access-bittorrent').html("<?=gettext("Access")?>: 0");
				$('#access-utorrent').html("<?=gettext("Access")?>: 0");
				$('#access-pornhub').html("<?=gettext("Access")?>: 0");
				$('#access-xvideos').html("<?=gettext("Access")?>: 0");
				$('#access-outros').html("<?=gettext("Access")?>: 0");

				$('#redesocial-top').html(redesocial + "%");
				$('#portais-top').html(portais + "%");
				$('#stream-top').html(stream + "%");
				$('#musica-top').html(musica + "%");
				$('#remoto-top').html(remoto + "%");
				$('#outros-top').html(demais + "%");

				$('#facebook-graph').css('width', facebook + "%");
				$('#facebook-graph').html(facebook + "%");

				$('#tiktok-graph').css('width', tiktok + "%");
				$('#tiktok-graph').html(tiktok + "%");

				$('#twitter-graph').css('width', twitter + "%");
				$('#twitter-graph').html(twitter + "%");

				$('#instagram-graph').css('width', instagram + "%");
				$('#instagram-graph').html(instagram + "%");

				$('#whatsapp-graph').css('width', whatsapp + "%");
				$('#whatsapp-graph').html(whatsapp + "%");

				$('#telegram-graph').css('width', telegram + "%");
				$('#telegram-graph').html(telegram + "%");

				$('#linkedin-graph').css('width', linkedin + "%");
				$('#linkedin-graph').html(linkedin + "%");

				$('#microsoft-graph').css('width', microsoft + "%");
				$('#microsoft-graph').html(microsoft + "%");
				
				$('#google-graph').css('width', google + "%");
				$('#google-graph').html(google + "%");
				
				$('#amazon-graph').css('width', amazon + "%");
				$('#amazon-graph').html(amazon + "%");
				
				$('#globo-graph').css('width', globo + "%");
				$('#globo-graph').html(globo + "%");
				
				$('#uol-graph').css('width', uol + "%");
				$('#uol-graph').html(uol + "%");
				
				$('#yahoo-graph').css('width', yahoo + "%");
				$('#yahoo-graph').html(yahoo + "%");
				
				$('#youtube-graph').css('width', youtube + "%");
				$('#youtube-graph').html(youtube + "%");
				
				$('#primevideo-graph').css('width', primevideo + "%");
				$('#primevideo-graph').html(primevideo + "%");
				
				$('#netflix-graph').css('width', netflix + "%");
				$('#netflix-graph').html(netflix + "%");
				
				$('#disney-graph').css('width', disney + "%");
				$('#disney-graph').html(disney + "%");
				
				$('#twitch-graph').css('width', twitch + "%");
				$('#twitch-graph').html(twitch + "%");
				
				$('#spotify-graph').css('width', spotify + "%");
				$('#spotify-graph').html(spotify + "%");
				
				$('#amazonmusic-graph').css('width', amazonmusic + "%");
				$('#amazonmusic-graph').html(amazonmusic + "%");
				
				$('#deezer-graph').css('width', deezer + "%");
				$('#deezer-graph').html(deezer + "%");
				
				$('#teamviewer-graph').css('width', teamviewer + "%");
				$('#teamviewer-graph').html(teamviewer + "%");
				
				$('#anydesk-graph').css('width', anydesk + "%");
				$('#anydesk-graph').html(anydesk + "%");
				
				$('#bittorrent-graph').css('width', bittorrent + "%");
				$('#bittorrent-graph').html(bittorrent + "%");
				
				$('#utorrent-graph').css('width', utorrent + "%");
				$('#utorrent-graph').html(utorrent + "%");
				
				$('#pornhub-graph').css('width', pornhub + "%");
				$('#pornhub-graph').html(pornhub + "%");
				
				$('#xvideos-graph').css('width', xvideos + "%");
				$('#xvideos-graph').html(xvideos + "%");
				
				$('#outros-graph').css('width', outros + "%");
				$('#outros-graph').html(outros + "%");    		
			}
		});

	}
		

	function status_page_qos() {
		$.ajax({
			type: 'POST',
			data: 'gerarStatus',
			url: '/firewallapp/ajax_qos_consume_show.php',
			dataType: 'json',
			success: function(response) {

				if (response['qos_instagram'] == "True") {
					document.getElementById("qos_instagram").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_instagram").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_instagram").removeAttribute("class");
					document.getElementById("qos_instagram").removeAttribute("title");
				}
				if (response['qos_whatsapp'] == "True") {
					document.getElementById("qos_whatsapp").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_whatsapp").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_whatsapp").removeAttribute("class");
					document.getElementById("qos_whatsapp").removeAttribute("title");
				}
				if (response['qos_facebook'] == "True") {
					document.getElementById("qos_facebook").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_facebook").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_facebook").removeAttribute("class");
					document.getElementById("qos_facebook").removeAttribute("title");
				}
				if (response['qos_twitter'] == "True") {
					document.getElementById("qos_twitter").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_twitter").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_twitter").removeAttribute("class");
					document.getElementById("qos_twitter").removeAttribute("title");
				}
				if (response['qos_tiktok'] == "True") {
					document.getElementById("qos_tiktok").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_tiktok").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_tiktok").removeAttribute("class");
					document.getElementById("qos_tiktok").removeAttribute("title");
				}
				if (response['qos_news_microsoft'] == "True") {
					document.getElementById("qos_news_microsoft").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_news_microsoft").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_news_microsoft").removeAttribute("class");
					document.getElementById("qos_news_microsoft").removeAttribute("title");
				}
				if (response['qos_uol'] == "True") {
					document.getElementById("qos_uol").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_uol").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_uol").removeAttribute("class");
					document.getElementById("qos_uol").removeAttribute("title");
				}
				if (response['qos_google'] == "True") {
					document.getElementById("qos_google").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_google").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_google").removeAttribute("class");
					document.getElementById("qos_google").removeAttribute("title");
				}
				if (response['qos_primevideo'] == "True") {
					document.getElementById("qos_primevideo").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_primevideo").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_primevideo").removeAttribute("class");
					document.getElementById("qos_primevideo").removeAttribute("title");
				}
				if (response['qos_youtube'] == "True") {
					document.getElementById("qos_youtube").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_youtube").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_youtube").removeAttribute("class");
					document.getElementById("qos_youtube").removeAttribute("title");
				}
				if (response['qos_deezer'] == "True") {
					document.getElementById("qos_deezer").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_deezer").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_deezer").removeAttribute("class");
					document.getElementById("qos_deezer").removeAttribute("title");
				}
				if (response['qos_telegram'] == "True") {
					document.getElementById("qos_telegram").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_telegram").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_telegram").removeAttribute("class");
					document.getElementById("qos_telegram").removeAttribute("title");
				}
				if (response['qos_spotify'] == "True") {
					document.getElementById("qos_spotify").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_spotify").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_spotify").removeAttribute("class");
					document.getElementById("qos_spotify").removeAttribute("title");
				}
				if (response['qos_teamviewer'] == "True") {
					document.getElementById("qos_teamviewer").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_teamviewer").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_teamviewer").removeAttribute("class");
					document.getElementById("qos_teamviewer").removeAttribute("title");
				}
				if (response['qos_twitch'] == "True") {
					document.getElementById("qos_twitch").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_twitch").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_twitch").removeAttribute("class");
					document.getElementById("qos_twitch").removeAttribute("title");
				}
				if (response['qos_anydesk'] == "True") {
					document.getElementById("qos_anydesk").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_anydesk").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_anydesk").removeAttribute("class");
					document.getElementById("qos_anydesk").removeAttribute("title");
				}
				if (response['qos_linkedin'] == "True") {
					document.getElementById("qos_linkedin").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_linkedin").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_linkedin").removeAttribute("class");
					document.getElementById("qos_linkedin").removeAttribute("title");
				}
				if (response['qos_netflix'] == "True") {
					document.getElementById("qos_netflix").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_netflix").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_netflix").removeAttribute("class");
					document.getElementById("qos_netflix").removeAttribute("title");
				}
				if (response['qos_music_amazon'] == "True") {
					document.getElementById("qos_music_amazon").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_music_amazon").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_music_amazon").removeAttribute("class");
					document.getElementById("qos_music_amazon").removeAttribute("title");
				}
				if (response['qos_yahoo'] == "True") {
					document.getElementById("qos_yahoo").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_yahoo").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_yahoo").removeAttribute("class");
					document.getElementById("qos_yahoo").removeAttribute("title");
				}
				if (response['qos_disneyplus'] == "True") {
					document.getElementById("qos_disneyplus").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_disneyplus").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_disneyplus").removeAttribute("class");
					document.getElementById("qos_disneyplus").removeAttribute("title");
				}
				if (response['qos_sites'] == "True") {
					document.getElementById("qos_sites").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_sites").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_sites").removeAttribute("class");
					document.getElementById("qos_sites").removeAttribute("title");
				}
				if (response['qos_g1'] == "True") {
					document.getElementById("qos_g1").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_g1").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_g1").removeAttribute("class");
					document.getElementById("qos_g1").removeAttribute("title");
				}
				if (response['qos_amazon'] == "True") {
					document.getElementById("qos_amazon").setAttribute("class", "fa fa-check-circle text-primary icon-primary");
					document.getElementById("qos_amazon").setAttribute("title", "Service with QoS traffic");
				} else {
					document.getElementById("qos_amazon").removeAttribute("class");
					document.getElementById("qos_amazon").removeAttribute("title");
				}
			},
			error: function (data) {
				
				document.getElementById("qos_instagram").removeAttribute("class");
				document.getElementById("qos_whatsapp").removeAttribute("class");
				document.getElementById("qos_facebook").removeAttribute("class");
				document.getElementById("qos_twitter").removeAttribute("class");
				document.getElementById("qos_tiktok").removeAttribute("class");
				document.getElementById("qos_news_microsoft").removeAttribute("class");
				document.getElementById("qos_uol").removeAttribute("class");
				document.getElementById("qos_google").removeAttribute("class");
				document.getElementById("qos_primevideo").removeAttribute("class");
				document.getElementById("qos_youtube").removeAttribute("class");
				document.getElementById("qos_deezer").removeAttribute("class");
				document.getElementById("qos_telegram").removeAttribute("class");
				document.getElementById("qos_spotify").removeAttribute("class");
				document.getElementById("qos_teamviewer").removeAttribute("class");
				document.getElementById("qos_twitch").removeAttribute("class");
				document.getElementById("qos_anydesk").removeAttribute("class");
				document.getElementById("qos_linkedin").removeAttribute("class");
				document.getElementById("qos_netflix").removeAttribute("class");
				document.getElementById("qos_music_amazon").removeAttribute("class");
				document.getElementById("qos_yahoo").removeAttribute("class");
				document.getElementById("qos_disneyplus").removeAttribute("class");
				document.getElementById("qos_sites").removeAttribute("class");
				document.getElementById("qos_g1").removeAttribute("class");
				document.getElementById("qos_amazon").removeAttribute("class");
				document.getElementById("qos_instagram").removeAttribute("title");
				document.getElementById("qos_whatsapp").removeAttribute("title");
				document.getElementById("qos_facebook").removeAttribute("title");
				document.getElementById("qos_twitter").removeAttribute("title");
				document.getElementById("qos_tiktok").removeAttribute("title");
				document.getElementById("qos_news_microsoft").removeAttribute("title");
				document.getElementById("qos_uol").removeAttribute("title");
				document.getElementById("qos_google").removeAttribute("title");
				document.getElementById("qos_primevideo").removeAttribute("title");
				document.getElementById("qos_youtube").removeAttribute("title");
				document.getElementById("qos_deezer").removeAttribute("title");
				document.getElementById("qos_telegram").removeAttribute("title");
				document.getElementById("qos_spotify").removeAttribute("title");
				document.getElementById("qos_teamviewer").removeAttribute("title");
				document.getElementById("qos_twitch").removeAttribute("title");
				document.getElementById("qos_anydesk").removeAttribute("title");
				document.getElementById("qos_linkedin").removeAttribute("title");
				document.getElementById("qos_netflix").removeAttribute("title");
				document.getElementById("qos_music_amazon").removeAttribute("title");
				document.getElementById("qos_yahoo").removeAttribute("title");
				document.getElementById("qos_disneyplus").removeAttribute("title");
				document.getElementById("qos_sites").removeAttribute("title");
				document.getElementById("qos_g1").removeAttribute("title");
				document.getElementById("qos_amazon").removeAttribute("title");

			}
		
		});
	}
	
	function status_page_code() {
		$.ajax({
			type: 'POST',
			data: 'gerarStatus',
			url: '/firewallapp/ajax_status_source.php',
			dataType: 'json',
			success: function(response) {
				document.getElementById("www.instagram.com").removeAttribute("class");
				document.getElementById("www.whatsapp.com").removeAttribute("class");
				document.getElementById("www.facebook.com").removeAttribute("class");
				document.getElementById("www.twitter.com").removeAttribute("class");
				document.getElementById("www.tiktok.com").removeAttribute("class");
				document.getElementById("news.microsoft.com").removeAttribute("class");
				document.getElementById("www.uol.com").removeAttribute("class");
				document.getElementById("www.google.com").removeAttribute("class");
				document.getElementById("www.primevideo.com").removeAttribute("class");
				document.getElementById("www.youtube.com").removeAttribute("class");
				document.getElementById("www.deezer.com").removeAttribute("class");
				document.getElementById("www.telegram.com").removeAttribute("class");
				document.getElementById("www.spotify.com").removeAttribute("class");
				document.getElementById("www.teamviewer.com").removeAttribute("class");
				document.getElementById("www.twitch.tv").removeAttribute("class");
				document.getElementById("www.anydesk.com").removeAttribute("class");
				document.getElementById("www.linkedin.com").removeAttribute("class");
				document.getElementById("www.netflix.com").removeAttribute("class");
				document.getElementById("music.amazon.com").removeAttribute("class");
				document.getElementById("www.yahoo.com").removeAttribute("class");
				document.getElementById("www.disneyplus.com").removeAttribute("class");
				document.getElementById("www.teste.com").removeAttribute("class");
				document.getElementById("www.g1.com").removeAttribute("class");
				document.getElementById("www.amazon.com").removeAttribute("class");


				if (response['www.instagram.com'] == "True") {
					document.getElementById("www.instagram.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.instagram.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.instagram.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.instagram.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.whatsapp.com'] == "True") {
					document.getElementById("www.whatsapp.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.whatsapp.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.whatsapp.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.whatsapp.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.facebook.com'] == "True") {
					document.getElementById("www.facebook.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.facebook.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.facebook.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.facebook.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.twitter.com'] == "True") {
					document.getElementById("www.twitter.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.twitter.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.twitter.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.twitter.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.tiktok.com'] == "True") {
					document.getElementById("www.tiktok.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.tiktok.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.tiktok.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.tiktok.com").setAttribute("title", "Sem resposta");
				}
				
				if (response['news.microsoft.com'] == "True") {
					document.getElementById("news.microsoft.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("news.microsoft.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("news.microsoft.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("news.microsoft.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.uol.com'] == "True") {
					document.getElementById("www.uol.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.uol.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.uol.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.uol.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.google.com'] == "True") {
					document.getElementById("www.google.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.google.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.google.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.google.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.primevideo.com'] == "True") {
					document.getElementById("www.primevideo.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.primevideo.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.primevideo.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.primevideo.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.youtube.com'] == "True") {
					document.getElementById("www.youtube.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.youtube.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.youtube.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.youtube.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.deezer.com'] == "True") {
					document.getElementById("www.deezer.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.deezer.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.deezer.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.deezer.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.telegram.com'] == "True") {
					document.getElementById("www.telegram.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.telegram.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.telegram.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.telegram.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.spotify.com'] == "True") {
					document.getElementById("www.spotify.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.spotify.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.spotify.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.spotify.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.teamviewer.com'] == "True") {
					document.getElementById("www.teamviewer.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.teamviewer.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.teamviewer.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.teamviewer.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.twitch.tv'] == "True") {
					document.getElementById("www.twitch.tv").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.twitch.tv").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.twitch.tv").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.twitch.tv").setAttribute("title", "Sem resposta");
				}

				if (response['www.anydesk.com'] == "True") {
					document.getElementById("www.anydesk.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.anydesk.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.anydesk.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.anydesk.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.linkedin.com'] == "True") {
					document.getElementById("www.linkedin.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.linkedin.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.linkedin.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.linkedin.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.netflix.com'] == "True") {
					document.getElementById("www.netflix.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.netflix.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.netflix.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.netflix.com").setAttribute("title", "Sem resposta");
				}

				if (response['music.amazon.com'] == "True") {
					document.getElementById("music.amazon.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("music.amazon.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("music.amazon.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("music.amazon.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.amazon.com'] == "True") {
					document.getElementById("www.amazon.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.amazon.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.amazon.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.amazon.com").setAttribute("title", "Sem resposta");
				}
				
				if (response['www.yahoo.com'] == "True") {
					document.getElementById("www.yahoo.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.yahoo.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.yahoo.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.yahoo.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.disneyplus.com'] == "True") {
					document.getElementById("www.disneyplus.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.disneyplus.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.disneyplus.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.disneyplus.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.teste.com'] == "True") {
					document.getElementById("www.teste.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.teste.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.teste.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.teste.com").setAttribute("title", "Sem resposta");
				}

				if (response['www.g1.com'] == "True") {
					document.getElementById("www.g1.com").setAttribute("class", "fa fa-check-circle text-success icon-primary");
					document.getElementById("www.g1.com").setAttribute("title", "Respondendo");
				} else {
					document.getElementById("www.g1.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
					document.getElementById("www.g1.com").setAttribute("title", "Sem resposta");
				}


			},
			error: function (data) {
				
				document.getElementById("www.instagram.com").removeAttribute("class");
				document.getElementById("www.whatsapp.com").removeAttribute("class");
				document.getElementById("www.facebook.com").removeAttribute("class");
				document.getElementById("www.twitter.com").removeAttribute("class");
				document.getElementById("www.tiktok.com").removeAttribute("class");
				document.getElementById("news.microsoft.com").removeAttribute("class");
				document.getElementById("www.uol.com").removeAttribute("class");
				document.getElementById("www.google.com").removeAttribute("class");
				document.getElementById("www.primevideo.com").removeAttribute("class");
				document.getElementById("www.youtube.com").removeAttribute("class");
				document.getElementById("www.deezer.com").removeAttribute("class");
				document.getElementById("www.telegram.com").removeAttribute("class");
				document.getElementById("www.spotify.com").removeAttribute("class");
				document.getElementById("www.teamviewer.com").removeAttribute("class");
				document.getElementById("www.twitch.tv").removeAttribute("class");
				document.getElementById("www.anydesk.com").removeAttribute("class");
				document.getElementById("www.linkedin.com").removeAttribute("class");
				document.getElementById("www.netflix.com").removeAttribute("class");
				document.getElementById("music.amazon.com").removeAttribute("class");
				document.getElementById("www.yahoo.com").removeAttribute("class");
				document.getElementById("www.disneyplus.com").removeAttribute("class");
				document.getElementById("www.teste.com").removeAttribute("class");
				document.getElementById("www.g1.com").removeAttribute("class");
				document.getElementById("www.amazon.com").removeAttribute("class");


				document.getElementById("www.amazon.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.amazon.com").setAttribute("title", "Sem resposta");

				document.getElementById("www.instagram.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.instagram.com").setAttribute("title", "Sem resposta");

				document.getElementById("www.whatsapp.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.whatsapp.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.facebook.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.facebook.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.twitter.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.twitter.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.tiktok.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.tiktok.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("news.microsoft.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("news.microsoft.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.uol.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.uol.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.google.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.google.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.primevideo.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.primevideo.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.youtube.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.youtube.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.deezer.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.deezer.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.telegram.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.telegram.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.spotify.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.spotify.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.teamviewer.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.teamviewer.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.twitch.tv").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.twitch.tv").setAttribute("title", "Sem resposta");

				document.getElementById("www.anydesk.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.anydesk.com").setAttribute("title", "Sem resposta");

				document.getElementById("www.linkedin.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.linkedin.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.netflix.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.netflix.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("music.amazon.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("music.amazon.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.yahoo.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.yahoo.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.disneyplus.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.disneyplus.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.teste.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.teste.com").setAttribute("title", "Sem resposta");
				
				document.getElementById("www.g1.com").setAttribute("class", "fa fa-times-circle text-danger icon-primary");
				document.getElementById("www.g1.com").setAttribute("title", "Sem resposta");
				
			}
		
		});
	}

	function set_variable_interface(interface_id) {
		all_interfaces = document.getElementsByClassName("btn weight-600 interface_target");
		for(counter_now = 0; counter_now <= all_interfaces.length-1; counter_now++) {
			all_interfaces[counter_now].classList.remove("btn-success");
			all_interfaces[counter_now].classList.add("btn-primary");
		}
		interface_ajax_consumo = {
			interface_alvo: interface_id  
		};
		document.getElementById("interface-btn-"+interface_id).classList.remove("btn-primary");
		document.getElementById("interface-btn-"+interface_id).classList.add("btn-success");
		graph_social_new();
	}

	//graph_social();
	if ("<?=$_SERVER['REQUEST_URI']?>" == "/firewallapp/consumo_de_aplicacoes.php") {
		all_interfaces = document.getElementsByClassName("btn weight-600 interface_target");
		for(counter_now = 0; counter_now <= all_interfaces.length-1; counter_now++) {
			if (!(all_interfaces[counter_now].hasAttribute("disabled"))) {
				interface_ajax_consumo = {
					interface_alvo: counter_now+1
				};
				all_interfaces[counter_now].classList.remove("btn-primary");
				all_interfaces[counter_now].classList.add("btn-success");
				all_interfaces[counter_now].click();
				graph_social_new();
				break;
			}
		}
		//graph_top_consumo();
		graph_social_new();
		status_page_code();
		status_page_qos();
		update_counter_access();
		update_desc_access_top();
		update_counter_access_top();
		//graph_top_category();
	}

	<?php if (get_serial_status() == 'ok') { ?>
		//setInterval(graph_social, 1000);

		if ("<?=$_SERVER['REQUEST_URI']?>" == "/firewallapp/consumo_de_aplicacoes.php") {
			setInterval(graph_social_new, 5000);
			setInterval(status_page_code, 30000);
			setInterval(status_page_qos, 30000);
			setInterval(update_counter_access, 5000);
			setInterval(update_desc_access_top, 5000);
			setInterval(update_counter_access_top, 5000);
			//setInterval(graph_top_category, 10000);
		}

	<?php } ?>

	<?php } // End IF index and active_protection pages ?>

	

	$('#menu-vert2 li').click(function(event) {
		event.stopPropagation();

		var li = $(this);

		if (li.children("ul").length > 0) {
			if (li.hasClass("active")) {
				li.removeClass("active");
				li.find("li.active").removeClass("active");
			} else {
				li.addClass("active");
			}
		}
		let elem = this;
		setTimeout(() => {
			let heightOffsetSubMenu = elem.offsetTop + 5;
			let topMenu = document.querySelector("#wrapper > nav");
			let leftMenu = document.querySelector("div.container-fluid > div > nav.sidebar");
			let screenUtilHeight = leftMenu.offsetHeight - topMenu.offsetHeight;
			let subMenu = elem.getElementsByTagName('ul')[0];

			if((subMenu.offsetHeight + heightOffsetSubMenu) > screenUtilHeight ){
				subMenu.style.top = screenUtilHeight - subMenu.offsetHeight+'px';
			}else{
				subMenu.style.top = heightOffsetSubMenu+'px';
			}
		}, 50);
	})

	$('#menu-vert2 li a').click(function(event) {
		var li = $(this).parent('li');
		var ul = li.parent("ul");

		ul.find(" > li").not(li).removeClass("active");
	});

	$('html').click(function() {
		$("#menu-vert2 li").removeClass('active');
	});

	function get_gw_stats() {
		var ajaxRequest;
		ajaxRequest = $.ajax({
			url: "/widgets/widgets/gateways.widget.php",
			type: "post",
			data: { ajax: "ajax"}
		});

		// Deal with the results of the above ajax call
		ajaxRequest.done(function (response, textStatus, jqXHR) {
			$('#gwtblbody').html(response);
			// and do it again
			setTimeout(get_gw_stats, "5000");
		});
	}

	$("#save_type").click(function() {
		if ($('#save_type').attr('data-done') == 'true') {
			$('#modal_type').modal('hide');
			$.ajax({
				url: '../ajax_update_rules_fapp.php',
				method: 'POST',
				data: { 
					"change_type": "true",
					"p_type": $("input[name='customRadioInline']:checked").val()
				},
				success: function(data) {
					$('#modal_update_rules_fapp .txt_modal_update_rules').text("<?=gettext('Rules updated successfully!')?>");
					$('#modal_update_rules_fapp #loader_modal_update_rules').attr('style', 'width:100px;height:auto');
					$('#modal_update_rules_fapp #loader_modal_update_rules').attr('src', '../images/update_rules_ok.png');
					setTimeout(function() {$('#modal_update_rules_fapp').modal('hide');}, 1500);
					$('#save_type').attr('data-done', 'false');
					setTimeout(function() {window.location.href = "./services.php"}, 1500);
				},
				beforeSend: function(xhr, settings) {
					$('#modal_update_rules_fapp .txt_modal_update_rules').text("<?=gettext('Wait, updating Firewallapp rules...')?>");
					$('#modal_update_rules_fapp #loader_modal_update_rules').attr('style', 'width:200px;height:200px');
					$('#modal_update_rules_fapp #loader_modal_update_rules').attr('src', '../images/spinner.gif');
					$('#modal_update_rules_fapp').modal('show');
				},
				error: function() {
					setTimeout(function() {$('#modal_update_rules_fapp').modal('hide');}, 1500);
					$('#save_type').attr('data-done', 'false');
				}
			});
		}
	});

	$("#enable_acp_force").click(function() {
		$('#enable_acp_force').hide();
		$('#modal_inter_fapp').hide();
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext("Wait, Activating Active Protection")?>");
		$('#modal_ativa #loader_modal_ativa').attr('style', 'width:200px;height:200px');
		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/spinner.gif');
		$('#modal_ativa').modal('show');
	});

	$(".cpack_btn").click(function() {
		$('#modal_ativa .txt_modal_ativa').text($(this).attr('name'));
		$('#modal_ativa #loader_modal_ativa').attr('style', 'width:200px;height:200px');
		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/spinner.gif');
		$('#modal_ativa').modal('show');
	});

	let enable_show_modal = 57000;
	let enable_hide_modal = 59000;
	let enable_href_modal = 60000;

	$("#click_ativa").click(function() {
		$.ajax({
			url: '../ajax_update_rules_fapp.php',
			method: 'POST',
			data: {
				"change_type": "false"
			},
			success: function(data) {
				setTimeout(function() {
					$('#modal_ativa .txt_modal_ativa').text("<?=gettext('FirewallApp Enabled!')?>");
					$('#modal_ativa #loader_modal_ativa').attr('style', 'width:100px;height:auto');
					$('#modal_ativa #loader_modal_ativa').attr('src', '../images/update_rules_ok.png');	
				}, enable_show_modal);
				setTimeout(function() {
					$('#modal_ativa #loader_modal_ativa').modal('hide');	
				}, enable_hide_modal);
				setTimeout(function() {window.location.href = "./services.php"}, enable_href_modal);
			},
			beforeSend: function(xhr, settings) {
				$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Wait, Activating FirewallApp')?>");
				$('#modal_ativa #loader_modal_ativa').attr('style', 'width:200px;height:200px');
				$('#modal_ativa #loader_modal_ativa').attr('src', '../images/spinner.gif');
				$('#modal_ativa').modal('show');
			},
		});
	});

	$("#click_ativa3").click(function() {
		$.ajax({
			url: '../ajax_update_rules_fapp.php',
			method: 'POST',
			data: {
				"change_type": "false"
			},
			success: function(data) {
				setTimeout(function() {
					$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext('Active Protection Enabled!')?>");
					$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
					$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/update_rules_ok.png');
				}, enable_show_modal);
				setTimeout(function() {
					$('#modal_ativa3 #loader_modal_ativa3').modal('hide');	
				}, enable_hide_modal);
				setTimeout(function() {window.location.href = "./ap_services.php"}, enable_href_modal);
			},
			beforeSend: function(xhr, settings) {
				$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext('Wait, Activating Active Protection')?>");
				$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:200px;height:200px');
				$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
				$('#modal_ativa3').modal('show');
			},
		});
	});

	$("#click_updatepack").click(function() {
		$.ajax({
			url: './ajax_update_pack.php',
			method: 'POST',
			data: {
				"change_type": "false"
			},
			success: function(data) {
				setTimeout(function() {
					$('#updatepack .txt_updatepack').text("<?=gettext('Updated version!')?>");
					$('#updatepack #loader_updatepack').attr('style', 'width:100px;height:auto');
					$('#updatepack #loader_updatepack').attr('src', '../images/update_rules_ok.png');
				}, enable_show_modal);
				setTimeout(function() {
					$('#updatepack').modal('hide');	
				}, enable_hide_modal);
				setTimeout(function() {window.location.href = "./index.php?" + Math.random();}, enable_href_modal);
			},
			beforeSend: function(xhr, settings) {
				$('#updatepack .txt_updatepack').text("<?=gettext('Wait, Updating the Pack')?>");
				$('#updatepack #loader_updatepack').attr('style', 'width:200px;height:200px');
				$('#updatepack #loader_updatepack').attr('src', '../images/spinner.gif');
				$('#updatepack').modal('show');
			}
		});
	});

	$("i[name='click_ativa']").click(function() {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Wait, Activating FirewallApp')?>");
		$('#modal_ativa #loader_modal_ativa').attr('style', 'width:200px;height:200px');
		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/spinner.gif');
		$('#modal_ativa').modal('show');
	});

	$("i[name='click_ativa3']").click(function() {
		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext('Wait, Activating Active Protection')?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:200px;height:200px');
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
		$('#modal_ativa3').modal('show');
	});

	$("i[name='click_disabled']").click(function() {
		$('#modal_ativa .txt_modal_ativa').text("<?=gettext('Wait, Disabled service FirewallAPP in interface')?>");
		$('#modal_ativa #loader_modal_ativa').attr('style', 'width:200px;height:200px');
		$('#modal_ativa #loader_modal_ativa').attr('src', '../images/spinner.gif');
		$('#modal_ativa').modal('show');
	});

	$("i[name='click_disabled3']").click(function() {
		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext('Wait, Disabled service Active Protection in interface')?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:200px;height:200px');
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
		$('#modal_ativa3').modal('show');
	});

	$("#click_enable").click(function() {
		$.ajax({
			url: './services.php',
			method: 'POST',
			data: {
				"change_type": "false"
			},
			success: function(data) {
				setTimeout(function() {
					$('#modal_enable .txt_modal_enable').text("<?=gettext('FirewallApp Enabled!')?>");
					$('#modal_enable #loader_modal_enable').attr('style', 'width:100px;height:auto');
					$('#modal_enable #loader_modal_enable').attr('src', '../images/update_rules_ok.png');
				}, enable_show_modal);
				setTimeout(function() {
					$('#modal_enable').modal('hide');
				}, enable_hide_modal);
				setTimeout(function() {window.location.href = "./services.php"}, enable_href_modal);
			},
			beforeSend: function(xhr, settings) {
				$('#modal_enable .txt_modal_enable').text("<?=gettext('Wait, Activating FirewallApp')?>");
				$('#modal_enable #loader_modal_enable').attr('style', 'width:200px;height:200px');
				$('#modal_enable #loader_modal_enable').attr('src', '../images/spinner.gif');
				$('#modal_enable').modal('show');
			}
		});
	});

	$("#click_enable3").click(function() {
		$.ajax({
			url: './ac_services.php',
			method: 'POST',
			data: {
				"change_type": "false"
			},
			success: function(data) {
				setTimeout(function() {
					$('#modal_enable3 .txt_modal_enable3').text("<?=gettext('Active Protection Enabled!')?>");
					$('#modal_enable3 #loader_modal_enable3').attr('style', 'width:100px;height:auto');
					$('#modal_enable3 #loader_modal_enable3').attr('src', '../images/update_rules_ok.png');
				}, enable_show_modal);
				setTimeout(function() {
					$('#modal_enable3').modal('hide');
				}, enable_hide_modal);
				setTimeout(function() {window.location.href = "./ac_services.php"}, enable_href_modal);
			},
			beforeSend: function(xhr, settings) {
				$('#modal_enable3 .txt_modal_enable3').text("<?=gettext('Wait, Activating Active Protection')?>");
				$('#modal_enable3 #loader_modal_enable3').attr('style', 'width:200px;height:200px');
				$('#modal_enable3 #loader_modal_enable3').attr('src', '../images/spinner.gif');
				$('#modal_enable3').modal('show');
			}
		});
	});

	$("#ip_enable").click(function() {
		$.ajax({
			url: './ap_services.php',
			method: 'POST',
			data: {
				"change_type": "false"
			},
			success: function(data) {
				setTimeout(function() {
					$('#modal_enable2 .txt_modal_enable2').text("<?=gettext('IP Reputation Enabled!')?>");
					$('#modal_enable2 #loader_modal_enable2').attr('style', 'width:100px;height:auto');
					$('#modal_enable2 #loader_modal_enable2').attr('src', '../images/update_rules_ok.png');
				}, enable_show_modal);
				setTimeout(function() {
					$('#modal_enable2').modal('hide');
				}, enable_hide_modal);
				setTimeout(function() {window.location.href = "./ap_services.php"}, enable_href_modal);
			},
			beforeSend: function(xhr, settings) {
				$('#modal_enable2 .txt_modal_enable2').text("<?=gettext('Wait, Enabling IP Reputation')?>");
				$('#modal_enable2 #loader_modal_enable2').attr('style', 'width:200px;height:200px');
				$('#modal_enable2 #loader_modal_enable2').attr('src', '../images/spinner.gif');
				$('#modal_enable2').modal('show');
			}
		});
	});

	let disabled_show_modal = 12000;
	let disabled_hide_modal = 14000;
	let disabled_href_modal = 15000;

	$("#click_disable").click(function() {
		$.ajax({
			url: './services.php',
			method: 'POST',
			data: {
				"change_type": "false"
			},
			success: function(data) {
				setTimeout(function() {
					$('#modal_disable .txt_modal_disable').text("<?=gettext('FirewallApp Disabled!')?>");
					$('#modal_disable #loader_modal_disable').attr('style', 'width:100px;height:auto');
					$('#modal_disable #loader_modal_disable').attr('src', '../images/update_rules_ok.png');
				}, disabled_show_modal);
				setTimeout(function() {
					$('#modal_enable').modal('hide');
				}, disabled_hide_modal);
				setTimeout(function() {window.location.href = "./services.php"}, disabled_href_modal);
			},
			beforeSend: function(xhr, settings) {
				$('#modal_disable .txt_modal_disable').text("<?=gettext('Wait, Disabling FirewallApp')?>");
				$('#modal_disable #loader_modal_disable').attr('style', 'width:200px;height:200px');
				$('#modal_disable #loader_modal_disable').attr('src', '../images/spinner.gif');
				$('#modal_disable').modal('show');
			}
		});
	});

	$("#click_disable3").click(function() {
		$.ajax({
			url: './ac_services.php',
			method: 'POST',
			data: {
				"change_type": "false"
			},
			success: function(data) {
				setTimeout(function() {
					$('#modal_enable3 .txt_modal_enable3').text("<?=gettext('Active Protection Disabled!')?>");
					$('#modal_enable3 #loader_modal_enable3').attr('style', 'width:100px;height:auto');
					$('#modal_enable3 #loader_modal_enable3').attr('src', '../images/update_rules_ok.png');
				}, disabled_show_modal);
				setTimeout(function() {
					$('#modal_enable3').modal('hide');
				}, disabled_hide_modal);
				setTimeout(function() {window.location.href = "./ac_services.php"}, disabled_href_modal);
			},
			beforeSend: function(xhr, settings) {
				$('#modal_disable3 .txt_modal_disable3').text("<?=gettext('Wait, Disabling Active Protection')?>");
				$('#modal_disable3 #loader_modal_disable3').attr('style', 'width:200px;height:200px');
				$('#modal_disable3 #loader_modal_disable3').attr('src', '../images/spinner.gif');
				$('#modal_disable3').modal('show');
			}
		});
	});

	$("#click_disable4").click(function() {
		$('#modal_disabled_interfaces').modal('hide');
		setTimeout(() => {
			$('#modal_disable3 .txt_modal_disable3').text("<?=gettext('Wait, Disabling Active Protection')?>");
			$('#modal_disable3 #loader_modal_disable3').attr('style', 'width:200px;height:200px');
			$('#modal_disable3 #loader_modal_disable3').attr('src', '../images/spinner.gif');
			$('#modal_disable3').modal('show');
		}, 100);		
	});

	$("#click_disable5").click(function() {
		$('#modal_disabled_interfaces').modal('hide');
		setTimeout(() => {
			$('#modal_disable .txt_modal_disable').text("<?=gettext('Wait, Disabling FirewallAPP')?>");
			$('#modal_disable #loader_modal_disable').attr('style', 'width:200px;height:200px');
			$('#modal_disable #loader_modal_disable').attr('src', '../images/spinner.gif');
			$('#modal_disable').modal('show');
		}, 100);		
	});

	$("#ip_disable").click(function() {
		$.ajax({
			url: './ap_services.php',
			method: 'POST',
			data: {
				"change_type": "false"
			},
			success: function(data) {
				setTimeout(function() {
					$('#modal_enable2 .txt_modal_enable2').text("<?=gettext('IP Reputation Disabled!')?>");
					$('#modal_enable2 #loader_modal_enable2').attr('style', 'width:100px;height:auto');
					$('#modal_enable2 #loader_modal_enable2').attr('src', '../images/update_rules_ok.png');
				}, disabled_show_modal);
				setTimeout(function() {
					$('#modal_enable2').modal('hide');
				}, disabled_hide_modal);
				setTimeout(function() {window.location.href = "./ap_services.php"}, disabled_href_modal);
			},
			beforeSend: function(xhr, settings) {
				$('#modal_disable2 .txt_modal_disable2').text("<?=gettext('Wait, Disabling IP Reputation')?>");
				$('#modal_disable2 #loader_modal_disable2').attr('style', 'width:200px;height:200px');
				$('#modal_disable2 #loader_modal_disable2').attr('src', '../images/spinner.gif');
				$('#modal_disable2').modal('show');
			}
		});
	});

	$('#capacity_utm').attr('disabled', true);
	$('#capacity').val($("#capacity_utm").val());
	$('#capacity').parent().parent().hide();

	$("#model").change(function() {
		var index = $(this).prop('selectedIndex');
		$("#capacity_utm option")[index].selected = true;
		$("#capacity").val($("#capacity_utm").val());
	});

	events.push(function(){
		$("#showallgateways").click(function() {
			$("#widget-<?=$widgetname?>_panel-footer [id^=show]").each(function() {
				$(this).prop("checked", true);
			});
		});

		// Start polling for updates some small random number of seconds from now (so that all the widgets don't
		// hit the server at exactly the same time)
		// setTimeout(get_gw_stats, Math.floor((Math.random() * 10000) + 1000));
		get_gw_stats();
	});

	//Hide column checkbox in table
	if ($('table thead th input').attr('type') == "checkbox") {	
		$('td:nth-child(1)').hide();
		$('th:nth-child(1)').hide();
	} else {
		if ($('table tbody td input').attr('type') == "checkbox") {
			$('td:nth-child(1)').hide();
			$('th:nth-child(1)').hide();
		}
	}

	function libraryDropMenuApresentationSSLInspect() {
		if ($("li[role=presentation]").length > 0) {
			$("li[role=presentation]").hide();
			$($("li[role=presentation]")[0]).show();
			$($("li[role=presentation]")[9]).show();
		}
	}

	if (sessionStorage.getItem("showLiPresentationMenu") == "true") {
		$("li[role=presentation]").show();
	} else {
		if ((window.location.href.search("e2guardian") >= 0) && ((window.location.href.search("pkg_edit.php") >= 0) || (window.location.href.search("pkg.php") >= 0))) {
			libraryDropMenuApresentationSSLInspect();
		}

		if (window.location.href.search("e2guardian_monitor.php") >= 0) {
			libraryDropMenuApresentationSSLInspect();
		}
	}

	//Change word's pfsense to bluepexutm in all body tags
	setTimeout(() => {
		function replaceRecursively(element, from, to) {
			if (element.childNodes.length) {
				element.childNodes.forEach(child => replaceRecursively(child, from, to));
			} else {
				const cont = element.textContent;
				if (cont) element.textContent = cont.replace(from, to);
			}
		};
		replaceRecursively(document.body, new RegExp(/(pfsense)|(pfSense)|(PFSENSE)/, "g"), "BluePexUTM");
	}, 100);

	</script>
</body>
</html>
<?php
unset($_POST);
?>
