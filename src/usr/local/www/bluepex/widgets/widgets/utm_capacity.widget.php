<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
define("LIMIT_HOSTS_FILE", "/etc/capacity-utm");

$limit_hosts = 0;
if (file_exists(LIMIT_HOSTS_FILE)) {
	$limit_hosts = trim(file_get_contents(LIMIT_HOSTS_FILE));
}
?>
<link href="/vendor/nvd3/nv.d3.css" rel="stylesheet" type="text/css" />
<style type="text/css">
svg { display: block; }
svg.nvd3-svg { height:280px; }
#utm-capacity-chart { background-color:#fff; border:1px solid #eee; }
#utm-capacity-chart #title { font-size:14px; font-weight:bold; margin:5px; background-color:#f9f9f9; padding:5px; }
</style>
<!-- UTM Capacity Chart -->
<div id="utm-capacity-chart">
	<div id="title" class="text-center"><?=sprintf(gettext("UTM Capacity: %s hosts"), $limit_hosts);?></div>
	<svg id="chart1" style="height:280px"></svg>
</div>
<!-- END Chart -->
<script src="/vendor/d3/d3.min.js" charset="utf-8"></script>
<script src="/vendor/nvd3/nv.d3.js"></script>
<script type="text/javascript">
//<![CDATA[
events.push(function(){
	var options = 'graphtype=line&left=utm-capacity&right=null&timePeriod=-1d&resolution=300';
	d3.json("rrd_fetch_json.php")
	    .header("Content-Type", "application/x-www-form-urlencoded")
	    .post(options, function(error, data) {
		if (typeof data.length != 'number')
		{
			return;
		}
		var get_chart_data = function (data) {
			if (data[0].values == 'undefined' || data[0].values.length == 0) {
				return [{ "key" : "<?=gettext('Permitted Hosts');?>", "values" : [] }, { "key" : "<?=gettext('Exceeded Hosts');?>", "values" : [] }];
			}
			var total_hosts = <?=$limit_hosts;?>;
			var permitted_hosts = [];
			var exceeded_host = [];
			for (var i=0; i<data[0].values.length; i++) {
				if (data[0].values[i][1] <= total_hosts) {
					permitted_hosts.push([data[0].values[i][0], Math.round(data[0].values[i][1])]);
					exceeded_host.push([data[0].values[i][0], 0]);
				} else {
					permitted_hosts.push([data[0].values[i][0], total_hosts]);
					exceeded_host.push([data[0].values[i][0], Math.round(data[0].values[i][1]-total_hosts)]);
				}
			}
			return [{ "key" : "<?=gettext('Permitted Hosts');?>", "values" : permitted_hosts }, { "key" : "<?=gettext('Exceeded Hosts');?>", "values" : exceeded_host }];
		}
		var chart_data = get_chart_data(data);
		var chart;
		nv.addGraph(function() {
			chart = nv.models.stackedAreaChart()
			    .useInteractiveGuideline(true)
			    .x(function(d) { return d[0] })
			    .y(function(d) { return d[1] })
			    .controlLabels({stacked: "Stacked"})
			    .duration(300);

			chart.height("280");
			chart.color(["#00CC00", "#F00000"])
			chart.xAxis.tickFormat(function(d) { return d3.time.format('%e/%m/%Y')(new Date(d)) });
			chart.yAxis.tickFormat(d3.format(',.0d'));
			chart.legend.vers('furious');
			var svg = d3.select('#chart1')
			    .datum(chart_data)
			    .transition().duration(1000)
			    .call(chart)
			    .each('start', function() {
				setTimeout(function() {
					d3.selectAll('#chart1 *').each(function() {
						if (this.__transition__) {
							this.__transition__.duration = 1;
						}
					})
				}, 0)
			});
			nv.utils.windowResize(chart.update);
			return chart;
		});
	});
});
//]]>
</script>
