<?php

/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 * Written by Wesley F. Peres <wesley.peres@bluepex.com>, 2019
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("pkg-utils.inc");
require_once("service-utils.inc");

if (is_package_installed("FRR")) {
	require_once("/usr/local/pkg/frr.inc");
	require_once("/usr/local/pkg/frr/inc/frr_bgp.inc");
}
$input_errors = array();
$matriz = &$config['system']['hostname'];

$groups = &$config['installedpackages']['frrbgp']['config']; 
$neighbors =  &$config['installedpackages']['frrbgpneighbors']['config'];

$statebgpd = array(
	"Idle" => array(dgettext("SD-WAN", gettext("No connection accepted, none attempted.")), "b-down.png"),
	"Established" => array(dgettext("SD-WAN", gettext("Fully established BGP session, KEEP ALIVEs are exchanged regulary, routes are exchanged.")), "b-up.png"),
	"Connect" => array(dgettext("SD-WAN", gettext("Trying to open a tcp connection with remote neighbor.")), "b-trying.png"),
	"Active" => array(dgettext("SD-WAN", gettext("Accepting tcp connection from neighbor, trying establish a TCP session.")), "b-active.png")
);

if (isset($_GET['act'])) {
	if ($_GET['act'] == "invert-metric") {
		$neighbor = explode("-", $_GET['neighbor']);
		$metric = explode("-", $_GET['metric']);
		
		if (count($metric) == 2 && count($neighbor) == 2) {
			update_metric($metric, $neighbor);
			write_config("FRR BGP metric changing...");
			frr_generate_config_bgp();
			restart_service("FRR bgpd");
			sleep(5);
			set_flash_message("success", dgettext("SD-WAN", gettext("Metric of Route changed successfully!")));
			pfSenseHeader("availability_branch_status.php");
			exit;
		} else {
			$input_errors[] = dgettext("SD-WAN", gettext("Not was possible to change route!"));
		}
	}
}

function update_metric($metric, $neighbor) {
	global $neighbors;

	$total = count($neighbors);
	for ($i=0; $i<$total; $i++) {
		if (!in_array($neighbors[$i]['peer'], $neighbor))
			continue;
		if ($neighbors[$i]['peer'] == $neighbor[0]) {
			$neighbors[$i]['weight'] = $metric[1];
		}
		if ($neighbors[$i]['peer'] == $neighbor[1]) { 
                        $neighbors[$i]['weight'] = $metric[0];
                }
	}
}

function status_bgpd($route, $active = false) {
	$fd = popen("/usr/local/bin/frrctl bgp sumary | grep {$route} 2>&1", "r");
        $ct = 0;
        $status = "";
        while (($line = fgets($fd)) !== FALSE) {
                $status .= htmlspecialchars($line, ENT_NOQUOTES);
                if ($ct++ > 1000) {
                        ob_flush();
                        $ct = 0;
                }
        }
        pclose($fd);

	$re = '/\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}\s+\d\s+\d{5}\s+\d{1,4}\s+\d{1,4}\s+\d\s+\d\s+\d\s(?:\d\w[\d|:][\d|}][\d|h][\d|:][\d][\d|\w]|\s+never)\s+(\d|Active|Connect|Idle)/m';

	preg_match_all($re, $status, $matches, PREG_SET_ORDER, 0);

	switch ($matches[0][1]) {
		case 1:
			if ($active) {
				$status = "fa fa-check-circle text-color-green-2";
			} else {
				$status = "fa fa-info-circle text-color-blue-2";
			}
			break;

		case 2:
			$status = "fa fa-info-circle text-color-blue-2";
			break;

		case "Connect":
			$status = "fa fa-info-circle text-color-orange";
			break;

		case "Active":
		case "Idle":
			$status = "fa fa-times-circle text-color-red";
			break;

	}

        return trim($status);
}

function descMetricNeighbor($route) {
	global $neighbors;

	$data = "";

	foreach ($neighbors as $neighbor) {
                if ($neighbor['peer'] != $route)
                        continue;

		$data = $neighbor['descr'];
	}

	return $data;
}

function checkRouteActive($command, $limit = "all", $filter = "", $header_size = 0) {
        $grepline = "";
        if (!empty($filter) && ($filter != "undefined")) {
                $ini = ($header_size > 0 ? $header_size+1 : 1);
                //$grepline = " | /usr/bin/sed -e '{$ini},\$ { /" . escapeshellarg(htmlspecialchars($filter)) . "/!d; };'";
                $grepline = " | grep " . escapeshellarg(htmlspecialchars($filter)) . " | awk '{print $1}'";
        }
        if (is_numeric($limit) && $limit > 0) {
                $limit += $header_size;
                $headline = " | /usr/bin/head -n " . escapeshellarg($limit);
        }

        $fd = popen("{$command}{$grepline}{$headline} 2>&1", "r");
        $ct = 0;
        $result = "";
        while (($line = fgets($fd)) !== FALSE) {
                $result .= htmlspecialchars($line, ENT_NOQUOTES);
                if ($ct++ > 1000) {
                        ob_flush();
                        $ct = 0;
                }
        }
        pclose($fd);

	$result = descMetricNeighbor(trim($result));
        
	return $result;
}

function getNetworkRoute($asnum) {
	$fd = popen("/usr/local/bin/frrctl bgp route | grep {$asnum} | awk '{print $2}' | grep '/' 2>&1", "r");
        $ct = 0;
        $network = "";
        while (($line = fgets($fd)) !== FALSE) {
                $network = htmlspecialchars($line, ENT_NOQUOTES);
                if ($ct++ > 1000) {
                        ob_flush();
                       	$ct = 0;
                }
        }
        pclose($fd);

	return trim($network);
}

function getRouteActive($asnum) {
	$fd = popen("/usr/local/bin/frrctl bgp route | grep {$asnum} | grep '*>' 2>&1", "r");
	$ct = 0;
	$route = "";
	while (($line = fgets($fd)) !== FALSE) {
		$route = htmlspecialchars($line, ENT_NOQUOTES);
		if ($ct++ > 1000) {
			ob_flush();
			$ct = 0;
		}
	}
	pclose($fd);

	$re = '/\*\&gt\;\s(\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}\/\d{2}|)\s+(\d{1,3}\.\d{1,3}.\d{1,3}\.\d{1,3})\s+(\d)\s+(\d{1,3})\s(\d{1,5})\s(\w)/';
	
	preg_match_all($re, trim($route), $matches, PREG_SET_ORDER, 0);
	
	return $matches;
}

$route = getRouteActive("65003");

function getGatewayRoute($network) {
	$network = explode("/", $network)[0];
	$fd = popen("netstat -rn | grep ovpn | grep {$network} | awk '{print $2}' 2>&1", "r");
        $ct = 0;
        $gateway = "";
        while (($line = fgets($fd)) !== FALSE) {
                $gateway = htmlspecialchars($line, ENT_NOQUOTES);
                if ($ct++ > 1000) {
                        ob_flush();
                        $ct = 0;
                }
        }
        pclose($fd);

	return trim($gateway);
}

function route_selected($remoteas) {
	$data = array();
	$cmd = "/usr/local/sbin/bgpctl show rib selected | /usr/bin/grep {$remoteas} | /usr/bin/sed 's/ * / /g'";

	$gc = exec($cmd, $output, $ret);
	if ($ret != 0) {
		log_error(dgettext("SD-WAN", gettext("Availability Branch: Not was possible to get the output routes.")));
		return $data;
	}
	foreach ($output as $out) {
		$format_route = explode(" ", trim($out));
		$data['network'] = $format_route[1];
		$data['tunnel'] = $format_route[2];
		$data['metric'] = $format_route[4];
	}
	return $data;
}

$pgtitle = array(dgettext("SD-WAN", gettext("SD-WAN")), dgettext("SD-WAN", gettext("Routes Status")));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg);

$tab_array = array();
$tab_array[] = array(dgettext("SD-WAN", gettext("Routes Status")), true, "availability_branch_status.php");
$tab_array[] = array(dgettext("SD-WAN", gettext("Server")), false, "availability_branch.php");
$tab_array[] = array(dgettext("SD-WAN", gettext("Client XML Import")), false, "availability_branch_import.php");
display_top_tabs($tab_array);

/*
* CHANGES:
* Added foot.inc on both sides of the decision, if you put only inside the sdwan, frr won't have the bottom menu working, 
*   if you put it at the end, parts of sdwan won't work, even if there is frr in the system;
* The FRR Package is required for this snippet to work, in this case, and it does not exist, it is shown to install the package;
*/
if (is_package_installed("frr")) { 
    //require_once("/usr/local/pkg/frr/inc/frr_bgp.inc");
    ?>
    <link href="BluePexUTM.css" rel="stylesheet">
    <style>
        .bg-blue {
        background-color: #007DC5;
        min-height: 0;
        }
        .text-color-blue-2 {
        color: #17a2b8;
        }
        .fa {
        font-size: 16px !important;
        }
        .fa-1 {
        font-size: 12px !important;
        }
        .bg-green {background-color: #68c756; color:#fff;}
        .pb-4, .py-4 {
            padding-bottom: 1.5rem !important;
        }
        .pt-4, .py-4 {
            padding-top: 1.5rem !important;
        }
        .text-color-green-2 {
            color: #68c756;
        }
        .mb-0, .my-0 {
            margin-bottom: 0 !important;
        }
        .mb-0, .my-0 {
            margin-bottom: 0 !important;
        }
        .left-border-grey {
            border-left: 4px solid #cfcfcf;
        }
        .p-2 {
            padding: .5rem !important;
        }
        .mb-2, .my-2 {
            margin-bottom: .9rem !important;
        }
        .box-light {
            background-color: #fff;
        }
        .p-2 {
            padding: .5rem !important;
        }
        .float-right {
            float: right !important;
        }
        #icon-connection-active {
            margin-top: -35px;
        }
    </style>
    <form action="availability_branch_import.php" method="post">
    <div id="page-sdwan">
        <div class="col-12">
            <div class="row mb-4 mt-3 pt-1">
                <div class="col-12 col-md-12 col-xl-8 pl-0 pr-md-3 pr-0">
                    <div class="border-box mt-4" id="sd-wan-branchs">
                        <div class="title-description">
                            <h5><?=gettext("ROUTES STATUS (BRANCHES)")?></h5>
                        </div>
                        <div class="col-xl-12 mt-3 text-center pb-2 mb-1">
                            <div class="text-center pb-3" id="chart-sdwan" style="width:100%; min-height:690px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 box-grey mt-4 px-0" id="box-branch-info" style="background-color:#efefef">                
                    
                    <div class="col-12 px-0 text-center">
                        <div class="bg-blue py-4" id="title-descriptions-sdwan">
                            <h5><?=gettext("BRANCH LIST")?></h5>
                            <div id="route_active_info" style="display:none"><i class="fa fa-wifi"></i> <strong><?=gettext("Active connection:")?></strong> <span id="route_active_descr"></span></div>
                        </div>
                    </div>                
                    
                    <div class="col-12 pt-3 mb-3">
                        <div class="box-light text-center p-3" id="description-sdwan" style="display:none">
                            <div class="row">
                                <div class="col-4">
                                    <h5 class="text-color-green-2 mb-0"><i class="fa fa-globe"></i> <span id="filial_network"></span></h5>
                                    <p class="mb-0"><?=gettext("Network")?></p>
                                </div>
                                <div class="col-4 border-left border-right">
                                    <h5 class="text-color-green-2 mb-0"><i class="fa fa-arrow-up"></i> <span id="filial_gateway"></span></h5>
                                    <p class="mb-0"><?=gettext("Gateway")?></p>
                                </div>
                                <div class="col-4">
                                    <h5 class="text-color-green-2 mb-0"><i class="fa fa-file"></i> <span id="route_weight"></span></h5>
                                    <p class="mb-0"><?=gettext("Weight")?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12" id="info-description-branch">
                        <?php 
                            foreach($neighbors as $neighbor) { 
                        ?>
                        <div class="<?=$neighbor['asnum']?> frr-neighbords box-light left-border-grey p-2 mb-2" style="display:none">
                            <div class="mb-3">
                                <h6><i class="<?=status_bgpd($neighbor['peer'], $neighbor['peer'] == getGatewayRoute(getNetworkRoute($neighbor['asnum'])) ? true : false);?>"></i> <?=$neighbor['descr']?></h6>
                                <span class="float-right" id="icon-connection-active"><i id="<?=str_replace(".", "-", $neighbor['peer']);?>" class="fa fa-wifi text-color-green-2" style="<?php if ($neighbor['peer'] != getGatewayRoute(getNetworkRoute($neighbor['asnum']))) { ?>display:none<?php } ?>;"></i></span>
                            </div>
                            <p class="mb-1"><?=gettext("Remote Tunnel Network:")?> <span id=""><?=$neighbor['peer']?></span></p>
                            <p class="mb-1"><?=gettext("Weight")?>: <span id=""><?=$neighbor['weight'];?></span></p>
                <?php if (status_bgpd($neighbor['peer'], $neighbor['peer'] == getGatewayRoute(getNetworkRoute($neighbor['asnum'])) ? true : false) != "fa fa-times-circle text-color-red") { ?>
                <p class="mb-0"><?=gettext("Outbound Route")?> <?php if ($neighbor['peer'] != getGatewayRoute(getNetworkRoute($neighbor['asnum']))) { ?><a href='availability_branch_status.php?act=invert-metric&neighbor=<?=$neighbor['peer'];?>-<?=getGatewayRoute(getNetworkRoute($neighbor['asnum']));?>&metric=<?=$neighbor['weight'];?>-<?=getRouteActive($neighbor['asnum'])[0][4];?>' title="<?=dgettext('SD-WAN', 'Change Route')?>"><i class="fa fa-refresh fa-1"></i></a><?php } else { ?><i class="fa fa-check fa-1"></i><?php } ?></p>
                <?php } ?>
                        </div>
                        <?php } ?>                    
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 pl-0 mb-5" id="info-legend">
            <h6><strong><?=gettext("NOTE")?></strong></h6>
            <hr class="my-2">
            <p class="mb-3 mb-md-1"><i class="fa fa-times-circle text-color-red"></i><?=gettext("No connection accepted, no attempt")?></p>
            <p class="mb-3 mb-md-1"><i class="fa fa-check-circle text-color-green-2"></i><?=gettext("BGP session fully established, KEEP ALIVEs regularly changed, routes changed")?></p>
            <p class="mb-3 mb-md-1"><i class="fa fa-info-circle text-color-orange"></i><?=gettext("Trying to open a TCP connection with the remote neighbor")?></p>
            <p class="mb-0"><i class="fa fa-info-circle text-color-blue-2"></i><?=gettext("Accepting tcp connection form neighbor, trying to establish a TCP session")?></p>
        </div>
    </div>
    <?php
        $data = array();
        $data['descr'] = "VPN1_MAT-MPLS_FIL-MPLS";
        $data['network'] = "192.168.20.0/24";
        $data['tunnel'] = "172.18.0.1";
        $data['metric'] = "5";
        $select = $data;

        $res = array();

        list($res['tunnel'], $res['as'], $res['state']) = explode(" ", "VPN1_MAT-MPLS_FIL-MPLS VPN1_MAT-MPLS_FIL-MPLS Established");

        $res = array_merge($res, $data);

        $data2 = array();
        $data2['descr'] = "VPN1_MAT-MPLS_FIL-MPLS";
        $data2['network'] = "192.168.20.0/24";                
        $data2['metric'] = "1";                

        $res2 = array();

        list($res2['tunnel'], $res2['as'], $res2['state']) = explode(" ", "VPN1_MAT-MPLS_FIL-MPLS VPN1_MAT-MPLS_FIL-MPLS Connect");

        $res2 = array_merge($res2, $data2);

        $data_new[] = $res;
        $data_new[] = $res2;
        $data_new[] = $res2;

        $status[] = $data_new;
        $status[] = $data_new;
        $status[] = $data_new;

        $filiais_ok = array();
        $filiais_info = array();
        $filiais_value_ok = array();
        $filiais_warning = array();
        $filiais_error = array();

        foreach($groups as $group) {
        $p_matriz['name'] = $group['name'];
            $matriz["Filial OK: {$group['name']}"] = "{$group['name']}";
        }    

        foreach($neighbors as $neighbor) {
        $network = getNetworkRoute($neighbor['asnum']);
            $gateway = getGatewayRoute($network);

        $route_active_descr = descMetricNeighbor(getRouteActive($neighbor['asnum'])[0][2]);
            $route_active = getRouteActive($neighbor['asnum'])[0][2];
            $route_weight = getRouteActive($neighbor['asnum'])[0][4];

        if (!empty($gateway)) {
            if (status_bgpd($neighbor['peer'], false) == "fa fa-times-circle text-color-red") {
                $filiais_error[] = array(
                                "name" => "Filial Erro: {$neighbor['descr']}",
                                "showName" => "{$neighbor['descr']}",
                                "asnum" => "{$neighbor['asnum']}"
                        );
            } else {
                    $filiais_ok[] = array( 
                    "name" => "Filial OK: {$neighbor['descr']}",
                    "showName" => "{$neighbor['descr']}",
                    "asnum" => "{$neighbor['asnum']}"
                ); 
            }
        } else {
            
            $filiais_warning[] = array(
                "name" => "Filial Atenção: {$neighbor['descr']}",
                "showName" => "{$neighbor['descr']}",
                "asnum" => "{$neighbor['asnum']}"
            );

        }

        $filiais_info["{$neighbor['asnum']}"] = array(
                "id" => str_replace(".", "-", $route_active),
                    "route_active_descr" => $route_active_descr,
                    "network" => $network,
                    "gateway" => $gateway,
                    "route_active" => $route_active,
                    "route_weight" => $route_weight,
            );

        }

        $filiais_info_json = json_encode($filiais_info);

    ?>
    </form>
    <?php include("foot.inc"); ?>
    <script>
    events.push(function() {
        // Resize panel-body element height
        var size_height = Math.max($(".panel-body").height());
        $(".panel-body").css("height", size_height);
    });
    </script>
    <script src="../vendor/echarts/dist/echarts.min.js"></script>
    <script type="text/javascript">

        var size = 50;

        var listdata = []; 
        var links = []; 
        var legendes = ["Branch_ok", "MatrizID", "Branch_warning", "MatrizID"];
        var texts = []; 
        var branchs = [];
        var branchOk = <?=json_encode($filiais_ok);?>;
        var valueBranchs = [];

        var valueBranchOK = ["01","02","03","04","05","06", "07","08","09", "10", "11", "12"]; 

        var branchWarning = <?=json_encode($filiais_warning);?>;

        var branchError = <?=json_encode($filiais_error);?>;

        for (var p in branchOk) {
        branchs.push(branchOk[p]);
        }

        for (var p in branchWarning) {
        branchs.push(branchWarning[p]);
        }

        for (var p in branchError) {
            branchs.push(branchError[p]);
        }

        var valueBranchWarning = ["01"];

        var valueBranchError = ["01"];

        var mainHeadCompany = {
            MatrizID: "<?=$matriz;?>",
        }

        function showAllNeighbors() {
            $('.frr-neighbords').show();
        }

        function setDataCompanyHead(json, n) {
            var i = 0;
            for (var p in json) {
                listdata.push({
                    x: 50,
                    y: 100,
                    "name": p,
            "value": p,
                    "showName": json[p],
                    "symbol":'image://'+"/images/matriz.png",
                    "symbolSize": 85,
                    "category": n,
                    "draggable": "false",
                    formatter: function(params) {
                        return params.data.showName
                    },
                    label:{
                        position: 'bottom'
                    }
                });
                i++;
            }
        }

        function setDataOk(json, n) {
            var i = 0;
            for (var p in json) {
            var filial_name = json[i].name;
            var filial_value = json[i].asnum;
            var filial_showName = json[i].showName;

                listdata.push({
                    x: i * 50,
                    y: size * 10 + i,
            /*"name": filial_showName,*/
            "showName": filial_showName,
                    "value": filial_value,
                    "symbol":'image://'+"/images/checked.png",
                    "symbolSize": size,
                    "category": n,
                    "draggable": false,
                    formatter: function(params) {
                return params.data.showName
                    },
                    label:{
                        position: 'bottom'
                    }
                });
                i++;
            }
        }

        function setDataWarning(json, n) {
            var i = 0;
            for (var p in json) {
            var filial_name = json[i].name;
            var filial_showName = json[i].showName;
            var filial_value = json[i].asnum;
                listdata.push({
                    x: i * 50,
                    y: size * 10 + i,
            "showName": filial_showName,
            "value": filial_value,
                    "symbol":'image://'+"/images/checked-warning.png",
                    "symbolSize": size,
                    "category": n,
                    "draggable": false,
                    formatter: function(params) {
                        return params.data.showName
                    },
                    label:{
                        position: 'bottom'
                    }
                });
                i++;
            }
        }

        function setDataError(json, n) {
            var i = 0;
            for (var p in json) {
                var filial_name = json[i].name;
                var filial_showName = json[i].showName;
                var filial_value = json[i].asnum;
                listdata.push({
                    x: i * 50,
                    y: size * 10 + i,
                    "showName": filial_showName,
                    "value": filial_value,
                    "symbol":'image://'+"/images/checked-critical.png",
                    "symbolSize": size,
                    "category": n,
                    "draggable": false,
                    formatter: function(params) {
                        return params.data.showName
                    },
                    label:{
                        position: 'bottom'
                    }
                });
                i++;
            }
        }

        function setLinkData(json, relarr, title) {
            if (relarr !== "") {
                var i = 0;
                for (var p in json) {
                    links.push({
                        "source": p,
                        "target": title,
                        "value": relarr[i],
                        lineStyle: {
                            normal: {
                                // text: relarr[i],
                                color: 'source'
                            }
                        }
                    });
                    i++;
                }
            } else {
                for (var p2 in json) {
                    links.push({
                        "source": p2,
                        "target": title,
                        "value": "",
                        lineStyle: {
                            normal: {
                                color: 'source'
                            }
                        }
                    });
                }
            }
        }

        function showFilialInfo(id) {
        var filiais_info = <?=$filiais_info_json;?>;
        $('#filial_network').text(filiais_info[id].network);
        $('#filial_gateway').text(filiais_info[id].gateway);

        if (filiais_info[id].gateway == "") {
            $('#title-descriptions-sdwan').removeClass('bg-green');
            $('#title-descriptions-sdwan').addClass('bg-warning');
        } else {
            $('#title-descriptions-sdwan').removeClass('bg-warning');
                    $('#title-descriptions-sdwan').addClass('bg-green');
        }

        $('#route_weight').text(filiais_info[id].route_weight);
        $('#route_active_descr').text(filiais_info[id].route_active_descr);
        } 

        function filterSDWANInfo(id) {
        if (id == "MatrizID") {
            $('#title-descriptions-sdwan').removeClass('bg-green');
            $('#title-descriptions-sdwan').addClass('bg-blue');
            $('#title-descriptions-sdwan h5').text('<?=gettext("BRANCH LIST")?>');
            $('#route_active_info').hide();
            $('#description-sdwan').hide();
            showAllNeighbors();
        } else {
            $('#title-descriptions-sdwan').removeClass('bg-blue');
                    $('#title-descriptions-sdwan').addClass('bg-green');
                    $('#title-descriptions-sdwan h5').text('');
            $('#route_active_info').show();
            $('#description-sdwan').show();
            $(".frr-neighbords").hide();
                $("." + id).show();
            showFilialInfo(id);
        }
        }

        for (var i = 0; i < legendes.length; i++) {
            texts.push({
                "name": legendes[i]
            })
        }

        // Initialize with load all neighbors
        showAllNeighbors();

        // Reload page every 60 seconds
        setTimeout(function() {
        location.reload();
        }, 60000);

        setDataOk(branchOk, 0);
        setDataWarning(branchWarning, 1);
        setDataError(branchError, 2);
        setDataCompanyHead(mainHeadCompany, 3);

        setLinkData(branchs, valueBranchs, legendes[3]);

        option = {
            tooltip: {
                formatter: '{b}'
            },
            backgroundColor: '#fff',
            animationDuration: 1000,
            // animationEasingUpdate: 'quinticInOut',
            series: [{
                type: 'graph',
                layout: 'force',
                force: {
                    repulsion: size * 5,
                    gravity: 0.05,
                    edgeLength: 208,
                    layoutAnimation: false,
                    draggable: false,
                },
                data: listdata,
                links: links,
                categories: texts,
                roam: false,
                nodeScaleRatio: 0, 
                focusNodeAdjacency: false, 
                lineStyle: {
                    normal: {
                        opacity: 0.1,
                        width: 1.9,
                        curveness: 0
                    }
                },
                label: {
                    normal: {
                        show: true,
                        position: 'inside',
                        textStyle: { 
                            color: '#000000', 
                            fontWeight: 'normal', 
                            fontSize: "12"
                        },
                        formatter: function(params) {
                            return params.data.showName 
                        },
                        fontSize: 18,
                        fontStyle: '600',
                    }
                },
                edgeLabel: {
                    normal: {
                        show: false,
                        textStyle: {
                            fontSize: 12
                        },
                        formatter: "{c}"
                    }
                }
            }],
            color: [
                '#000', '#000', '#000', '#000',
                '#000', '#000', '#000'
            ] 
        };

        const clickFun = param => {
        filterSDWANInfo(param.value);
        }

        var ChartSdWan = echarts.init(document.getElementById("chart-sdwan"));
        
        ChartSdWan.setOption(option);

        ChartSdWan.on("click", clickFun);

        $(window).resize(function() {
            ChartSdWan.resize();
        });

    </script>
    <?php

    $p_matriz = array();
    $p_matriz['name'] = $matriz;
    $p_matriz['network'] = $network;
    $p_matriz['gateway'] = $gateway;
    $p_matriz['metric'] = $route_weight;

    $p_filiais = array();

    foreach($neighbors as $key => $neighbor) {
        $status_bgpd = status_bgpd($neighbor['peer'], $neighbor['peer'] == getGatewayRoute(getNetworkRoute($neighbor['asnum'])) ? true : false);
        $status = "";
        
        switch($status_bgpd) {
            case "fa fa-check-circle text-color-green-2":
                $status = "Established";
                break;
            case "fa fa-info-circle text-color-blue-2":
                $status = "Active";
                break;
            case "fa fa-check-circle text-color-orange":
                $status = "Connect";
                break;
            case "fa fa-check-circle text-color-red":
                $status = "Idle";
                break;
        }

        $p_filiais[] = array(
            "name" => $neighbor['descr'],
            "tunel" => $neighbor['peer'],
            "metric" => $neighbor['weight'],
            "status" => $status
        );
    }

    init_config_arr(array('system', 'bluepex_stats', 'sd_wan', 'matriz'));
    init_config_arr(array('system', 'bluepex_stats', 'sd_wan', 'filiais'));

    $config['system']['bluepex_stats']['sd_wan']['matriz'] = $p_matriz;
    $config['system']['bluepex_stats']['sd_wan']['filiais'] = $p_filiais;

    write_config("SD-WAN settings write in config.xml");

} else {
    //Added this excerpt to show the need for FRR
    echo "<h3 class='text-center'>" . sprintf(dgettext("SD-WAN", gettext("Frr package is not installed! %s")), "<a href=\"../pkg_mgr_install.php?pkg=BluePexUTM-pkg-frr\">" . dgettext('SD-WAN', gettext('Click here to install package.')) . "</a>") . "</h3>";
    include("foot.inc");
}
?>
