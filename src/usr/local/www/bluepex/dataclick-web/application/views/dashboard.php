<div class="container text-center" id="dashboard-page">
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
								<a href="<?=base_url("main/dashboard/daily")?>" id="period_daily" class="btn btn-blue">
									<i class="fa fa-calendar"></i> <?=$this->lang->line('utm_dash_period_1');?>
								</a>
								<a href="<?=base_url("main/dashboard/weekly")?>" id="period_weekly" class="btn btn-blue">
									<i class="fa fa-calendar"></i> <?=$this->lang->line('utm_dash_period_7');?>
								</a>
								<a href="<?=base_url("main/dashboard/monthly")?>" id="period_monthly" class="btn btn-blue">
									<i class="fa fa-calendar"></i> <?=$this->lang->line('utm_dash_period_30');?>
								</a>
								<?php if (checkPermission("dataclick-web/dashboard/refresh-data")) : ?>
								<a href="<?=base_url("main/dashboard/refresh-data")?>" class="btn btn-warning btn-refresh" data-toggle="tooltip" data-placement="bottom" title="Refresh">
									<i class="fa fa-refresh"></i>
								</a>
								<?php endif; ?>
								<?php if (isset($json->reports_info->last_generated_reports)) : ?>
								<div id="last-update-reports">
									<?php if ($period == "daily") : ?>
									Last Update: <?=subDate($json->reports_info->last_generated_reports, "days", 1, "d/m/Y H:i:s");?>
									<?php elseif ($period == "weekly") : ?>
									Last Update: <?=subDate($json->reports_info->last_generated_reports, "weeks", 1, "d/m/Y H:i:s");?>
									<?php elseif ($period == "monthly") : ?>
									Last Update: <?=subDate($json->reports_info->last_generated_reports, "months", 1, "d/m/Y H:i:s");?>
									<?php endif; ?>
									 - <?=convertDate($json->reports_info->last_generated_reports, 'Y-m-d H:i:s', 'd/m/Y H:i:s');?>
								</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="col-md-3">
							<div class="text-right">
								<?php if (uri_string() == "main/realtime") : ?>
									<a href="<?=base_url() . "dashboard"?>" class="btn btn-blue">
										<i class="fa fa-dashboard"></i> <?=$this->lang->line('utm_dash_style1');?>
									</a>
								<?php else : ?>
									<a href="<?=base_url() . "main/realtime"?>" class="btn btn-blue">
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
		<div class="col-md-6"><!--Top 10 usuários-->
			<div class="panel panel-default widget-chart">
				<div class="panel-body">
					<h4><?=$this->lang->line('utm_dash_graph_users');?></h4>
					<p><h6><?=$this->lang->line('utm_dash_graph_users_sub');?></h6></p>
					<hr />
					<div id="chart1" style="height:300px;"></div>
				</div>
			</div>
		</div>
		<div class="col-md-6"><!--Top 5 redes sociais-->
			<div class="panel panel-default widget-chart">
				<div class="panel-body">
					<h4><?=$this->lang->line('utm_dash_graph_social');?></h4>
					<p><h6><?=$this->lang->line('utm_dash_graph_social_sub');?></h6></p>
					<hr />
					<div id="chart2" style="height:300px;"></div>
				</div>
			</div>
		</div>
		<div class="col-md-12"><!--Top 10 categorias-->
			<div class="panel panel-default widget-chart">
				<div class="panel-body">
					<h4><?=$this->lang->line('utm_dash_graph_cat');?></h4>
					<p><h6><?=$this->lang->line('utm_dash_graph_cat_sub');?></h6></p>
					<hr />
					<div id="chart3" style="height:300px;"></div>
				</div>
			</div>
		</div>
		<div class="col-md-6"><!--Top 10 domínios-->
			<div class="panel panel-default widget-chart">
				<div class="panel-body">
					<h4><?=$this->lang->line('utm_dash_graph_domain');?></h4>
					<p><h6><?=$this->lang->line('utm_dash_graph_domain_sub');?></h6></p>
					<hr />
					<div id="chart4" style="height:300px;"></div>
				</div>
			</div>
		</div>
		<div class="col-md-6"><!--Top 10 Sites/Acessos-->
			<div class="panel panel-default widget-chart">
				<div class="panel-body">
					<h4><?=$this->lang->line('utm_dash_graph_sites');?></h4>
					<p><h6><?=$this->lang->line('utm_dash_graph_sites_sub');?></h6></p>
					<hr />
					<div id="chart5" style="height:300px;"></div>
				</div>
			</div>
		</div>
		<div class="col-md-12"><!--Top 10 VPN users-->
			<div class="panel panel-default widget-chart">
				<div class="panel-body">
					<h4><?=$this->lang->line('utm_dash_graph_vpn');?></h4>
					<p><h6><?=$this->lang->line('utm_dash_graph_vpn_sub');?></h6></p>
					<hr />
					<div id="chart6" style="height:300px;"></div>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="<?=base_url("/public/plugins/echarts/dist/echarts.min.js");?>"></script>
<script src="<?=base_url("/public/js/echarts-theme-blue.js");?>"></script>
<script src="<?=base_url() . "public/js/dashboard.js";?>"></script>
<script type="text/javascript">
$(document).ready(function() {
	$.extend({
		percentage: function(a, b) {
			return Math.round((a / b) * 100);
		}
	});

	$(".btn-refresh").click(function(e) {
		e.preventDefault();
		$("body").alertModal({
			id: "modal-refresh-data",
			title: "<?=$this->lang->line('utm_dash_alert_title');?>",
			content: "<center><i class='fa fa-warning'></i><br /><?=$this->lang->line('utm_dash_alert_text1');?><br /><br />*<?=$this->lang->line('utm_dash_alert_text2');?></center>",
			footer: { btnCloseDescr: "<?=$this->lang->line('utm_dash_alert_cancel');?>", btnSaveDescr: "<?=$this->lang->line('utm_dash_alert_upd');?>", btnSaveLocation: $(this).attr("href") },
		});
	});

	// Active the selected period
	$("#period_<?php echo $period; ?>").addClass('active');

	// Graph 1
	<?php if (!empty($json->report_top_10_users)) : ?>
	var graph1_users = [];
	var graph1_values = [];
	<?php foreach ($json->report_top_10_users as $obj) : ?>
		graph1_users.push("<?php echo $obj->item ?>");
		graph1_values.push("<?php echo $obj->value ?>");
	<?php endforeach; ?>
	var params = {
		"title": '<?=$this->lang->line('utm_dash_graph_users_series');?>',
		"data": {
			"yAxis": graph1_users,
			"series": graph1_values
		}
	}
	chartRT0001(document.getElementById('chart1'), params);
	<?php else : ?>
		$("#chart1").html('<h4 class="text-center"><?=$this->lang->line('utm_charts_no_data');?></h4>');
	<?php endif; ?>

	// Graph 2
	<?php if (isset($json->report_top_5_social_access->sites)) : ?>
	var total = <?=$json->report_top_5_social_access->max_result?>;
	var series = [];
	series[1] = [
		<?php foreach ($json->report_top_5_social_access->sites as $sn) :?>
		{ value:$.percentage(<?=$sn->total?>, total), name:'<?=$sn->site?>' },
		<?php endforeach; ?>
	];
	var sortByValue = series[1].slice(0);
	sortByValue.sort(function(a,b) {
		return a.value - b.value;
	});
	var series_value = 5;
	var values = [];
	for (var i = 0; i<sortByValue.length; i++) {
		values.push({ value: series_value, name: sortByValue[i].name, tooltip: {show: false} });
		series_value += 5;
	}
	series[0] = values;
	var params = {
		"title": '<?=$this->lang->line('utm_dash_graph_social_series');?>',
		"data": {
			"series": series
		}
	}
	chartRT0002(document.getElementById('chart2'), params);
	<?php else : ?>
		$("#chart2").html('<h4 class="text-center"><?=$this->lang->line('utm_charts_no_data');?></h4>');
	<?php endif; ?>

	// Graph 3
	<?php if (!empty($json->report_top_5_categories)) : ?>
	var graph3_cat_name = [];
	var graph3_values = [];
	<?php foreach ($json->report_top_5_categories as $obj) : ?>
		graph3_cat_name.push("<?php echo $obj->item ?>");
		graph3_values.push("<?php echo $obj->value ?>");
	<?php endforeach; ?>
	var params = {
		"title": '<?=$this->lang->line('utm_dash_graph_cat_series');?>',
		"data": {
			"xAxis": graph3_cat_name,
			"series": graph3_values
		}
	}
	chartRT0003(document.getElementById('chart3'), params);
	<?php else : ?>
		$("#chart3").html('<h4 class="text-center"><?=$this->lang->line('utm_charts_no_data');?></h4>');
	<?php endif; ?>

	// Graph 4
	<?php if (!empty($json->report_top_10_domains)) : ?>
	var series = [
	<?php foreach ($json->report_top_10_domains as $obj): ?>
		{ value:<?=$obj->value?>, name:'<?=$obj->item?>' },
	<?php endforeach; ?>
	];
	var params = {
		"title": '<?=$this->lang->line('utm_dash_graph_cat_series');?>',
		"data": {
			"series": series
		} 
	}
	chartRT0004(document.getElementById('chart4'), params);
	<?php else : ?>
		$("#chart4").html('<h4 class="text-center"><?=$this->lang->line('utm_charts_no_data');?></h4>');
	<?php endif; ?>

	// Graph 5
	<?php if (!empty($json->report_top_10_accessed_sites)) : ?>
	var series = [
	<?php foreach ($json->report_top_10_accessed_sites as $obj): ?>
		{ value:<?=$obj->value?>, name:'<?=$obj->item?>' },
	<?php endforeach; ?>
	];
	var params = {
		"title": '<?=$this->lang->line('utm_dash_graph_sites_series');?>',
		"data": {
			"series": series
		}
	}
	chartRT0005(document.getElementById('chart5'), params);
	<?php else : ?>
		$("#chart5").html('<h4 class="text-center"><?=$this->lang->line('utm_charts_no_data');?></h4>');
	<?php endif; ?>

	// Graph 6
	<?php if (!empty($json->report_top_5_openvpn_control)) : ?>
		var graph6_cat_name = [];
		var graph6_values = [];
		<?php foreach ($json->report_top_5_openvpn_control as $obj) : ?>
			graph6_cat_name.push("<?php echo $obj->username ?>");
			graph6_values.push("<?php echo number_format($obj->bytes_received / pow(1024, 2), 2)?>");
		<?php endforeach; ?>
		graph6_cat_name.reverse();
		graph6_values.reverse();
		var params = {
			"title": "<?=$this->lang->line('utm_charts_data_consumed');?>",
			"data": {
				"xAxis": graph6_cat_name,
				"series": graph6_values
			}
		}
		chartRT0003(document.getElementById('chart6'), params);
	<?php else : ?>
		$("#chart6").html('<h4 class="text-center"><?=$this->lang->line('utm_charts_no_data');?></h4>');
	<?php endif; ?>
});
</script>
