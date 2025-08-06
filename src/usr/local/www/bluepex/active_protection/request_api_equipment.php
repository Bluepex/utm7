<?php

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("system.inc");
require_once("bluepex/firewallapp_webservice.inc");

$pgtitle = array(gettext("Active Protection"), gettext("Active Protection Reports"));
$pglinks = array("./active_protection/ap_services.php", "@self");
include("head.inc");
?>

<div class="infoblock" style="margin-left: auto;">
    <div class="alert alert-info clearfix" role="alert">
        <div class="pull-left">
            <p><?=gettext("Graphic sampling of CSV files generated from samples taken sporadically.")?></p>
            <p><?=gettext("This page is updated from time to time, so if you request any new information, it will be presented as soon as it is available to the system.")?></p>
            <hr>
                <p><?=gettext("Information about data files:")?></p>
                <ul>
                    <li><?=gettext("LOG Filter -> This information refers to the geolocation of addresses filtered as suspicious and/or real threats by the interfaces in general.")?></li>
                    <li><?=gettext("Filter ACP -> This information refers to the geolocation of addresses filtered as suspicious and/or real threats by the interfaces running Active Protection.")?></li>
                </ul>
            <hr>
            <p class="text-danger"><?=gettext("OBS:")?></p>
            <ul>
                <li class="text-danger"><?=gettext("If your equipment is newly installed or does not have any previous Active Protection service, there will be no data to be populated on this page;")?></li>
                <li class="text-danger"><?=gettext("The number of records listed is different from the number of total records, the reason being that the records are compressed by the number of occurrences of the same;")?></li>
            </ul>
        </div>
    </div>
</div>

<div class="alert alert-warning clearfix" role="alert" name="displayRequesInRunning">
	<div class="pull-left">
		<p><?=gettext("The information for the population on the Active Protection status page is currently being obtained...")?></p>
	</div>
</div>

<?php
$tab_array = array();
$tab_array[] = array(gettext("Graphic presentation"), true, "./request_api_equipment.php");
$tab_array[] = array(gettext("Presentation of samples"), false, "./request_api_equipment_table.php");
$tab_array[] = array(gettext("Reports"), false, "./request_api_equipment_to_pdf.php");

display_top_tabs($tab_array);
?>

<div class="col-12 card displayNonePrint">
	<div class="row pb-2 pr-xl-7">
		<div class="col-sm-12 p-3 p-2 mb-2" style="height: 150px;">
			<h4><?=gettext("Get/Update Data")?></i></h4>
			<hr>
			<div class="d-flex">
				<div style='width:50%;'>
					<button class="btn btn-primary" style="width: 100%; height: 100%;" data-toggle="modal" data-target="#showOptionsModal" type="button"><i class='fa fa-search'></i> <?=gettext("Get/Update equipment data")?></button>
				</div>
				<div style='display:flex;width:50%;'>
					<div id="filterACP" style="text-align:center;width:50%;"></div>
					<div id="filterLOG" style="text-align:center;width:50%;"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="displayNone" id="page1print">
	<div class="col-12 card" name="displayNoneACP">
		<h4><?=gettext("Records by risk level")?></h4>
		<hr>
		<div id="qtdPriotityfilterACP" style="height:360px;margin:auto;"></div>                    
	</div>
</div>
<div class="displayNone" id="page2print">
	<div class="col-12 card" name="displayNoneAmbos">
		<h4><?=gettext("Risks listed by type")?></h4>
		<hr>
		<div id="qtdValuesfilter" style="height:360px;margin:auto;"></div>
	</div>
</div>
<div class="displayNone" id="page3print">
	<div class="col-12 card" name="displayNoneACP">
		<h4><?=gettext("Daily hits (Filter ACP) (TOP 5)")?></h4>
		<hr>
		<div id="qtdAccessTimeLineACP" style="height:360px;margin:auto;"></div>
		<h4 class="mt-5"><?=gettext("List of daily hits (TOP 10)")?></h4>
		<hr>
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>#</th>
					<th><?=gettext("Date/Time")?></th>
					<th><?=gettext("Acesses")?></th>
				</tr>
			</thead>
			<tbody id="qtdAccessTimeLineACPTable">
			</tbody>
		</table>
	</div>
</div>
<div class="displayNone" id="page4print">
	<div class="col-12 card" name="displayNoneLOG">
		<h4><?=gettext("Daily hits (Filter LOG) (TOP 5)")?></h4>
		<hr>
		<div id="qtdAccessTimeLineLOG" style="height:360px;margin:auto;"></div>
		<h4 class="mt-5"><?=gettext("List of daily hits (TOP 10)")?></h4>
		<hr>
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>#</th>
					<th><?=gettext("Date/Time")?></th>
					<th><?=gettext("Acesses")?></th>
				</tr>
			</thead>
			<tbody id="qtdAccessTimeLineLOGTable">
			</tbody>
		</table>
	</div>
</div>
<div class="displayNone" id="page5print">
	<div class="col-12 card" name="displayNoneACP">
		<h4><?=gettext("Top Accused Alerts (Filter ACP) (TOP 5)")?></h4>
		<hr>
		<div id="qtdAccessRulefilterACP" style="height:360px;margin:auto;"></div>
		<h4 class="mt-5"><?=gettext("Alerts listed (TOP 10)")?></h4>
		<hr>
		<table class="table table-hover table-striped table-condensed">
		<thead>
			<tr>
				<th>#</th>
				<th><?=gettext("Alert")?></th>
				<th><?=gettext("Acesses")?></th>
			</tr>
			</thead>
			<tbody id="qtdTableAccessRulefilterACP">
			</tbody>
		</table>
	</div>
</div>
<div class="displayNone" id="page6print">
	<div class="col-12 card" name="displayNoneACP">
		<h4><?=gettext("Top most accessed internal addresses (ACP Filter) (TOP 5)")?></h4>
		<hr>
		<div id="qtdIPAccessExternalfilterACP" style="height:360px;margin:auto;"></div>
		<h4 class="mt-5"><?=gettext("Internal Ips listed (TOP 10)")?></h4>
		<hr>
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>#</th>
					<th><?=gettext("IP")?></th>
					<th><?=gettext("Acesses")?></th>
				</tr>
			</thead>
			<tbody id="qtdTableIPAccessExternalfilterACP">
			</tbody>
		</table>
	</div>
</div>
<div class="displayNone" id="page7print">
	<div class="col-12 card" name="displayNoneACP">
		<h4><?=gettext("Top internal ports most accessed externally (ACP Filter) (TOP 5)")?></h4>
		<hr>
		<div id="qtdPortInternalfilterACP" style="height:360px;margin:auto;"></div>
		<h4 class="mt-5"><?=gettext("Most accessed internal ports (TOP 10)")?></h4>
		<hr>
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>#</th>
					<th><?=gettext("Port")?></th>
					<th><?=gettext("Acesses")?></th>
				</tr>
			</thead>
			<tbody id="qtdTablePortInternalfilterACP">
			</tbody>
		</table>
	</div>
</div>
<div class="displayNone" id="page8print">
	<div class="col-12 card" name="displayNoneACP">
		<h4><?=gettext("Main external addresses accessed (Filter ACP) (TOP 5)")?></h4>
		<hr>
		<div id="qtdIPExternalfilterACP" style="height:360px;margin:auto;"></div>
		<h4 class="mt-5"><?=gettext("External Ips listed (TOP 10)")?></h4>
		<hr>
		<table class="table table-hover table-striped table-condensed">
		<thead>
			<tr>
				<th>#</th>
				<th><?=gettext("IP")?></th>
				<th><?=gettext("Acesses")?></th>
			</tr>
			</thead>
			<tbody id="qtdTableIPExternalfilterACP">
			</tbody>
		</table>
	</div>
</div>
<div class="displayNone" id="page9print">
	<div class="col-12 card" name="displayNoneACP">
		<h4><?=gettext("Main external ports accessed (Filter ACP) (TOP 5)")?></h4>
		<hr>
		<div id="qtdPortExternalfilterACP" style="height:360px;margin:auto;"></div>
		<h4 class="mt-5"><?=gettext("Most accessed external ports (TOP 10)")?></h4>
		<hr>
		<table class="table table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>#</th>
					<th><?=gettext("Port")?></th>
					<th><?=gettext("Acesses")?></th>
				</tr>
			</thead>
			<tbody id="qtdTablePortExternalfilterACP">
			</tbody>
		</table>
	</div>
</div>
<div class="displayNone" id="page10print">
	<div class="col-12 card" name="displayNoneACP" id="accessFilterACPPrint">
		<h4><?=gettext("Protocol accesses (Filter ACP) (TOP 5)")?></h4>
		<hr>
		<div id="qtdProtocolfilterACP" style="height:360px;margin:auto;"></div>
	</div>
</div>
<div class="displayNone" id="page11print">
	<div class="col-12 card" name="displayNoneLOG" id="accessProtocolFilterLogPrint">
		<h4><?=gettext("Accesses by protocol (Filter Log) (TOP 5)")?></h4>
		<hr>
		<div id="qtdProtocolfilterLOG" style="height:360px;margin:auto;"></div>
	</div>
</div>
<div class="displayNone" id="page12print">
	<div class="col-12 card" name="displayNoneLOG">
		<h4><?=gettext("Accesses by Interface (Filter LOG) (TOP 5)")?></h4>
		<hr>
		<div id="qtdInterfacefilterLOG" style="height:360px;margin:auto;"></div>
	</div>
</div>

<div id="showOptionsModal" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title"><?=gettext("GET SAMPLING DATA")?></h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label for="data_range"><?=gettext("Filter by last day/s:")?> </label>
					<select class="form-control" id="data_range">
						<option value="1" default><?=gettext("1 Day")?></option>
						<option value="7"><?=gettext("7 Days")?></option>
						<option value="14"><?=gettext("14 Days")?></option>
						<option value="30"><?=gettext("30 Days")?></option>
						<option value="60"><?=gettext("60 Days")?></option>
						<option value="90"><?=gettext("90 Days")?></option>
					</select>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><?=gettext("Cancel")?></button>
				<button type="button" class="btn btn-primary" onclick="clearFields()" ><?=gettext("Clear search")?></button>
				<button type="button" class="btn btn-success" onclick="getCSV()" data-dismiss="modal"><?=gettext("Get")?></button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal_request" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_request" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_request" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<script src="/js/jquery-3.1.1.min.js?v=<?=filemtime('/usr/local/www/js/jquery-3.1.1.min.js')?>"></script>
<?php if (file_exists('/usr/local/www/vendor/echarts/dist/echarts.min.js')): ?>
<script src="/vendor/echarts/dist/echarts.min.js?v=<?=filemtime('/usr/local/www/vendor/echarts/dist/echarts.min.js')?>"></script>
<?php
endif;
if (file_exists('/usr/local/www/vendor/echarts/echarts.min.js')): 
?>
<script src="/vendor/echarts/echarts.min.js?v=<?=filemtime('/usr/local/www/vendor/echarts/echarts.min.js')?>"></script>
<?php endif; ?>
<script src="/js/echarts/map/js/world.js?v=<?=filemtime('/usr/local/www/js/echarts/map/js/world.js')?>"></script>
<script src="/js/traffic-graphs.js?v=<?=filemtime('/usr/local/www/js/traffic-graphs.js')?>"></script>
<?php
include("foot.inc");
?>
<script>

let colorPalette = ['#b82738','#e27f22', '#e1b317', '#86ae4e', '#007dc5'];

function showOptionsModal() {
    $("#showOptionsModal").show();
}

function clearFields() {
    $("#data_range").val("1");
}

function getCSV() {
	$('#modal_request .txt_modal_request').text("<?=gettext('Obtaining traffic data from the equipment.')?>");
	$('#modal_request').modal('show');

	$.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				getCSV: true,
				data_range: $("#data_range").val(),
			},
		}
	);

	setTimeout(() => { window.location.reload(); }, 15000);
}

function filterACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				filterACP: true,
			},
		}).done(function(data) {
            $("#filterACP").html(data);
        });
}

function filterLOG(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				filterLOG: true,
			},
		}).done(function(data) {
            $("#filterLOG").html(data);
        });
}

function qtdValuesfilter(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdValuesfilter: true,
			},
		}).done(function(data) {
            if (data != "false") {

			var chartDom = document.getElementById('qtdValuesfilter');
			var myChart = echarts.init(chartDom);
			var option;

            var dataPie = [];
            JSON.parse(data).map(function(values){
                dataPie.push({
                        name:values['name'],
                        value:values['value'],
                        label: {
                            formatter: values['name'],
                            show: true,
                            position: "outside"
                        }
                    }
                )
            });
            

            option = {
            tooltip: {
                trigger: 'item'
            },
            legend: {
                orient: 'vertical',
                left: 'left'
            },
            series: [
                {
                name: '<?=gettext("Access from")?>',
                color: colorPalette,
                type: 'pie',
                radius: '60%',
                data: dataPie,
                emphasis: {
                    itemStyle: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
                }
            ]
            }
            
            option && myChart.setOption(option);

        }});
}

function qtdProtocolfilterLOG(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdProtocolfilterLOG: true,
			},
		}).done(function(data) {
            if (data != "false") {

            let protocols = [];
            let values = [];
            let counter = 0;
            
            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {
                protocols.push(key);
                values.push(valuesJson[key]);
                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }
            
			var chartDom = document.getElementById('qtdProtocolfilterLOG');
			var myChart = echarts.init(chartDom);
			var option;

            option = {
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow'
                    }
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis: [
                    {
                    type: 'category',
                    data: protocols,
                    axisTick: {
                        alignWithLabel: true
                    }
                    }
                ],
                yAxis: [
                    {
                        type: 'value'
                    }
                ],
                series: [
                    {
                    itemStyle: {normal: {color: colorPalette[4]}},
                    name: '<?=gettext("Number of hits")?>',
                    type: 'bar',
                    barWidth: '60%',
                    label: {
                        show: true,
                        position: 'top'
                    },
                    data: values
                    }
                ]
            };

			option && myChart.setOption(option);
				
        }});
}

function qtdProtocolfilterACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdProtocolfilterACP: true,
			},
		}).done(function(data) {
            if (data != "false") {

            let protocols = [];
            let values = [];
            counter = 0;
            
            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {
                protocols.push(key);
                values.push(valuesJson[key]);
                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }
            
			var chartDom = document.getElementById('qtdProtocolfilterACP');
			var myChart = echarts.init(chartDom);
			var option;

            option = {
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow'
                    }
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis: [
                    {
                    type: 'category',
                    data: protocols,
                    axisTick: {
                        alignWithLabel: true
                    },
                    }
                ],
                yAxis: [
                    {
                        type: 'value',
                    }
                ],
                series: [
                    {
                    itemStyle: {normal: {color: colorPalette[3]}},
                    name: '<?=gettext("Number of hits")?>',
                    type: 'bar',
                    barWidth: '60%',
                    label: {
                        show: true,
                        position: 'top'
                    },
                    data: values
                    }
                ]
            };

			option && myChart.setOption(option);
        }});
}

function qtdInterfacefilterLOG(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdInterfacefilterLOG: true,
			},
		}).done(function(data) {
            if (data != "false") {

            let interfaces = [];
            let values = [];
            let counter = 0;
            
            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {
                interfaces.push(key);
                values.push(valuesJson[key]);
                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }
            
			var chartDom = document.getElementById('qtdInterfacefilterLOG');
			var myChart = echarts.init(chartDom);
			var option;

            option = {
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow'
                    }
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    containLabel: true
                },
                xAxis: [
                    {
                    type: 'category',
                    data: interfaces,
                    axisTick: {
                        alignWithLabel: true
                    }
                    }
                ],
                yAxis: [
                    {
                        type: 'value'
                    }
                ],
                series: [
                    {
                    itemStyle: {normal: {color: colorPalette[3]}},
                    name: '<?=gettext("Number of hits")?>',
                    type: 'bar',
                    barWidth: '60%',
                    label: {
                        show: true,
                        position: 'top'
                    },
                    data: values
                    }
                ]
            };

			option && myChart.setOption(option);
        }});
}


function qtdDatafilterACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdDatafilterACP: true,
			},
		}).done(function(data) {
            $("#qtdDatafilterACP").html(data);
        });
}

function qtdDatafilterLOG(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdDatafilterLOG: true,
			},
		}).done(function(data) {
            $("#qtdDatafilterLOG").html(data);
        });
}

function qtdPriotityfilterACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdPriotityfilterACP: true,
			},
		}).done(function(data) {
            if (data != "false") {

            chartDom = document.getElementById('qtdPriotityfilterACP');
            myChart = echarts.init(chartDom);
            option = '';
            dataPie = [];
            counterTable=1;
            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {
                if (!isNaN(key)) {

                    nameRisk = "";

                    if (key == 1) {
                        nameRisk = "<?=gettext("High Risk")?>";
                    } else if (key == 2) {
                        nameRisk = "<?=gettext("Medium Risk")?>";    
                    } else if (key == 3) {
                        nameRisk = "<?=gettext("Low Risk")?>";    
                    } else {
                        nameRisk = "<?=gettext("minimal risk")?>";    
                    }


                    dataPie.push({
                        name:nameRisk,
                        value:valuesJson[key],
                        label: {
                            formatter: nameRisk,
                            show: true,
                            position: "outside"
                        }
                    });
                }

            };

            option = {
                tooltip: {
                    trigger: 'item'
                },
                legend: {
                    orient: 'vertical',
                    left: 'left'
                },
                series: [
                    {
                        name: '<?=gettext("Access from")?>',
                        color: colorPalette,
                        type: 'pie',
                        radius: '60%',
                        data: dataPie,
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                            }
                        }
                    }
                ]
            }

            option && myChart.setOption(option);

        }});
}

function qtdAccessRulefilterACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdAccessRulefilterACP: true,
			},
		}).done(function(data) {
            if (data != "false") {

            let counter = 0;
            let qtTotais = 0;
            let series_teste = [];

            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {

                qtTotais = valuesJson[key];

                series_teste.push({
                    label: {
                        show: true,
                        position: 'inside'
                    },
					name: key,
					type: 'bar',
					data: [valuesJson[key]],
                    itemStyle: {
                        color: colorPalette[counter]
                    }
				});

                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }

            let tabletBase = "";
            let counterTable = 1;

            for (var key in valuesJson) {
                tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + valuesJson[key] + "</td></tr>";
                if (counterTable == 10) {
                    break;
                } else {
                    counterTable++;
                }
            }

            $("#qtdTableAccessRulefilterACP").html(tabletBase);

			var chartDom = document.getElementById('qtdAccessRulefilterACP');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: ["<?=gettext("TOP Accesses with alert")?>"]
				},
				series: series_teste
            };


			option && myChart.setOption(option);

        }});
}


function qtdIPAccessExternalfilterACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdIPAccessExternalfilterACP: true,
			},
		}).done(function(data) {
            if (data != "false"){ 

            let counter = 0;
            let qtTotais = 0;
            let series_teste = [];

            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {

                qtTotais = valuesJson[key];

                series_teste.push({
                    label: {
                        show: true,
                        position: 'inside'
                    },
					name: key,
					type: 'bar',
					data: [valuesJson[key]],
                    itemStyle: {
                        color: colorPalette[counter]
                    }
				});

                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }

            let values = [];
            let tabletBase = "";
            let counterTable = 1;

            for (var key in valuesJson) {
                tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + valuesJson[key] + "</td></tr>";
                if (counterTable == 10) {
                    break;
                } else {
                    counterTable++;
                }
            }
            $("#qtdTableIPAccessExternalfilterACP").html(tabletBase);

			var chartDom = document.getElementById('qtdIPAccessExternalfilterACP');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: ["<?=gettext("TOP Internal access's")?>"]
				},
				series: series_teste
            };


			option && myChart.setOption(option);

        }});
}

function qtdIPExternalfilterACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdIPExternalfilterACP: true,
			},
		}).done(function(data) {
            if (data != "false"){ 

            let counter = 0;
            let qtTotais = 0;
            let series_teste = [];

            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {

                qtTotais = valuesJson[key];

                series_teste.push({
                    label: {
                        show: true,
                        position: 'inside'
                    },
					name: key,
					type: 'bar',
					data: [valuesJson[key]],
                    itemStyle: {
                        color: colorPalette[counter]
                    }
				});

                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }

            let tabletBase = "";
            let counterTable = 1;

            for (var key in valuesJson) {
                tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + valuesJson[key] + "</td></tr>";
                if (counterTable == 10) {
                    break;
                } else {
                    counterTable++;
                }
            }
            $("#qtdTableIPExternalfilterACP").html(tabletBase);

			var chartDom = document.getElementById('qtdIPExternalfilterACP');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: ["<?=gettext("TOP external IP's accessed")?>"]
				},
				series: series_teste
            };


			option && myChart.setOption(option);

        }});
}

function qtdPortExternalfilterACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdPortExternalfilterACP: true,
			},
		}).done(function(data) {
            if (data != "false") {

            let counter = 0;
            let series_teste = [];

            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {
                
                series_teste.push({
                    label: {
                        show: true,
                        position: 'inside'
                    },
					name: key.split("P_")[1],
					type: 'bar',
					data: [valuesJson[key]],
                    itemStyle: {
                        color: colorPalette[counter]
                    }
				});

                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }

            let tabletBase = "";
            let counterTable = 1;

            for (var key in valuesJson) {
                tabletBase += "<tr><td>" + counterTable + "</td><td>" + key.split("P_")[1] + "</td><td>" + valuesJson[key] + "</td></tr>";
                if (counterTable == 10) {
                    break;
                } else {
                    counterTable++;
                }
            }
            $("#qtdTablePortExternalfilterACP").html(tabletBase);

			var chartDom = document.getElementById('qtdPortExternalfilterACP');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: ["<?=gettext("TOP External doors accessed")?>"]
				},
				series: series_teste
            };


			option && myChart.setOption(option);

        }});
}


function qtdPortInternalfilterACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdPortInternalfilterACP: true,
			},
		}).done(function(data) {
            if (data != "false") {

            let counter = 0;
            let qtTotais = 0;
            let series_teste = [];


            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {


                qtTotais = valuesJson[key];

                series_teste.push({
                    label: {
                        show: true,
                        position: 'inside'
                    },
					name: key.split("P_")[1],
					type: 'bar',
					data: [valuesJson[key]],
                    itemStyle: {
                        color: colorPalette[counter]
                    }
				});

                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }

            let tabletBase = "";
            let counterTable = 1;

            for (var key in valuesJson) {
                tabletBase += "<tr><td>" + counterTable + "</td><td>" + key.split("P_")[1] + "</td><td>" + valuesJson[key] + "</td></tr>";
                if (counterTable == 10) {
                    break;
                } else {
                    counterTable++;
                }
            }
            $("#qtdTablePortInternalfilterACP").html(tabletBase);

			var chartDom = document.getElementById('qtdPortInternalfilterACP');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: ["<?=gettext("TOP Internal doors used")?>"]
				},
				series: series_teste
            };

			option && myChart.setOption(option);

        }});
}

function qtdAccessTimeLineACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdAccessTimeLineACP: true,
			},
		}).done(function(data) {
            if (data != "false") {
            let interfaces = [];
            let values = [];
            let tabletBase = "";
            let counter = 0;
            let counterTable = 1;

            valuesJson = JSON.parse(data);
            for (var key in valuesJson) {
                interfaces.push(key);
                values.push(valuesJson[key]);
                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }

            for (var key in valuesJson) {
                tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + valuesJson[key] + "</td></tr>";
                if (counterTable == 10) {
                    break;
                } else {
                    counterTable++;
                }
            }

            $("#qtdAccessTimeLineACPTable").html(tabletBase);
            
			var chartDom = document.getElementById('qtdAccessTimeLineACP');
			var myChart = echarts.init(chartDom);
			var option;

            option = {
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow'
                    }
                },
                grid: {
                    left: '1%',
                    right: '2%',
                    bottom: '1%',
                    containLabel: true
                },
                xAxis: [
                    {
                    type: 'category',
                    data: interfaces,
                    axisTick: {
                        alignWithLabel: true
                    }
                    }
                ],
                yAxis: [
                    {
                        type: 'value'
                    }
                ],
                series: [
                    {
                    itemStyle: {normal: {color: colorPalette[3]}},
                    name: '<?=gettext("Number of hits")?>',
                    type: 'bar',
                    barWidth: '60%',
                    label: {
                        show: true,
                        position: 'top'
                    },
                    data: values
                    }
                ]
            };

			option && myChart.setOption(option);
        }});
}


function qtdAccessTimeLineLOG(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				qtdAccessTimeLineLOG: true,
			},
		}).done(function(data) {
            if (data != "false") {
            let interfaces = [];
            let values = [];
            let tabletBase = "";
            let counter = 0;
            let counterTable = 1;

            valuesJson = JSON.parse(data);

            for (var key in valuesJson) {
                interfaces.push(key);
                values.push(valuesJson[key]);
                if (counter == 4) {
                    break;
                } else {
                    counter++;
                }
            }

            for (var key in valuesJson) {
                tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + valuesJson[key] + "</td></tr>";
                if (counterTable == 10) {
                    break;
                } else {
                    counterTable++;
                }
            }

            $("#qtdAccessTimeLineLOGTable").html(tabletBase);
            
			var chartDom = document.getElementById('qtdAccessTimeLineLOG');
			var myChart = echarts.init(chartDom);
			var option;

            option = {
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'shadow'
                    }
                },
                grid: {
                    left: '1%',
                    right: '2%',
                    bottom: '1%',
                    containLabel: true
                },
                xAxis: [
                    {
                    type: 'category',
                    data: interfaces,
                    axisTick: {
                        alignWithLabel: true
                    }
                    }
                ],
                yAxis: [
                    {
                        type: 'value'
                    }
                ],
                series: [
                    {
                    itemStyle: {normal: {color: colorPalette[4]}},
                    name: '<?=gettext("Number of hits")?>',
                    type: 'bar',
                    barWidth: '60%',
                    label: {
                        show: true,
                        position: 'top'
                    },
                    data: values
                    }
                ]
            };

			option && myChart.setOption(option);
        }});
}

function displayNone(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				displayNone: true,
			},
		}).done(function(data) {
            data = data.split("\n")[2];
            if (data == "true") {
                $("div[name=displayNone]").removeAttr("style").attr("style", "display:block;");
            } else {
                $("div[name=displayNone]").removeAttr("style").attr("style", "display:none;");
            }
        });
}
function displayNoneACP(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				displayNoneACP: true,
			},
		}).done(function(data) {
            data = data.split("\n")[2];
            if (data == "true") {
                $("div[name=displayNoneACP]").removeAttr("style").attr("style", "display:block;");
            } else {
                $("div[name=displayNoneACP]").removeAttr("style").attr("style", "display:none;");
            }
        });
}
function displayNoneLOG(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				displayNoneLOG: true,
			},
		}).done(function(data) {
            data = data.split("\n")[2];
            if (data == "true") {
                $("div[name=displayNoneLOG]").removeAttr("style").attr("style", "display:block;");
            } else {
                $("div[name=displayNoneLOG]").removeAttr("style").attr("style", "display:none;");
            }
        });
}

function displayNoneAmbos(){
    $.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				displayNoneAmbos: true,
			},
		}).done(function(data) {
            data = data.split("\n")[2];
            if (data == "true") {
                $("div[name=displayNoneAmbos]").removeAttr("style").attr("style", "display:block;");
            } else {
                $("div[name=displayNoneAmbos]").removeAttr("style").attr("style", "display:none;");
            }
        });
}

function displayRequesInRunning() {
	$.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				displayRequesInRunning: true,
			},
		}).done(function(data) {
			data = data.split("\n")[2];
			if (data == "true") {
				$("div[name=displayRequesInRunning]").removeAttr("style").attr("style", "display:block;");
			} else {
				$("div[name=displayRequesInRunning]").removeAttr("style").attr("style", "display:none;");
			}
		}
	);
}

//Feitos
displayNone();
displayNoneACP();
displayNoneLOG();
displayNoneAmbos();
displayRequesInRunning();

setTimeout(() => {
    filterACP();
    filterLOG();
}, 100);
setTimeout(() => {
    qtdAccessTimeLineACP();
    qtdAccessTimeLineLOG();
}, 200);
setTimeout(() => {
    qtdValuesfilter();
}, 200);
setTimeout(() => {
    qtdProtocolfilterLOG();
}, 300);
setTimeout(() => {
    qtdProtocolfilterACP();
}, 400);
setTimeout(() => {
    qtdInterfacefilterLOG();
}, 500);
setTimeout(() => {
    qtdPriotityfilterACP();
}, 600);
setTimeout(() => {
    qtdAccessRulefilterACP();
}, 700);
setTimeout(() => {
    qtdIPExternalfilterACP();
    qtdIPAccessExternalfilterACP();
}, 800);
setTimeout(() => {
    qtdPortExternalfilterACP();
    qtdPortInternalfilterACP();
}, 900);

window.setInterval("qtdValuesfilter()", 15000);
window.setInterval("qtdProtocolfilterLOG()", 16000);
window.setInterval("qtdProtocolfilterACP()", 17000);
window.setInterval("qtdInterfacefilterLOG()", 18000);
window.setInterval("qtdPriotityfilterACP()", 19000);
window.setInterval("qtdAccessRulefilterACP()", 20000);
window.setInterval("qtdIPExternalfilterACP()", 21000);
window.setInterval("qtdIPAccessExternalfilterACP()", 25000);
window.setInterval("qtdPortExternalfilterACP()", 22000);
window.setInterval("qtdPortInternalfilterACP()", 26000);
window.setInterval("qtdAccessTimeLineACP()", 23000);
window.setInterval("qtdAccessTimeLineLOG()", 24000);
window.setInterval("displayNone()", 5000);
window.setInterval("displayNoneACP()", 5000);
window.setInterval("displayNoneLOG()", 5000);
window.setInterval("displayNoneAmbos()", 5000);
window.setInterval("filterACP()", 5000);
window.setInterval("filterLOG()", 5000);
window.setInterval("displayRequesInRunning()", 5000);

</script>
