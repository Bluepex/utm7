<div class="container text-center" id="dashboard-realtime-page">
	<div class="row">
		<section id="info">
			<div class="col-md-12">
				<div class="panel panel-default">
					<div class="panel-body">
						<div class="col-md-3">
							<div class="btn-group">
								<button type="button" class="btn btn-default"><i class="fa fa-server"></i> <?=$this->lang->line('utm_dash_default_utm');?> <?=$utm->name?></button>
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
									<span class="caret"></span>
									<span class="sr-only">UTM Dropdown</span>
								</button>
								<ul class="dropdown-menu">
									<?php foreach ($utms as $_utm) : ?>
									<li><a href="<?=base_url() . "main/utm_changeDisplay/{$_utm->id}"?>"><?=$_utm->name?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>
						<div class="col-md-6">
							<div class="text-center" id="navbar">
								<h4 class="text-center"><?=$this->lang->line('utm_dash_graph_real_header');?></h4>
							</div>
						</div>
						<div class="col-md-3">
							<div class="text-right">
								<?php if(uri_string() == "main/dashboard") : ?>
								<a href="<?=base_url() . "dashboard"?>" class="btn btn-blue">
									<i class="fa fa-dashboard"></i> <?=$this->lang->line('utm_dash_style1');?>
								</a>
								<?php else : ?>
								<a href="<?=base_url() . "main/dashboard"?>" class="btn btn-blue">
									<i class="fa fa-television"></i> <?=$this->lang->line('utm_dash_style2');?>
								</a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
	</div>
	<div class="row">
		<div class="col-md-12"><!--Acessos-->
			<div class="panel panel-default">
				<div class="panel-body">
					<h4>
						<i class="fa fa-group"></i> <?=$this->lang->line('utm_dash_graph_real_access');?>
						<button type="button" class="btn btn-sm btn-default btn-clean-table-rows pull-right" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_dash_realtime_eraser');?>"><i class="fa fa-eraser"></i></button>
					</h4>
					<hr />
					<div class="col-md-6">
						<div class="input-group">
							<span class="input-group-addon" id="filter"><i class="fa fa-filter"></i></span>
							<input type="text" name="filter" class="form-control" placeholder="<?=$this->lang->line('utm_dash_graph_real_input1');?>" />
						</div>
						<br />
					</div>
					<div class="col-md-6">
						<div class="input-group">
							<span class="input-group-addon" id="filter_ignore"><i class="fa fa-times-circle-o"></i></span>
							<input type="text" name="filter_ignore" class="form-control" placeholder="<?=$this->lang->line('utm_dash_graph_real_input2');?>" />
						</div>
						<br />
					</div>
					<div class="clearfix"></div>
					<div class="table-responsive" id="accesses" style="height:300px;">
						<table class='table text-left font-12'>
						<thead>
							<tr>
								<th><?=$this->lang->line('utm_cont_date');?></th>
								<th><?=$this->lang->line('utm_cont_username');?></th>
								<th>URL</th>
								<th><?=$this->lang->line('utm_cont_status');?></th>
								<th><?=$this->lang->line('utm_cont_cat');?></th>
								<th><?=$this->lang->line('utm_cont_ip');?></th>
								<th><?=$this->lang->line('utm_cont_group');?></th>
							</tr>
						</thead>
						<tbody id="access-row">
						</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6"><!--Access Facebook-->
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="title facebook">
						<img src="<?=base_url() . "public/images/logo_social_networks/facebook.png"?>" />
						<button type="button" class="btn btn-sm btn-default btn-clean-table-rows pull-right" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_dash_realtime_eraser');?>"><i class="fa fa-eraser"></i></button>
					</div>
					<hr />
					<div class="table-responsive" id="accesses_face" style="height:250px;">
						<table class='table text-left font-12'>
						<thead>
							<tr>
								<th><?=$this->lang->line('utm_cont_date');?></th>
								<th><?=$this->lang->line('utm_cont_username');?></th>
								<th><?=$this->lang->line('utm_cont_ip');?></th>
								<th><?=$this->lang->line('utm_cont_group');?></th>
							</tr>
						</thead>
						<tbody id="access-row">
						</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6"><!--Access youtube-->
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="title youtube">
						<img src="<?=base_url() . "public/images/logo_social_networks/youtube.png"?>" />
						<button type="button" class="btn btn-sm btn-default btn-clean-table-rows pull-right" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_dash_realtime_eraser');?>"><i class="fa fa-eraser"></i></button>
					</div>
					<hr />
					<div class="table-responsive" id="accesses_youtube" style="height:250px;">
						<table class='table text-left font-12'>
						<thead>
							<tr>
								<th><?=$this->lang->line('utm_cont_date');?></th>
								<th><?=$this->lang->line('utm_cont_username');?></th>
								<th><?=$this->lang->line('utm_cont_ip');?></th>
								<th><?=$this->lang->line('utm_cont_group');?></th>
							</tr>
						</thead>
						<tbody id="access-row">
						</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6"><!--Access Instagram-->
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="title instagram">
						<img src="<?=base_url() . "public/images/logo_social_networks/instagram.png"?>" />
						<button type="button" class="btn btn-sm btn-default btn-clean-table-rows pull-right" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_dash_realtime_eraser');?>"><i class="fa fa-eraser"></i></button>
					</div>
					<hr />
					<div class="table-responsive" id="accesses_insta" style="height:250px;">
						<table class='table text-left font-12'>
						<thead>
							<tr>
								<th><?=$this->lang->line('utm_cont_date');?></th>
								<th><?=$this->lang->line('utm_cont_username');?></th>
								<th><?=$this->lang->line('utm_cont_ip');?></th>
								<th><?=$this->lang->line('utm_cont_group');?></th>
							</tr>
						</thead>
						<tbody id="access-row">
						</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6"><!--Access Linkedin-->
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="title linkedin">
						<img src="<?=base_url() . "public/images/logo_social_networks/linkedin.png"?>" />
						<button type="button" class="btn btn-sm btn-default btn-clean-table-rows pull-right" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_dash_realtime_eraser');?>"><i class="fa fa-eraser"></i></button>
					</div>
					<hr />
					<div class="table-responsive" id="accesses_linke" style="height:250px;">
						<table class='table text-left font-12'>
						<thead>
							<tr>
								<th><?=$this->lang->line('utm_cont_date');?></th>
								<th><?=$this->lang->line('utm_cont_username');?></th>
								<th><?=$this->lang->line('utm_cont_ip');?></th>
								<th><?=$this->lang->line('utm_cont_group');?></th>
							</tr>
						</thead>
						<tbody id="access-row">
						</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6"><!--Access Twitter-->
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="title twitter">
						<img src="<?=base_url() . "public/images/logo_social_networks/twitter.png"?>" />
						<button type="button" class="btn btn-sm btn-default btn-clean-table-rows pull-right" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_dash_realtime_eraser');?>"><i class="fa fa-eraser"></i></button>
					</div>
					<hr />
					<div class="table-responsive" id="accesses_twitter" style="height:250px;">
						<table class='table text-left font-12'>
						<thead>
							<tr>
								<th><?=$this->lang->line('utm_cont_date');?></th>
								<th><?=$this->lang->line('utm_cont_username');?></th>
								<th><?=$this->lang->line('utm_cont_ip');?></th>
								<th><?=$this->lang->line('utm_cont_group');?></th>
							</tr>
						</thead>
						<tbody id="access-row">
						</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-6"><!--Access Whatsapp-->
			<div class="panel panel-default">
				<div class="panel-body">
					<div class="title whatsapp">
						<img src="<?=base_url() . "public/images/logo_social_networks/whatsapp_web.png"?>" />
						<button type="button" class="btn btn-sm btn-default btn-clean-table-rows pull-right" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_dash_realtime_eraser');?>"><i class="fa fa-eraser"></i></button>
					</div>
					<hr />
					<div class="table-responsive" id="accesses_whatsapp" style="height:250px;">
						<table class='table text-left font-12'>
						<thead>
							<tr>
								<th><?=$this->lang->line('utm_cont_date');?></th>
								<th><?=$this->lang->line('utm_cont_username');?></th>
								<th><?=$this->lang->line('utm_cont_ip');?></th>
								<th><?=$this->lang->line('utm_cont_group');?></th>
							</tr>
						</thead>
						<tbody id="access-row">
						</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="col-md-12"><!--VPN-->
			<div class="panel panel-default">
				<div class="panel-body">
					<h4><?=$this->lang->line('utm_dash_graph_real_vpn');?></h4>
					<hr />
					<div class="table-responsive" id="vpn" style="height:300px;"></div>
				</div>
			</div>
		</div>
		<div class="col-md-12"><!--Captive Portal-->
			<div class="panel panel-default">
				<div class="panel-body">
					<h4><?=$this->lang->line('utm_dash_graph_real_captive');?></h4>
					<hr />
					<div class="table-responsive" id="captive" style="height:300px;"></div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
$(document).ready(function() {
	function getInfo()
	{
		var filter_online_navigation = $("input[name=filter]").val();
		var filter_ignore_online_navigation = $("input[name=filter_ignore]").val();
		$.ajax({
			method: "POST",
			url: "<?=base_url() . "dashboard/realtime_values"?>",
			data: { "filter_online_navigation": filter_online_navigation, "filter_ignore_online_navigation": filter_ignore_online_navigation },
			success: function( response ) {
				//console.log(response);
				var data = JSON.parse(response);
				var msg_no_data = "<h4 class='text-center'><?=$this->lang->line('utm_charts_no_data');?></h4>";
				if (data.reportRT0001 !== "") {
					var rows = JSON.parse(data.reportRT0001);
					if (rows.length > 0) {
						for (var i=0; i<rows.length; i++) {
							if ($('#accesses #access-row tr[data-hash='+rows[i].line_hash+']').length == 0) {
								$('#accesses #access-row').prepend(rows[i].row);
							}
						}
					}
					if ($('#accesses #access-row tr').length > 1000) {
						$('#accesses #access-row tr').slice(500, $('#accesses #access-row tr').length).remove();
					}
				}
				if (data.reportRT0002_1 !== "") {
					var rows = JSON.parse(data.reportRT0002_1);
					if (rows.length > 0) {
						for (var i=0; i<rows.length; i++) {
							if ($('#accesses_face #access-row tr[data-hash='+rows[i].line_hash+']').length == 0) {
								$('#accesses_face #access-row').prepend(rows[i].row);
							}
						}
					}
					if ($('#accesses_face #access-row tr').length > 1000) {
						$('#accesses_face #access-row tr').slice(500, $('#accesses_face #access-row tr').length).remove();
					}
				}
				if (data.reportRT0002_2 !== "") {
					var rows = JSON.parse(data.reportRT0002_2);
					if (rows.length > 0) {
						for (var i=0; i<rows.length; i++) {
							if ($('#accesses_youtube #access-row tr[data-hash='+rows[i].line_hash+']').length == 0) {
								$('#accesses_youtube #access-row').prepend(rows[i].row);
							}
						}
					}
					if ($('#accesses_youtube #access-row tr').length > 1000) {
						$('#accesses_youtube #access-row tr').slice(500, $('#accesses_youtube #access-row tr').length).remove();
					}
				}
				if (data.reportRT0002_3 !== "") {
					var rows = JSON.parse(data.reportRT0002_3);
					if (rows.length > 0) {
						for (var i=0; i<rows.length; i++) {
							if ($('#accesses_insta #access-row tr[data-hash='+rows[i].line_hash+']').length == 0) {
								$('#accesses_insta #access-row').prepend(rows[i].row);
							}
						}
					}
					if ($('#accesses_insta #access-row tr').length > 1000) {
						$('#accesses_insta #access-row tr').slice(500, $('#accesses_insta #access-row tr').length).remove();
					}
				}
				if (data.reportRT0002_4 !== "") {
					var rows = JSON.parse(data.reportRT0002_4);
					if (rows.length > 0) {
						for (var i=0; i<rows.length; i++) {
							if ($('#accesses_linke #access-row tr[data-hash='+rows[i].line_hash+']').length == 0) {
								$('#accesses_linke #access-row').prepend(rows[i].row);
							}
						}
					}
					if ($('#accesses_linke #access-row tr').length > 1000) {
						$('#accesses_linke #access-row tr').slice(500, $('#accesses_linke #access-row tr').length).remove();
					}
				}
				if (data.reportRT0002_5 !== "") {
					var rows = JSON.parse(data.reportRT0002_5);
					if (rows.length > 0) {
						for (var i=0; i<rows.length; i++) {
							if ($('#accesses_twitter #access-row tr[data-hash='+rows[i].line_hash+']').length == 0) {
								$('#accesses_twitter #access-row').prepend(rows[i].row);
							}
						}
					}
					if ($('#accesses_twitter #access-row tr').length > 1000) {
						$('#accesses_twitter #access-row tr').slice(500, $('#accesses_twitter #access-row tr').length).remove();
					}
				}
				if (data.reportRT0002_6 !== "") {
					var rows = JSON.parse(data.reportRT0002_6);
					if (rows.length > 0) {
						for (var i=0; i<rows.length; i++) {
							if ($('#accesses_whatsapp #access-row tr[data-hash='+rows[i].line_hash+']').length == 0) {
								$('#accesses_whatsapp #access-row').prepend(rows[i].row);
							}
						}
					}
					if ($('#accesses_whatsapp #access-row tr').length > 1000) {
						$('#accesses_whatsapp #access-row tr').slice(500, $('#accesses_whatsapp #access-row tr').length).remove();
					}
				}
				if (data.reportRT0003 == "") {
					$('#vpn').html(msg_no_data);
				} else {
					$('#vpn').html(data.reportRT0003);
				}
				if (data.reportRT0004 == "") {
					$('#captive').html(msg_no_data);
				} else {
					$('#captive').html(data.reportRT0004);
				}
			}
		});
	}

	$(".btn-clean-table-rows").click(function() {
		$(this).parents(".panel").find("#access-row tr").fadeOut(300, function(){ $(this).remove(); });
	});

	if (typeof timeTicket1 !== 'undefined') {
		clearInterval(timeTicket1);
	}
	timeTicket1 = setInterval(function() {getInfo();}, 5000);
	getInfo();

	var hidden, visibilityChange;
	if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
		hidden = "hidden";
		visibilityChange = "visibilitychange";
	} else if (typeof document.msHidden !== "undefined") {
		hidden = "msHidden";
		visibilityChange = "msvisibilitychange";
	} else if (typeof document.webkitHidden !== "undefined") {
		hidden = "webkitHidden";
		visibilityChange = "webkitvisibilitychange";
	}

	function handleVisibilityChange()
	{
		if (document[hidden]) {
			clearInterval(timeTicket1);
		} else {
			timeTicket1 = setInterval(function() {getInfo();}, 5000);
		}
	}

	// Warn if the browser doesn't support addEventListener or the Page Visibility API
	if (typeof document.addEventListener === "undefined" || typeof document[hidden] === "undefined") {
		console.log("This Browser not supports 'addEventListener' or not supports the 'Page Visibility API'.");
	} else {
		// Handle page visibility change
		document.addEventListener(visibilityChange, handleVisibilityChange, false);
	}
});
</script>
