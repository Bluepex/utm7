<?php
require_once("bp_webservice.inc");
require_once("bp_pack_version.inc");

function getValuesByColumn($file, $columnCounter, $columnValue, $sorted_values=true, $reverse_array=false, $fixed_index=false) {
    if (file_exists("/usr/local/www/active_protection/" . $file)) {
        $valuesLines = [];
        foreach(explode("\n",shell_exec("awk -F\",\" '{print $" . $columnCounter . "\",\"$"  . $columnValue . "}' /usr/local/www/active_protection/" . $file)) as $value) {
            $valuesLines[] = $value;
        }
        $linaFilter = [];
        foreach(explode("\n",shell_exec("awk -F\",\" '{print $" . $columnValue . "}' /usr/local/www/active_protection/" . $file)) as $value) {
            $linaFilter[] = $value;
        }
        $linaFilter = array_filter(array_unique($linaFilter));
        $returnCounter = [];
        foreach($linaFilter as $valueFilter) {
            foreach($valuesLines as $lineValues) {
                $lineValues = explode(",", $lineValues);
                if ($valueFilter == $lineValues[1]) {
                    $valueFilter = str_replace(' ', '_', $valueFilter);
                    $valueFilter = str_replace('"', '_', $valueFilter);
                    if ($fixed_index) {
                        $valueFilter = "P_" . $valueFilter;
                    }
                    $returnCounter[$valueFilter] = $returnCounter[$valueFilter] +  $lineValues[0];
                }
            }
        }
        if ($sorted_values) {
            arsort($returnCounter);
        }
        if ($reverse_array) {
            $returnCounter = array_reverse($returnCounter);
        }
        return json_encode($returnCounter);
    }
    return json_encode("{}");
}

function returnValueTable($value) {
    $valuesArray = str_split($value);
    $valueExplode = 1;
    $valueReturn = "";
    for($valuesQtd = count($valuesArray)-1;$valuesQtd >=0;$valuesQtd--) {
        if ($valueExplode == 3) {
            $valueReturn = "." . $valuesArray[$valuesQtd] . $valueReturn;
            $valueExplode = 0;
        } else {
            $valueReturn = $valuesArray[$valuesQtd] . $valueReturn;
        }
        $valueExplode++;
    }
    if (substr($valueReturn, 0, 1) == ".") {
        return ltrim($valueReturn, '.');
    } else {
        return $valueReturn;
    }
}

function returnBarFilters() {
	$returnJson = [];
	if (file_exists('/usr/local/www/active_protection/filtro_log_report.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_log_report.csv')) > 0) {
		$valuesLines = [];
		foreach(explode("\n",shell_exec("awk -F\",\" '{print $1}' /usr/local/www/active_protection/filtro_log_report.csv")) as $value) {
			$valuesLines[] = intval($value);
		}
        $returnValue = 0;
        if (array_sum($valuesLines) > 0) {
            $returnValue = array_sum($valuesLines);
        }
		$returnJson[0]["value"] = $returnValue;
        $returnJson[0]["name"] = "Filtro_LOG";
	}  else {
        $returnJson[0]["value"] = 0;
		$returnJson[0]["name"] = "Filtro_LOG";
    }
	if (file_exists('/usr/local/www/active_protection/filtro_acp_report.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp_report.csv')) > 0) {
		$valuesLines = [];
		foreach(explode("\n",shell_exec("awk -F\",\" '{print $2}' /usr/local/www/active_protection/filtro_acp_report.csv")) as $value) {
			$valuesLines[] = intval($value);
		}
        $returnValue = 0;
        if (array_sum($valuesLines) > 0) {
            $returnValue = array_sum($valuesLines);
        }
		$returnJson[1]["value"] = $returnValue;
		$returnJson[1]["name"] = "Filtro_ACP";
	} else {
        $returnJson[1]["value"] = 0;
		$returnJson[1]["name"] = "Filtro_ACP";
    }
	echo json_encode($returnJson);
}


?>
<link rel="stylesheet" href="nv.d3.css" media="screen, projection" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="bootstrap.min.css">
<link rel="stylesheet" href="pfSense.css">
<link rel="stylesheet" href="font-awesome.min.css">
<link rel="stylesheet" href="BluePexUTM.css">
<link rel="stylesheet" href="bootstrap-switch.min.css">

<meta content="text/html;charset=utf-8" http-equiv="Content-Type"> 
<meta content="utf-8" http-equiv="encoding">

<style>
    div.col-12.cards-info {
        width: 21cm;
        height: 29.7cm;
    } 
	.popover-content > table > thead {
		border-bottom-width: 2px !important;
		background: #108ad0 !important;
		color: #fff !important;
	}

	.popover-content > table > thead > tr > th {
		padding: 5px !important;
		text-align: center !important;
	}

	.popover-content > table > tbody > tr > td {
		padding: 5px !important;
		text-align: center !important;
	}

	select.form-control:not([size]):not([multiple]) {
		height:28px!important;
	}
	.card-ameaca, .card-system, .card-link, .card-app {
        background-color: #f2f2f2;
        border-left: 6px solid #13afad;
        overflow: hidden;
    }
    table > tbody > tr > td {
        border-top: 1px solid #FFF;
        text-align: center!important;
        font-size: 13px!important;
        vertical-align: top;
        padding: 6px 4px 6px 10px !important;
    }
    table > thead > tr > th {
        border-bottom-width: 2px;
        background: #108ad0;
        color: #fff;
        text-align: center!important;
        padding: 7px;
        font-size: 14px!important;
        padding: 6px 4px 6px 10px !important;
    }
    page {
        background: white;
        display: block;
        margin: 0 auto;
        margin-bottom: 0.5cm;
        box-shadow: 0 0 0.5cm rgba(0,0,0,0.5);
    }
    page[size="A4"] {  
        width: 21cm;
        height: 29.7cm; 
    }
    .observationBox {
        width: 19.7cm;
        bottom: 1;
        position: absolute;
        font-size: 10px;
        margin-bottom: 1cm;
    }
    .copyright {
        position: absolute;
        bottom: 0;
        width: 19cm;
    }
    html{height: 0;}
</style>

<script src="d3.min.js"></script>
<script src="nv.d3.js"></script>
<script src="visibility-1.2.3.min.js"></script>
<script src="jquery-3.1.1.min.js"></script>
<script src="traffic-graphs.js"></script>
<script src="echarts.min.js"></script>
<script src="echarts.min1.js"></script>
<script src="echarts.min2.js"></script>
<script src="world.js"></script>
<script src="Chart.min.js"></script>

<style>
.divBackground {
    background-color: #007DC5;
}
.divDisplayInline {
    display: -webkit-inline-box;
    width: 19.7cm !important;
}
.marginTop5Cm {
    margin-top: 200px !important;
}
.marginTop3Cm {
    margin-top: 3cm !important;
}
.paddingInternal1cm {
    padding: 1cm !important;
}
.divText {
    /*margin-bottom: 20px !important;*/
    margin-bottom: 0px !important;
}
p {
    margin-top: 0;
    margin-bottom: 0px;
}
.divIgnoreTextMarginBottom {
    margin-bottom: 0px !important;
}
.h3Text {
    color: white;
}
.noBold {
    font-weight: normal;
}
.imgTop {
    margin-top: 120px;
    width: 300px;
}
.internalWidthHead {
    width: 10cm;
}
.internalWidthHeadWithMarginLeft {
    width: 9cm;
    margin-left:0.85cm;
}
.internalWidthHeadWithMarginRigth {
    width: 9cm;
    margin-right:0.85cm;
}
.col-12.cards-info {
    padding: 1cm !important;
}
.titleH1 {
    font-size:42px;
    text-align:center;
    color: white;
}
.divColorWhite {
    color: white;
}
.divWidth19Cm: {
    width: 19cm !important;
}
.counterPage {
    /*margin-left: 11cm !important;*/
    margin-left: -1cm !important;
}
.centerPage {
    text-align: center;
}
.cards-info h6 {
    font-size: 16px;
    font-weight: normal;
    color: #007dc5;
    text-transform: uppercase;
    margin-top: 8px;
    text-align: center;
    font-weight: bold;
}
.h6LeftText {
    text-align: left !important;
}
.marginHeader {
    width: 20%;
}
.marginHeader60 {
    width: 60%;
}
.centerText {
    text-align: center;
}
.endText {
    text-align: end;
}
.marginTextHeader {
    margin-top: 10px;
}
.footerPage {
    width: 100% !important;
    text-align: center;
}
.pageSizeReport {
    height: 100%;
    width: 19.7cm;
}
.hrBoldLine {
    width: 19.7cm;
    border-top: 3px solid #007dc5 !important;
}
.hrResume {
    width: 9cm;
    border-top: 3px solid #007dc5 !important;
}
.tableReport {
    height: 10cm;
    margin-top:0px!important;
}
</style>

<!--
<page size="A4">
    <div class="col-12 cards-info divBackground">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <br>
                    <div>
                        <h1 class="titleH1 divWidth19Cm">Relatório de acessos</h1>
                        <h1 class="titleH1 divWidth19Cm">Active Protection</h1>
                    </div>
                    <div class='marginTop3Cm divDisplayInline'>
                        <div class="paddingInternal1cm internalWidthHead">
                            <div class="divText">
                                <h3 class="h3Text"><bold>Chave do produto:</bold></h3>
                                <h3 class="h3Text noBold"><?=getProductKey()?></h3>
                            </div>
                            <div class="divText">
                                <h3 class="h3Text"><bold>Serial do produto:</bold></h3>
                                <h3 class="h3Text noBold"><?=trim(file_get_contents("/etc/serial"))?></h3>
                            </div>
                            <div class="divText">
                                <h3 class="h3Text"><bold>Versão do software:</bold></h3>
                                <h3 class="h3Text noBold"><?=trim(file_get_contents('/etc/version')) . "," . explode(",",trim(file_get_contents('/etc/sub_version_base')))[1]?></h3>
                            </div>
                            <div class="divText">
                                <h3 class="h3Text"><bold>Modelo do equipamento:</bold></h3>
                                <h3 class="h3Text noBold"><?=trim(file_get_contents("/etc/model"))?></h3>
                            </div>
                            <div class="divText divIgnoreTextMarginBottom">
                                <h3 class="h3Text"><bold>Data referente:</bold></h3>
                                <h3 class="h3Text noBold"><?=date("d/m/Y - H:i")?></h3>
                            </div>
                        </div>
                        <div class="paddingInternal1cm internalWidthHead">
                            <img src='../../images/logo.png' class='imgTop'>
                        </div>
                    </div>
                    <div class='copyright divColorWhite centerPage'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <p>&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <h6><?=gettext("Sumário")?></h6>
                    <hr>
                    <table id="cabecalhoLog"></table>
                    <div class='copyright centerPage'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <p>&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>
-->


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"><h6>Reports Active Protection</h6></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <div class='divDisplayInline'>
                        <div class="internalWidthHead">
                            <div class="divText">
                                <p><b>Model:</b> <?=trim(file_get_contents("/etc/model"))?></p>
                                <p><b>Version:</b> <?=trim(file_get_contents('/etc/version')) . " (" . reset(bp_list_files_pack_fix('inverse', true)) . ") "?></h3>
                                <p><b>Producty Key:</b> <?=getProductKey()?></h3>
                            </div>
                        </div>
                        <div class="internalWidthHead">
                            <div class="divText">
                                <?php $range_time_value = (file_exists("/etc/report_acp_range") && !empty(file_get_contents("/etc/report_acp_range"))) ? file_get_contents("/etc/report_acp_range") : "1";?>
                                <p><b>Relative date (of):</b> <?=date('d/m/Y', strtotime("-{$range_time_value} day", strtotime('now')))?></p>
                                <p><b>Relevant date (until):</b> <?=date('d/m/Y', strtotime('-1 day', strtotime('now')))?></p>
                                <p><b>Serial:</b> <?=trim(file_get_contents("/etc/serial"))?></p>
                            </div>
                        </div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Origins of Intrusion Attempts (LOG Filter)</h6>
                    <div id="chart-map-threats" style="height:360px;border:0px solid #fff;padding:-10px;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6 class="h6LeftText">Countries of origin of the invasion attempts (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Country</th>
                                    <th>Attempts</th>
                                </tr>
                            </thead>
                            <tbody id="tentativasAcessosACP">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>This information refers to the geolocation of addresses filtered as suspicious and/or real threats by the interfaces in general.</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Origins of Intrusion Threats (ACP Filter)</h6>
                    <div id="chart-map-threats-ameacas" style="height:360px;border:0px solid #fff;padding:-10px;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6 class="h6LeftText">Countries of origin of the invasion attempts (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Country</th>
                                    <th>Attempts</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaAmeacasRecentesACP">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>This information refers to the geolocation of addresses filtered as suspicious and/or real threats by the interfaces running Active Protection.</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <!--<div class='divDisplayInline'>
                    <div class="internalWidthHeadWithMarginRigth">-->
                    <h6>Records by risk level</h6>
                    <div id="qtdPriotityfilterACP" style="height:200px;width:auto;margin:auto;"></div>
                    <br>
                    <!--<hr>-->
                    <!--<hr class="hrResume">-->
                    <h6 class="h6LeftText">Listed risks</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Risk</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="qtdPriotityfilterACPTable">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <p>The risk levels listed refer to the rules in operation of Active Protection that operate in a preventive way, therefore, potentially harmful traffic is blocked, however they are registered as "attempts", the classification follows below:</p>
                        <ul>
                            <li>High Risk: Access identified as possibly harmful and blocked;</li>
                            <li>Medium Risk: Access identified as dubious, its action is not blocked, but it is placed on alert;</li>
                            <li>Low Risk: Access identified with low risk potential and is only alerted;</li>
                            <li>Minimum Risk: Access identified as risk-free and can travel normally;</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - Todos os direitos reservados.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>

<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Risks listed by type</h6>
                    <div id="qtdValuesfilter" style="height:200px;width:auto;margin:auto;"></div>
                    <br>
                    <h6 class="h6LeftText">Risks listed by type</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody id="qtdValuesfilterTable">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>LOG Filter: Refers to the general access of all interfaces with external access;</li>
                            <li>ACP Filter: Refers to all access records by Active Protection;</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Time Line accesses - (ACP Filter)</h6>
                    <div id="qtdAccessTimeLineACP" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6  class="h6LeftText">Dates listed (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date/Time</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdAccessTimeLineACPTable">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>The graph and the table demonstrate the number of accesses registered on a given date, that is, regardless of whether the access was interrupted or released.</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Accesses by Interface (LOG Filter)</h6>
                    <div id="qtdAccessTimeLineLOG" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6 class="h6LeftText">Listed Dates (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date/time</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdAccessTimeLineLOGTable">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>The graph and the table demonstrate the number of accesses registered on a given date, that is, regardless of whether the access was interrupted or released..</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Top Accused Alerts (ACP Filter) (TOP 5)</h6>
                    <div id="qtdAccessRulefilterACP" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6 class="h6LeftText">Listed Alerts (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Alerts</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdTableAccessRulefilterACP">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>The alerts presented were the most 'triggered' during the period of time, these alerts refer to rules running in Active Protection;</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>

<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Top most accessed internal addresses (ACP Filter) (TOP 5)</h6>
                    <div id="qtdPortInternalfilterACP" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6 class="h6LeftText">Internal IPs listed (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>IPs</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdTablePortInternalfilterACP">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>The registered internal IP's refer to the ones that suffered the most attempts of external access, which may be legitimate attempts or improper access;</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Main internal ports accessed (ACP filter) (TOP 5)</h6>
                    <div id="qtdPortExternalfilterACP" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6 class="h6LeftText">Listed Internal Doors (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Ports</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdTablePortExternalfilterACP">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>The ports listed refer to the ones that had the most attempts to access externally to the internal services;</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>TOP accesses of Internal IP's (ACP Filter) (TOP 5)</h6>
                    <div id="qtdIPAccessExternalfilterACP" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6 class="h6LeftText">Listed Ips (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>IPs</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdTableIPAccessExternalfilterACP">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>The external IP's refer to connection attempts from the internal to external network, and the addresses listed are those that had more requests that may have been successful or interrupted;</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Main external ports accessed (ACP filter) (TOP 5)</h6>
                    <div id="qtdIPExternalfilterACP" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6 class="h6LeftText">Most accessed external doors (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Ports</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdTableIPExternalfilterACP">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>External ports are normally provided dynamically by the site/service provider, so the values ​​listed are not rules that access to certain addresses must be performed by a certain port.</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Protocol accesses (ACP filter) (TOP 5)</h6>
                    <div id="qtdProtocolfilterACP" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6>Protocols used (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Protocol</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdProtocolfilterACPTable">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>The protocols listed are those detected by Active Protection;</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Protocol accesses (LOG filter) (TOP 5)</h6>
                    <div id="qtdProtocolfilterLOG" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6>Protocols used (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Protocol</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdProtocolfilterLOGTable">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>The protocols listed are those detected by the general use of interfaces with external access;</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<page size="A4">
    <div class="col-12 cards-info">
        <div class="row pb-0">
            <div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12">
                <div class="pageSizeReport">
                    <div class='divDisplayInline'>
                        <div class="marginHeader"><img src="logo-login.jpg"></div>
                        <div class="marginHeader60 centerText marginTextHeader"></div>
                        <div class="marginHeader endText"><img src="logo-default.jpg" style="height: 36px;"></div>
                    </div>
                    <hr class="hrBoldLine">
                    <h6>Accesses by Interface (LOG Filter) (TOP 5)</h6>
                    <div id="qtdInterfacefilterLOG" style="height:360px;margin:auto;"></div>
                    <br>
                    <br>
                    <br>
                    <!--<hr>-->
                    <h6>Interfaces Listed (TOP 10)</h6>
                    <div class='tableReport'>
                        <table class="table table-bordered mt-lg-4">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Interfaces</th>
                                    <th>Accesses</th>
                                </tr>
                            </thead>
                            <tbody id="qtdInterfacefilterLOGTable">
                            </tbody>
                        </table>
                    </div>
                    <div class='observationBox'>
                        <p>Observation:</p>
                        <ul>
                            <li>The interfaces listed are those that were detected that performed external accesses;</li>
                        </ul>
                    </div>
                    <div class='copyright'>
                        <hr class="hrBoldLine" style='margin-top: 100%;'>
                        <div class="divDisplayInline">
                            <p class="footerPage">&copy; <?=date("Y");?> BluePex CyberSecurity S/A - All rights reserved.</p>
                            <p class="counterPage"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</page>


<script>

function resumeDescrKey(counter, value) {
	value = (value.length >= 25) ? value.slice(0, 25) + '...' : value;
	return (counter + 1) + ") " + value;
}

function explodeNumbersShowTable(value) {
    value = String(value);
    valorString = value.split("");
    valueExplode = 1;
    valueReturn = "";
    for(var counter=value.length-1;counter >=0;counter--) {
        if (valueExplode == 3) {
            valueReturn = "." + valorString[counter] + valueReturn; 
            valueExplode = 0;
        } else {
            valueReturn = valorString[counter] + valueReturn; 
        }
        valueExplode++;
    }
    if (valueReturn.substr(0, 1) == ".") {
        return valueReturn.slice(1);
    } else {
        return valueReturn;
    }
}

colorPalette = ['#b82738','#e27f22', '#e1b317', '#86ae4e', '#007dc5'];

chartDom = document.getElementById('qtdValuesfilter');
myChart = echarts.init(chartDom);
option = '';
dataPie = [];
counterTable=1;
tabletBase = "";
JSON.parse('<?=returnBarFilters()?>').map(function(values){
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
    if (counterTable <= 10) {
        tabletBase += "<tr><td>" + counterTable + "</td><td>" + values['name'] + "</td><td>" + explodeNumbersShowTable(values['value']) + "</td></tr>";
    }
    counterTable++;
});


$("#qtdValuesfilterTable").html(tabletBase);

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


chartDom = document.getElementById('qtdPriotityfilterACP');
myChart = echarts.init(chartDom);
option = '';
dataPie = [];
counterTable=1;
tabletBase = "";
valuesJson = JSON.parse('<?=getValuesByColumn("filtro_acp_report.csv", 2, 5)?>');
for (var key in valuesJson) {
    if (!isNaN(key)) {
        tabletBase += "<tr><td style='vertical-align: middle;'>" + counterTable + "</td>";

        nameRisk = "";
        if (key == 1) {
            nameRisk = "High risk";
            tabletBase += "<td style='vertical-align: middle;'><p>High risk</p></td>";        
        } else if (key == 2) {
            nameRisk = "Medium Risk";
            tabletBase += "<td style='vertical-align: middle;'><p>Medium Risk</p></td>";        
        } else if (key == 3) {
            nameRisk = "Low risk";
            tabletBase += "<td style='vertical-align: middle;'><p>Low risk</p></td>";          
        } else {
            nameRisk = "Minimum Risk";
            tabletBase += "<td style='vertical-align: middle;'><p>Minimum Risk</p></td>";        
        }
        tabletBase += "<td style='vertical-align: middle;'>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";

        dataPie.push({
            name:nameRisk,
            value:valuesJson[key],
            label: {
                formatter: nameRisk,
                show: true,
                position: "outside"
            }
        });

        if (counterTable == 10) {
            break;
        } else {
            counterTable++;
        }
    }
};


$("#qtdPriotityfilterACPTable").html(tabletBase);

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

data_response = "";
data = [];

data_response = JSON.parse('<?=trim(file_get_contents("/usr/local/www/active_protection/tentativas_invasao"))?>');			
for (var item in data_response.data) {
    data.push({name: data_response.data[item].name, value: data_response.data[item].value});
}
	
nameMap = {
	'Afghanistan':'Afghanistan',
	'Singapore':'Singapore',
	'Angola':'Angola',
	'Albania':'Albania',
	'United Arab Emirates':'United Arab Emirates',
	'Argentina':'Argentina',
	'Armenia':'Armenia',
	'French Southern and Antarctic Lands':'French Southern and Antarctic Lands',
	'Australia':'Australia',
	'Austria':'Austria',
	'Azerbaijan':'Azerbaijan',
	'Burundi':'Burundi',
	'Belgium':'Belgium',
	'Benin':'Benin',
	'Burkina Faso':'Burkina Faso',
	'Bangladesh':'Bangladesh',
	'Bulgaria':'Bulgaria',
	'The Bahamas':'The Bahamas',
	'Bosnia and Herzegovina':'Bosnia and Herzegovina',
	'Belarus':'Belarus',
	'Belize':'Belize',
	'Bermuda':'Bermuda',
	'Bolivia':'Bolivia',
	'Brazil':'Brazil',
	'Brunei':'Brunei',
	'Bhutan':'Bhutan',
	'Botswana':'Botswana',
	'Central African Republic':'Central African Republic',
	'Canada':'Canada',
	'Switzerland':'Switzerland',
	'Chile':'Chile',
	'China':'China',
	'Ivory Coast':'Ivory Coast',
	'Cameroon':'Cameroon',
	'Democratic Republic of the Congo':'Democratic Republic of the Congo',
	'Republic of the Congo':'Republic of the Congo',
	'Colombia':'Colombia',
	'Costa Rica':'Costa Rica',
	'Cuba':'Cuba',
	'Northern Cyprus':'Northern Cyprus',
	'Cyprus':'Cyprus',
	'Czech Republic':'Czech Republic',
	'Germany':'Germany',
	'Djibouti':'Djibouti',
	'Denmark':'Denmark',
	'Dominican Republic':'Dominican Republic',
	'Algeria':'Algeria',
	'Ecuador':'Ecuador',
	'Egypt':'Egypt',
	'Eritrea':'Eritrea',
	'Spain':'Spain',
	'Estonia':'Estonia',
	'Ethiopia':'Ethiopia',
	'Finland':'Finland',
	'Fiji':'Fiji',
	'Falkland Islands':'Falkland Islands',
	'France':'France',
	'Gabon':'Gabon',
	'United Kingdom':'United Kingdom',
	'Georgia':'Georgia',
	'Ghana':'Ghana',
	'Guinea':'Guinea',
	'Gambia':'Gambia',
	'Guinea Bissau':'Guinea Bissau',
	'Equatorial Guinea':'Equatorial Guinea',
	'Greece':'Greece',
	'Greenland':'Greenland',
	'Guatemala':'Guatemala',
	'French Guiana':'French Guiana',
	'Guyana':'Guyana',
	'Honduras':'Honduras',
	'Croatia':'Croatia',
	'Haiti':'Haiti',
	'Hungary':'Hungary',
	'Indonesia':'Indonesia',
	'India':'India',
	'Ireland':'Ireland',
	'Iran':'Iran',
	'Iraq':'Iraq',
	'Iceland':'Iceland',
	'Israel':'Israel',
	'Italy':'Italy',
	'Jamaica':'Jamaica',
	'Jordan':'Jordan',
	'Japan':'Japan',
	'Kazakhstan':'Kazakhstan',
	'Kenya':'Kenya',
	'Kyrgyzstan':'Kyrgyzstan',
	'Cambodia':'Cambodia',
	'South Korea':'South Korea',
	'Kosovo':'Kosovo',
	'Kuwait':'Kuwait',
	'Laos':'Laos',
	'Lebanon':'Lebanon',
	'Liberia':'Liberia',
	'Libya':'Libya',
	'Sri Lanka':'Sri Lanka',
	'Lesotho':'Lesotho',
	'Lithuania':'Lithuania',
	'Luxembourg':'Luxembourg',
	'Latvia':'Latvia',
	'Morocco':'Morocco',
	'Moldova':'Moldova',
	'Madagascar':'Madagascar',
	'Mexico':'Mexico',
	'Macedonia':'Macedonia',
	'Mali':'Mali',
	'Myanmar':'Myanmar',
	'Montenegro':'Montenegro',
	'Mongolia':'Mongolia',
	'Mozambique':'Mozambique',
	'Mauritania':'Mauritania',
	'Malawi':'Malawi',
	'Malaysia':'Malaysia',
	'Namibia':'Namibia',
	'New Caledonia':'New Caledonia',
	'Niger':'Niger',
	'Nigeria':'Nigeria',
	'Nicaragua':'Nicaragua',
	'Netherlands':'Netherlands',
	'Norway':'Norway',
	'Nepal':'Nepal',
	'New Zealand':'New Zealand',
	'Oman':'Oman',
	'Pakistan':'Pakistan',
	'Panama':'Panama',
	'Peru':'Peru',
	'Philippines':'Philippines',
	'Papua New Guinea':'Papua New Guinea',
	'Poland':'Poland',
	'Puerto Rico':'Puerto Rico',
	'North Korea':'North Korea',
	'Portugal':'Portugal',
	'Paraguay':'Paraguay',
	'Qatar':'Qatar',
	'Romania':'Romania',
	'Russia':'Russia',
	'Rwanda':'Rwanda',
	'Western Sahara':'Western Sahara',
	'Saudi Arabia':'Saudi Arabia',
	'Sudan':'Sudan',
	'South Sudan':'South Sudan',
	'Senegal':'Senegal',
	'Solomon Islands':'Solomon Islands',
	'Sierra Leone':'Sierra Leone',
	'El Salvador':'El Salvador',
	'Somaliland':'Somaliland',
	'Somalia':'Somalia',
	'Republic of Serbia':'Republic of Serbia',
	'Suriname':'Suriname',
	'Slovakia':'Slovakia',
	'Slovenia':'Slovenia',
	'Sweden':'Sweden',
	'Swaziland':'Swaziland',
	'Syria':'Syria',
	'Chad':'Chad',
	'Togo':'Togo',
	'Thailand':'Thailand',
	'Tajikistan':'Tajikistan',
	'Turkmenistan':'Turkmenistan',
	'East Timor':'East Timor',
	'Trinidad and Tobago':'Trinidad and Tobago',
	'Tunisia':'Tunisia',
	'Turkey':'Turkey',
	'United Republic of Tanzania':'United Republic of Tanzania',
	'Uganda':'Uganda',
	'Ukraine':'Ukraine',
	'Uruguay':'Uruguay',
	'United States of America':'United States of America',
	'Uzbekistan':'Uzbekistan',
	'Venezuela':'Venezuela',
	'Vietnam':'Vietnam',
	'Vanuatu':'Vanuatu',
	'West Bank':'West Bank',
	'Yemen':'Yemen',
	'South Africa':'South Africa',
	'Zambia':'Zambia',
	'Zimbabwe':'Zimbabwe'
};
map_countries_chart_option = {
	timeline: {
		axisType: 'category',
			orient: 'vertical',
			autoPlay: true,
			inverse: true,
			playInterval: 5000,
			left: null,
			right: -105,
			top: 0,
			bottom: 0,
			width: 46,
		data: ['2019',]  
	},
	baseOption: {
		visualMap: {
			min: 50,
			max: 5000,
			text: ['Max', 'Min'],
			realtime: false,
			calculable: true,
			
			inRange: {
				color: ['#fddd57', '#F5B240', '#fdae61', '#f46d43', '#d73027', '#a50026']
			}
		},
		series: [{
			type: 'map',
			map: 'world',
			zoom: 1.20,
			roam: true,
			nameMap: nameMap,
			itemStyle: {
				normal: {
					borderColor: '#bebebe',
				}
			},
		}]
	},
	
	options: [{
		series: {
			data: data,
		} 
	},]
};
ChartMapThreats = echarts.init(document.getElementById("chart-map-threats"));
map_countries_chart_option && ChartMapThreats.setOption(map_countries_chart_option);

<?php
$arraySort = [];
foreach(json_decode(trim(file_get_contents("/usr/local/www/active_protection/tentativas_invasao")),true)['data'] as $valuesReturn) {
    $arraySort[$valuesReturn['name']] = $valuesReturn['value'];
}

$counter = 1;
arsort($arraySort);

$returnTable = "";
foreach($arraySort as $key_now => $values_now) {
    $values_now = returnValueTable($values_now);
    $returnTable .= "<tr><td>{$counter}</td><td>{$key_now}</td><td>{$values_now}</td></tr>";
    if ($counter == 10) {
        break;
    }
    $counter++;
}
?>
$("#tentativasAcessosACP").html("<?=$returnTable?>");



data_response = "";
data = [];

data_response = JSON.parse('<?=trim(file_get_contents("/usr/local/www/active_protection/geo_ameacas_map"))?>');			
for (var item in data_response.data) {
    data.push({name: data_response.data[item].name, value: data_response.data[item].value});
}
	
nameMap = {
	'Afghanistan':'Afghanistan',
	'Singapore':'Singapore',
	'Angola':'Angola',
	'Albania':'Albania',
	'United Arab Emirates':'United Arab Emirates',
	'Argentina':'Argentina',
	'Armenia':'Armenia',
	'French Southern and Antarctic Lands':'French Southern and Antarctic Lands',
	'Australia':'Australia',
	'Austria':'Austria',
	'Azerbaijan':'Azerbaijan',
	'Burundi':'Burundi',
	'Belgium':'Belgium',
	'Benin':'Benin',
	'Burkina Faso':'Burkina Faso',
	'Bangladesh':'Bangladesh',
	'Bulgaria':'Bulgaria',
	'The Bahamas':'The Bahamas',
	'Bosnia and Herzegovina':'Bosnia and Herzegovina',
	'Belarus':'Belarus',
	'Belize':'Belize',
	'Bermuda':'Bermuda',
	'Bolivia':'Bolivia',
	'Brazil':'Brazil',
	'Brunei':'Brunei',
	'Bhutan':'Bhutan',
	'Botswana':'Botswana',
	'Central African Republic':'Central African Republic',
	'Canada':'Canada',
	'Switzerland':'Switzerland',
	'Chile':'Chile',
	'China':'China',
	'Ivory Coast':'Ivory Coast',
	'Cameroon':'Cameroon',
	'Democratic Republic of the Congo':'Democratic Republic of the Congo',
	'Republic of the Congo':'Republic of the Congo',
	'Colombia':'Colombia',
	'Costa Rica':'Costa Rica',
	'Cuba':'Cuba',
	'Northern Cyprus':'Northern Cyprus',
	'Cyprus':'Cyprus',
	'Czech Republic':'Czech Republic',
	'Germany':'Germany',
	'Djibouti':'Djibouti',
	'Denmark':'Denmark',
	'Dominican Republic':'Dominican Republic',
	'Algeria':'Algeria',
	'Ecuador':'Ecuador',
	'Egypt':'Egypt',
	'Eritrea':'Eritrea',
	'Spain':'Spain',
	'Estonia':'Estonia',
	'Ethiopia':'Ethiopia',
	'Finland':'Finland',
	'Fiji':'Fiji',
	'Falkland Islands':'Falkland Islands',
	'France':'France',
	'Gabon':'Gabon',
	'United Kingdom':'United Kingdom',
	'Georgia':'Georgia',
	'Ghana':'Ghana',
	'Guinea':'Guinea',
	'Gambia':'Gambia',
	'Guinea Bissau':'Guinea Bissau',
	'Equatorial Guinea':'Equatorial Guinea',
	'Greece':'Greece',
	'Greenland':'Greenland',
	'Guatemala':'Guatemala',
	'French Guiana':'French Guiana',
	'Guyana':'Guyana',
	'Honduras':'Honduras',
	'Croatia':'Croatia',
	'Haiti':'Haiti',
	'Hungary':'Hungary',
	'Indonesia':'Indonesia',
	'India':'India',
	'Ireland':'Ireland',
	'Iran':'Iran',
	'Iraq':'Iraq',
	'Iceland':'Iceland',
	'Israel':'Israel',
	'Italy':'Italy',
	'Jamaica':'Jamaica',
	'Jordan':'Jordan',
	'Japan':'Japan',
	'Kazakhstan':'Kazakhstan',
	'Kenya':'Kenya',
	'Kyrgyzstan':'Kyrgyzstan',
	'Cambodia':'Cambodia',
	'South Korea':'South Korea',
	'Kosovo':'Kosovo',
	'Kuwait':'Kuwait',
	'Laos':'Laos',
	'Lebanon':'Lebanon',
	'Liberia':'Liberia',
	'Libya':'Libya',
	'Sri Lanka':'Sri Lanka',
	'Lesotho':'Lesotho',
	'Lithuania':'Lithuania',
	'Luxembourg':'Luxembourg',
	'Latvia':'Latvia',
	'Morocco':'Morocco',
	'Moldova':'Moldova',
	'Madagascar':'Madagascar',
	'Mexico':'Mexico',
	'Macedonia':'Macedonia',
	'Mali':'Mali',
	'Myanmar':'Myanmar',
	'Montenegro':'Montenegro',
	'Mongolia':'Mongolia',
	'Mozambique':'Mozambique',
	'Mauritania':'Mauritania',
	'Malawi':'Malawi',
	'Malaysia':'Malaysia',
	'Namibia':'Namibia',
	'New Caledonia':'New Caledonia',
	'Niger':'Niger',
	'Nigeria':'Nigeria',
	'Nicaragua':'Nicaragua',
	'Netherlands':'Netherlands',
	'Norway':'Norway',
	'Nepal':'Nepal',
	'New Zealand':'New Zealand',
	'Oman':'Oman',
	'Pakistan':'Pakistan',
	'Panama':'Panama',
	'Peru':'Peru',
	'Philippines':'Philippines',
	'Papua New Guinea':'Papua New Guinea',
	'Poland':'Poland',
	'Puerto Rico':'Puerto Rico',
	'North Korea':'North Korea',
	'Portugal':'Portugal',
	'Paraguay':'Paraguay',
	'Qatar':'Qatar',
	'Romania':'Romania',
	'Russia':'Russia',
	'Rwanda':'Rwanda',
	'Western Sahara':'Western Sahara',
	'Saudi Arabia':'Saudi Arabia',
	'Sudan':'Sudan',
	'South Sudan':'South Sudan',
	'Senegal':'Senegal',
	'Solomon Islands':'Solomon Islands',
	'Sierra Leone':'Sierra Leone',
	'El Salvador':'El Salvador',
	'Somaliland':'Somaliland',
	'Somalia':'Somalia',
	'Republic of Serbia':'Republic of Serbia',
	'Suriname':'Suriname',
	'Slovakia':'Slovakia',
	'Slovenia':'Slovenia',
	'Sweden':'Sweden',
	'Swaziland':'Swaziland',
	'Syria':'Syria',
	'Chad':'Chad',
	'Togo':'Togo',
	'Thailand':'Thailand',
	'Tajikistan':'Tajikistan',
	'Turkmenistan':'Turkmenistan',
	'East Timor':'East Timor',
	'Trinidad and Tobago':'Trinidad and Tobago',
	'Tunisia':'Tunisia',
	'Turkey':'Turkey',
	'United Republic of Tanzania':'United Republic of Tanzania',
	'Uganda':'Uganda',
	'Ukraine':'Ukraine',
	'Uruguay':'Uruguay',
	'United States of America':'United States of America',
	'Uzbekistan':'Uzbekistan',
	'Venezuela':'Venezuela',
	'Vietnam':'Vietnam',
	'Vanuatu':'Vanuatu',
	'West Bank':'West Bank',
	'Yemen':'Yemen',
	'South Africa':'South Africa',
	'Zambia':'Zambia',
	'Zimbabwe':'Zimbabwe'
};
map_countries_chart_option = {
	timeline: {
		axisType: 'category',
			orient: 'vertical',
			autoPlay: true,
			inverse: true,
			playInterval: 5000,
			left: null,
			right: -105,
			top: 0,
			bottom: 0,
			width: 46,
		data: ['2019',]  
	},
	baseOption: {
		visualMap: {
			min: 50,
			max: 5000,
			text: ['Max', 'Min'],
			realtime: false,
			calculable: true,
			
			inRange: {
				color: ['#fddd57', '#F5B240', '#fdae61', '#f46d43', '#d73027', '#a50026']
			}
		},
		series: [{
			type: 'map',
			map: 'world',
			zoom: 1.20,
			roam: true,
			nameMap: nameMap,
			itemStyle: {
				normal: {
					borderColor: '#bebebe',
				}
			},
		}]
	},
	
	options: [{
		series: {
			data: data,
		} 
	},]
};
ChartMapThreats = echarts.init(document.getElementById("chart-map-threats-ameacas"));
map_countries_chart_option && ChartMapThreats.setOption(map_countries_chart_option);

<?php
$arraySort = [];
foreach(json_decode(trim(file_get_contents("/usr/local/www/active_protection/geo_ameacas_map")),true)['data'] as $valuesReturn) {
    $arraySort[$valuesReturn['name']] = $valuesReturn['value'];
}

$counter = 1;
arsort($arraySort);

$returnTable = "";
foreach($arraySort as $key_now => $values_now) {
    $values_now = returnValueTable($values_now);
    $returnTable .= "<tr><td>{$counter}</td><td>{$key_now}</td><td>{$values_now}</td></tr>";
    if ($counter == 10) {
        break;
    }
    $counter++;
}
?>
$("#tabelaAmeacasRecentesACP").html("<?=$returnTable?>");


protocols = [];
values = [];
tabletBase = "";
counterTable=1;
counter=0;

valuesJson = JSON.parse('<?=getValuesByColumn("filtro_log_report.csv", 1, 5)?>');
for (var key in valuesJson) {
    protocols.push(key);
    values.push(valuesJson[key]);
    if (counter == 4) {
        break;
    } else {
        counter++;
    }
}

for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}

$("#qtdProtocolfilterLOGTable").html(tabletBase);
chartDom = document.getElementById('qtdProtocolfilterLOG');
myChart = echarts.init(chartDom);
option = '';
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
				

protocols = [];
values = [];
tabletBase = "";
counterTable=1;
counter=0;

valuesJson = JSON.parse('<?=getValuesByColumn("filtro_acp_report.csv", 2, 6)?>');
for (var key in valuesJson) {
    protocols.push(key);
    values.push(valuesJson[key]);
    if (counter == 4) {
        break;
    } else {
        counter++;
    }
}

for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}

$("#qtdProtocolfilterACPTable").html(tabletBase);
chartDom = document.getElementById('qtdProtocolfilterACP');
myChart = echarts.init(chartDom);
option = '';
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


interfaces = [];
values = [];
counterTable = 1;
counter = 0;
tabletBase = "";

valuesJson = JSON.parse('<?=getValuesByColumn("filtro_log_report.csv", 1, 4)?>');
for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}

for (var key in valuesJson) {
    interfaces.push(key);
    values.push(valuesJson[key]);
    if (counter == 4) {
        break;
    } else {
        counter++;
    }
}

$("#qtdInterfacefilterLOGTable").html(tabletBase);

chartDom = document.getElementById('qtdInterfacefilterLOG');
myChart = echarts.init(chartDom);
option = '';
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


counter = 0;
qtTotais = 0;
series_teste = [];
valuesJson = JSON.parse('<?=str_replace("'","",getValuesByColumn("filtro_acp_report.csv", 2, 3))?>');
for (var key in valuesJson) {
    qtTotais = valuesJson[key];
    series_teste.push({
        label: {
            show: true,
            position: 'inside'
        },
		name: resumeDescrKey(counter, key),
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
tabletBase = "";
counterTable = 1;
for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}
$("#qtdTableAccessRulefilterACP").html(tabletBase);
chartDom = document.getElementById('qtdAccessRulefilterACP');
myChart = echarts.init(chartDom);
option = '';
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




counter = 0;
qtTotais = 0;
series_teste = [];
valuesJson = JSON.parse('<?=getValuesByColumn("filtro_acp_report.csv", 2, 7)?>');
for (var key in valuesJson) {
    qtTotais = valuesJson[key];
    series_teste.push({
        label: {
            show: true,
            position: 'inside'
        },
		name: resumeDescrKey(counter, key),
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
values = [];
tabletBase = "";
counterTable = 1;
for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}
$("#qtdTableIPAccessExternalfilterACP").html(tabletBase);
chartDom = document.getElementById('qtdIPAccessExternalfilterACP');
myChart = echarts.init(chartDom);
option = '';
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




counter = 0;
qtTotais = 0;
series_teste = [];
valuesJson = JSON.parse('<?=getValuesByColumn("filtro_acp_report.csv", 2, 9)?>');
for (var key in valuesJson) {
    qtTotais = valuesJson[key];
    series_teste.push({
        label: {
            show: true,
            position: 'inside'
        },
		name: resumeDescrKey(counter, key),
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
tabletBase = "";
counterTable = 1;
for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}
$("#qtdTableIPInternalfilterACP").html(tabletBase);
chartDom = document.getElementById('qtdIPInternalfilterACP');
myChart = echarts.init(chartDom);
option = '';
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



counter = 0;
series_teste = [];
valuesJson = JSON.parse('<?=getValuesByColumn("filtro_acp_report.csv", 2, 8, true, false, true)?>');
for (var key in valuesJson) {
    
    series_teste.push({
        label: {
            show: true,
            position: 'inside'
        },
		name: resumeDescrKey(counter, key.split("P_")[1]),
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
tabletBase = "";
counterTable = 1;
for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key.split("P_")[1] + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}
$("#qtdTablePortExternalfilterACP").html(tabletBase);
chartDom = document.getElementById('qtdPortExternalfilterACP');
myChart = echarts.init(chartDom);
option = '';
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



counter = 0;
qtTotais = 0;
series_teste = [];
valuesJson = JSON.parse('<?=getValuesByColumn("filtro_acp_report.csv", 2, 10, true, false, true)?>');
for (var key in valuesJson) {
    qtTotais = valuesJson[key];
    series_teste.push({
        label: {
            show: true,
            position: 'inside'
        },
		name: resumeDescrKey(counter, key.split("P_")[1]),
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
tabletBase = "";
counterTable = 1;
for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key.split("P_")[1] + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}
$("#qtdTablePortInternalfilterACP").html(tabletBase);
chartDom = document.getElementById('qtdPortInternalfilterACP');
myChart = echarts.init(chartDom);
option = '';
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


interfaces = [];
values = [];
tabletBase = "";
counter = 0;
counterTable = 1;
valuesJson = JSON.parse('<?=getValuesByColumn("filtro_acp_report.csv", 2, 1)?>');
for (var key in valuesJson) {
    interfaces.push(key);
    values.push(valuesJson[key]);
    if (counter == 4) {
        break;
    } else {
        counter++;
    }
}

valuesJson = JSON.parse('<?=getValuesByColumn("filtro_acp_report.csv", 2, 1, true, false, false)?>');
for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}

$("#qtdAccessTimeLineACPTable").html(tabletBase);

chartDom = document.getElementById('qtdAccessTimeLineACP');
myChart = echarts.init(chartDom);
option = '';
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



interfaces = [];
values = [];
tabletBase = "";
counter = 0;
counterTable = 1;
valuesJson = JSON.parse('<?=getValuesByColumn("filtro_log_report.csv", 1, 2)?>');
for (var key in valuesJson) {
    interfaces.push(key);
    values.push(valuesJson[key]);
    if (counter == 4) {
        break;
    } else {
        counter++;
    }
}

valuesJson = JSON.parse('<?=getValuesByColumn("filtro_log_report.csv", 1, 2, true, false, false)?>');
for (var key in valuesJson) {
    tabletBase += "<tr><td>" + counterTable + "</td><td>" + key + "</td><td>" + explodeNumbersShowTable(valuesJson[key]) + "</td></tr>";
    if (counterTable == 10) {
        break;
    } else {
        counterTable++;
    }
}

$("#qtdAccessTimeLineLOGTable").html(tabletBase);


chartDom = document.getElementById('qtdAccessTimeLineLOG');
myChart = echarts.init(chartDom);
option = '';
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

qtdPages = 0;
textReturn = "";

for(counterPages=0;counterPages <= document.getElementsByTagName("page").length-1; counterPages++) {
    qtdPages++;
}

for(counterPages=0;counterPages <= document.getElementsByTagName("page").length-1; counterPages++) {
    if (!!document.getElementsByTagName("page")[counterPages].getElementsByClassName("counterPage")[0]) {
        document.getElementsByTagName("page")[counterPages].getElementsByClassName("counterPage")[0].innerText = parseInt(parseInt(counterPages)+1) + "/" + qtdPages;
    }
}

</script>
