<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("service-utils.inc");
require('../classes/Form.class.php');

define("NETFILTER_FILE", "/var/tmp/wflogs");

$g['theme'] = "BluePex-4.0";
$input_errors = array();
$savemsg = "";
$categories = array(
	"1" => dgettext('BluePexWebFilter','Pornography'),
	"2" => dgettext('BluePexWebFilter','Music'),
	"3" => dgettext('BluePexWebFilter','Video'),
	"4" => dgettext('BluePexWebFilter','Books'),
	"5" => dgettext('BluePexWebFilter','Job Search'),
	"6" => dgettext('BluePexWebFilter','Sports'),
	"7" => dgettext('BluePexWebFilter','Games'),
	"8" => dgettext('BluePexWebFilter','Entertainment'),
	"9" => dgettext('BluePexWebFilter','E-learning'),
	"10" => dgettext('BluePexWebFilter','Chat'),
	"11" => dgettext('BluePexWebFilter','Newspapers'),
	"12" => dgettext('BluePexWebFilter','Magazines'),
	"13" => dgettext('BluePexWebFilter','Animations'),
	"14" => dgettext('BluePexWebFilter','Tutorials'),
	"15" => dgettext('BluePexWebFilter','Classfields'),
	"16" => dgettext('BluePexWebFilter','Dating'),
	"17" => dgettext('BluePexWebFilter','Curiosities'),
	"18" => dgettext('BluePexWebFilter','Shopping'),
	"19" => dgettext('BluePexWebFilter','News'),
	"20" => dgettext('BluePexWebFilter','Virtual cards'),
	"21" => dgettext('BluePexWebFilter','Esoterism'),
	"22" => dgettext('BluePexWebFilter','Webmail'),
	"25" => dgettext('BluePexWebFilter','Comics'),
	"26" => dgettext('BluePexWebFilter','TV'),
	"27" => dgettext('BluePexWebFilter','Recipes'),
	"28" => dgettext('BluePexWebFilter','Weapons'),
	"29" => dgettext('BluePexWebFilter','Auctions'),
	"30" => dgettext('BluePexWebFilter','Travelling'),
	"31" => dgettext('BluePexWebFilter','Pets'),
	"32" => dgettext('BluePexWebFilter','Hacking'),
	"33" => dgettext('BluePexWebFilter','Movies'),
	"34" => dgettext('BluePexWebFilter','Photography'),
	"35" => dgettext('BluePexWebFilter','Airlines'),
	"36" => dgettext('BluePexWebFilter','Arts'),
	"37" => dgettext('BluePexWebFilter','Cars'),
	"38" => dgettext('BluePexWebFilter','Banking'),
	"39" => dgettext('BluePexWebFilter','Blogs'),
	"40" => dgettext('BluePexWebFilter','Drugs'),
	"41" => dgettext('BluePexWebFilter','Social networks'),
	"42" => dgettext('BluePexWebFilter','Health'),
	"43" => dgettext('BluePexWebFilter','Sects and cults'),
	"44" => dgettext('BluePexWebFilter','dvertisement banners'),
	"45" => dgettext('BluePexWebFilter','Proxy tunnels'),
	"46" => dgettext('BluePexWebFilter','Search engines'),
	"47" => dgettext('BluePexWebFilter','Violence'),
	"48" => dgettext('BluePexWebFilter','Web portals'),
	"49" => dgettext('BluePexWebFilter','Nazism and hate crimes'),
	"50" => dgettext('BluePexWebFilter','Download sites'),
	"99" => dgettext('BluePexWebFilter','Not Categorized')
);

// AJAX Request
if (isset($_GET['webtail'])) {
	if (!is_pid_running("{$g['varrun_path']}/wfrotated.pid")) {
		echo "<h3 class='text-center'>" . dgettext("BluePexWebFilter","WF Realtime: Service WF Rotate not is running!") . "</h3>";
		exit;
	}

	$maxlines = isset($_GET['maxlines']) ? $_GET['maxlines'] : 10;
	$filter   = isset($_GET['filter']) ? $_GET['filter'] : "";
	$enable_color = ($_GET['color'] == "yes") ? true : false;

	exec("/usr/bin/pgrep -f realtime_logs", $out, $err);
	if (!$out)
        	mwexec_bg("/usr/local/bin/php /usr/local/bin/realtime_logs.php");

	exec("cat /var/log/wflogs.log | tail -$maxlines > /var/tmp/wflogs");

	if (!empty($filter)) {
		$cmd = "tail -n {$maxlines} " . NETFILTER_FILE . " | /usr/bin/grep -i '" . $filter . "' | sed 's/[[:space:]]/ / g'";
	} else {
		$cmd = "tail -n {$maxlines} " . NETFILTER_FILE . " | sed 's/[[:space:]]/ / g'";
	}

	$gc = exec($cmd, $outnet, $err);
	if ($err != 0) {
		echo "<h3 class='text-center'>" . dgettext('BluePexWebFilter','Error to process netfilter logs!') . "</h3>";
		exit;
	} elseif (empty($outnet)) {
		echo "<h3 class='text-center'>" . dgettext('BluePexWebFilter', 'No access data to display!') . "</h3>";
		exit;
	}

	$html = "<table class='table'>";
	$html .= "<thead>";
	$html .= "<tr>";
	$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','DateTime')) . "</th>";
	$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','URL')) . "</th>";
	$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Status')) . "</th>";
	$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Categories')) . "</th>";
	$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','IP Address')) . "</th>";
	$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Username')) . "</th>";
	$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Groupname')) . "</th>";
	$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Diagnostic')) . "</th>";
	$html .= "</tr>";
	$html .= "</thead>";
	$html .= "<tbody>";
	foreach ($outnet as $out) {
		$lines = explode("\n", $out);
		foreach($lines as $line) {
			if(!empty($line)) {
				$line_raw = explode(" ", str_replace("  ", " ", $line));
				$line = implode(" ", array_splice($line_raw, 5));
				$lines_array = explode(' ', $line);
				$color = "";
				if ($enable_color)
					$color = ($lines_array[2] >= 1000) ? "bgcolor='#FF6347'" : "bgcolor='#7FFFD4'";
				if ($lines_array[2] >= 1000) {
					$status = htmlentities(dgettext('BluePexWebFilter', 'blocked'));
				} else {
					$status = htmlentities(dgettext('BluePexWebFilter', 'allowed'));
				}
				$lines_array[5] = ($lines_array[5] == "-") ? htmlentities(dgettext('BluePexWebFilter','not referenced')) : $lines_array[5];
				$lines_array[6] = ($lines_array[6] == "-") ? htmlentities(dgettext('BluePexWebFilter','not referenced')) : $lines_array[6];
				$html .= "<tr>";
				$html .= "<td {$color}>" . date('Y-m-d H:i:s', $lines_array[0]) . "</td>";
				$html .= "<td {$color}><a href='{$lines_array[1]}'>" . substr($lines_array[1], 0, 60) . "...</a></td>";
				$html .= "<td {$color}><span class='badge'>{$status}</span></td>";
				$html .= "<td {$color}>" . htmlentities(getCategories($lines_array[3])) . "</td>";
				$html .= "<td {$color}>{$lines_array[4]}</td>";
				$html .= "<td {$color}>{$lines_array[5]}</td>";
				$html .= "<td {$color}>{$lines_array[6]}</td>";
				$html .= "<td {$color} align='center'><a class='btn btn-xs' target='_blank' href='/webfilter/wf_diagnostic.php?url={$lines_array[1]}&ip=$lines_array[4]&user={$lines_array[5]}'><i class='fa fa-search'></i></a></td>";
				$html .= "</tr>";
			}
		}
	}
	$html .= "</tbody>";
	$html .= "</table>";
	echo $html;
	exit;
}

function getCategories($refcat) {
	global $categories;
	foreach (explode(",", $refcat) as $cat) {
		if ($cat == "-" || $cat == 0)
			$categories_array[] = $categories['99'];
		else
			$categories_array[] = $categories[$cat];
	}
	return implode(", ", $categories_array);
}

if (!is_pid_running("{$g['varrun_path']}/wfrotated.pid")) {
	$input_errors[] = dgettext("BluePexWebFilter","WF Realtime: Service WF Rotate not is running!");
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Realtime'));
include('head.inc');

if ($input_errors)
        print_input_errors($input_errors);
if ($savemsg)
        print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Realtime'), true, '/webfilter/wf_realtime.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Webfilter Sync'), false, '/webfilter/wf_xmlrpc_sync.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Update Content'), false, '/webfilter/wf_updatecontent.php');
display_top_tabs($tab_array);

$form = new Form(false);
$section = new Form_Section('Options');

$max_lines = array();
foreach (range(10,100,10) as $lines) {
	$max_lines[$lines] = $lines;
}

$section->addInput(new Form_Select(
        'maxlines',
        dgettext('BluePexWebFilter', 'Amount Lines'),
        $maxlines,
	$max_lines
))->setHelp(dgettext('BluePexWebFilter', 'Enter amount lines to show access data. Default 10 lines.'));

$section->addInput(new Form_Input(
       	'filter',
       	dgettext('BluePexWebFilter', 'Filter Content'),
       	'text',
	$settings['content_filter_processes']
))->setHelp(dgettext('BluePexWebFilter', 'Enter the filter to filter access data.'));

$section->addInput(new Form_Checkbox(
        'enable_color',
        dgettext('BluePexWebFilter', 'Enable Color'),
        dgettext('BluePexWebFilter', 'Check this option to enable colors.'),
        true
));

$form->addGlobal(new Form_Button(
	'start',
	dgettext('BluePexWebFilter', 'Start')
))->removeClass('btn-primary')->addClass('btn-success');

$form->addGlobal(new Form_Button(
	'stop',
	dgettext('BluePexWebFilter', 'Stop')
))->removeClass('btn-primary')->addClass('btn-warning');

$form->add($section);

print $form;
?>
<script>
//<![CDATA[
events.push(function(){
	<?php if (isset($_GET['webtail'])): ?>
		var int=self.setInterval(function(){webtail()},1000);
	<?php endif ?>
	var int;
	function stopInterval() {
		int=window.clearInterval(int);
		$('#loading').fadeOut('fast');
	}
	function webtail() {
		var maxlines = $("#maxlines").val();
		var filter = $("#filter").val();
		var color = $("#enable_color:checked").val();
		var killcache = Math.random()*9999;
		var request_data = { "webtail": true, "filter": filter, "maxlines": maxlines, "color": color, "cachekill": killcache };
		$.get("wf_realtime.php", request_data, function(data) {
			$('#loading').fadeIn();
			$('#log').html(data);
		});
	}
	$("form input[type=submit]").click(function(e) {
		e.preventDefault();

		if ($(this).attr("name") == "start") {
			$("#realtime-logs").removeClass("hide");
			int = self.setInterval(function(){webtail()},1000);
		} else if ($(this).attr("name") == "stop") {
			stopInterval();
		}
		return false;
	});
});
//]]>
</script>
<div class="panel panel-default hide" id="realtime-logs">
	<div class="panel-heading">
		<h2 class="panel-title"><?=dgettext('BluePexWebFilter', 'Realtime Access');?></h2>
	</div>
	<div class="panel-body">
		<span class="pull-right" id="loading"><img class="img-responsive" src="themes/<?=$g['theme']?>/img/loader.gif" /></span>
		<div class="table-responsive" id="log"></div>
	</div>
</div>
<?php include('../foot.inc'); ?>
