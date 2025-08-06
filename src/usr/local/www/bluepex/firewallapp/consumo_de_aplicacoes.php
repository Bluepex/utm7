<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Alexandre Morais da Costa <alexandre.costa@bluepex.com>, 2021
 *
 * ====================================================================
 *
 */

// Message to display if the session times out and an AJAX call is made
$timeoutmessage = gettext("The dashboard web session has timed out.\\n" .
	"It will not update until you refresh the page and log-in again.");

// Turn on buffering to speed up rendering
ini_set('output_buffering', 'true');

// Start buffering with a cache size of 100000
ob_start(null, "1000");

## Load Essential Includes
require_once('guiconfig.inc');
require_once('functions.inc');
require_once('notices.inc');
require_once("pkg-utils.inc");
require("config.inc");
require_once("captiveportal.inc");
require_once("bluepex/firewallapp_webservice.inc");
require_once("bluepex/firewallapp.inc");

$pgtitle = array(gettext("FirewallApp"), gettext("Application Consumption"));

include("head.inc");


$json_data_fapp = json_decode('{"access_all":"0","access_alerts":"0","access_drop":"0","facebook":"0","tiktok":"0","instagram":"0","whatsapp":"0","telegram":"0","twitter":"0","linkedin":"0","microsoft":"0","uol":"0","google":"0","g1":"0","amazon":"0","yahoo":"0","primevideo":"0","netflix":"0","youtube":"0","disney":"0","twitch":"0","deezer":"0","amazonmusic":"0","spotify":"0","teamviewer":"0","anydesk":"0"}');
if (file_exists("/usr/local/www/fapp_data.json")) {
    $json_data_fapp = json_decode(file_get_contents("/usr/local/www/fapp_data.json"));
}

$json_data_fapp_services = json_decode('{"www.whatsapp.com":"False","www.tiktok.com":"False","www.instagram.com":"False","www.facebook.com":"False","www.telegram.com":"False","www.twitter.com":"False","news.microsoft.com":"False","www.linkedin.com":"False","www.uol.com":"False","www.google.com":"False","www.amazon.com":"False","www.primevideo.com":"False","www.disneyplus.com":"False","www.youtube.com":"False","www.deezer.com":"False","www.twitch.tv":"False","music.amazon.com":"False","www.spotify.com":"False","www.anydesk.com":"False","www.teamviewer.com":"False","www.amazon.com":"False","www.netflix.com":"False","www.yahoo.com":"False","www.g1.com":"False","www.teste.com":"False","0":"0"}"');
if (file_exists("/tmp/categorias/status_services_gerais.status.services")) {
    $json_data_fapp_services = json_decode(file_get_contents("/tmp/categorias/status_services_gerais.status.services"));
}

init_config_arr(array('gateways', 'gateway_item'));
$a_gtw = &$config['gateways']['gateway_item'];
$a_gateways = return_gateways_array(true, false, true, true);

// Get list of configured firewall interfaces
$ifaces = get_configured_interface_list();

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

function getInterfaceFAPP() {

    init_config_arr(array('installedpackages', 'suricata', 'rule'));

    $a_instance = &$config['installedpackages']['suricata']['rule'];
    $a_rule = &$config['installedpackages']['suricata']['rule'];

    $suricata_uuid = $a_instance[$instanceid]['uuid'];
    $if_real = get_real_interface($a_instance[$instanceid]['interface']);

    global $g, $config;

    global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

    $ret = '';

    if (!is_array($config['installedpackages']['suricata']['rule']))
    $config['installedpackages']['suricata']['rule'] = array();

    $a_rule = &$config['installedpackages']['suricata']['rule'];

    for ($id = 0; $id <= count($a_rule)-1; $id++) {

        $if_real = get_real_interface($a_rule[$id]['interface']);

        $suricata_uuid = $a_rule[$id]['uuid'];

        foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
            $if = get_real_interface($suricatacfg['interface']);
            $uuid = $suricatacfg['uuid'];

            if (!in_array($if, $all_gtw,true)) {
            //if ($suricatacfg['interface'] != 'wan') {
                    $ret = "suricata_".$if.$uuid;
            }
        }

    }

    return $ret;

}


if ($savemsg) {
	print_info_box($savemsg, $class);
}

pfSense_handle_custom_code("/usr/local/pkg/dashboard/pre_dashboard");

$status_iface = getStatusNewFapp();

$status_iface2 = getInterfaceNewFapp();

if (($status_iface >= 1) && ($status_iface2 >= 1))  {

?>
<style>
div.progress-bar.bg-info2 {
    color: black;
}
div.progress {
    width:90% !important;
}
button.interface_target {
    text-transform: uppercase;
    margin-top: 10px;
    padding-left: 50px;
    padding-right: 50px;
    border-radius: 5px;
    width: 100%;
}
ul.gear-firewallapp {
    list-style-type: none;
    text-align: end;
}
#table-access th {
    background: #108ad0;
    color: #fff;
    text-align: center;
}
#Detail-Access .table-bordered td {
    background-color: unset !important;
}
div#aplication-details {
    margin-bottom: 20px;
}
</style>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
            <p><?=gettext("The panels on this page are intended to easily report current network usage based on traffic analyzed by FirewallApp.")?></p>
            <p><?=gettext("The page is operating with constant updates, therefore, the values ​​informed will always be the most updated in the use of the service.")?></p>
            <hr>
            <p style='color:red'><?=gettext("OBS: Due to the possible large amount of information to be analyzed, the \"Top Consumption\" and \"Access in Real Time\" tabs have a limit of information to be presented:")?></p>
            <ul style='color:red'>
                <li><b><?=gettext("Top Consumption")?></b>: <?=gettext("Limited to the presentation of last minute traffic information, which if not found, the last interface records will be used;")?></li>
                <li><b><?=gettext("Real-Time Access - (Top 5 - Most Accessed Applications)")?></b>: <?=gettext("Limited to the presentation of traffic information from the last 5 minutes, which if not found, the last records of the interface will be used;")?></li>
            </ul>
        </div>
    </div>
</div>

<div class="col-12">
    <div class="row">
        <div class="col-md-12 mt-5 pt-2">
            <!-- Conteúdo -->
            <div id="aplication-details">
                <div>
                    <ul class="gear-firewallapp">					
                        <li>
                            <a href="services.php" title="Perfil"><i class="fa fa-gear"></i></a>
                        </li>
                    </ul>

                    <center><h3 class="color-blue"><?=gettext("CONSUMPTION OF APPLICATIONS AND NAVIGATION DETAILS")?></h3></center>
                    
                </div>
                <hr>
                <ul class="nav nav-pills mb-3 nav-fill" id="pills-tab" role="tablist">
                    <li class="nav-item">
                    <a class="nav-link active" id="pills-home-tab" data-toggle="pill" href="#pills-home" role="tab" aria-controls="pills-home" aria-selected="true"><?=gettext("Top Consumption - Applications")?></a>
                    </li>
                    <li class="nav-item">
                    <a class="nav-link" id="pills-profile-tab" data-toggle="pill" href="#pills-profile" role="tab" aria-controls="pills-profile" aria-selected="false"><?=gettext("Real Time Access")?></a>
                    </li>
                </ul>
                <div class="tab-content" id="pills-tabContent">
                    <!-- 1ª ABA -->
                    <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">
                        <div class="col-12 px-0">
                            <div class="bg-gray-2 p-2">
                                <div class="col-12">
                                    <div class="bg-white padding-15 margin-bottom-20 border-left-5 margins-content-top" id="category-consumer">
                                        <h4 class="text-center margins-content-bottom"><?=gettext("SELECT INTERFACE")?></h4>
                                        <div class="col-12 text-center margins-content-bottom">
                                        <?php
                                        $contador = 0;
                                        foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
                                            if ($suricatacfg['enable'] == "on") {
                                                if (suricata_is_running($suricatacfg['uuid'], get_real_interface($suricatacfg['interface']))) {
                                                    $if = get_real_interface($suricatacfg['interface']);
                                                    if (!in_array($if, $all_gtw,true)) {
                                                    //if ($suricatacfg['interface'] != 'wan') {
                                                        echo "<button type='click' class='btn btn-primary weight-600 interface_target' id='interface-btn-" . $contador . "' value='" . $contador . "' onclick='set_variable_interface(" . $contador . ")'>" . $suricatacfg['descr'] . "</button>";
                                                    } 
                                                } else {
                                                    $if = get_real_interface($suricatacfg['interface']);
                                                    if (!in_array($if, $all_gtw,true)) {
                                                    //if ($suricatacfg['interface'] != 'wan') {
                                                        echo "<button type='click' class='btn btn-warning weight-600 interface_target' id='interface-btn-" . $contador . "' value='" . $contador . "' onclick='set_variable_interface(" . $contador . ")' disabled>" . $suricatacfg['descr'] . "</button>";    
                                                    } 
                                                }
                                            }
                                            $contador++;
                                        }
                                        ?>
                                        </div> 
                                    </div>
                                    <div class="bg-white padding-15 margin-bottom-20 border-left-5 margins-content-top" id="category-consumer">
                                        <h4 class="text-center margins-content-bottom"><?=gettext("CONSUMPTION BY CATEGORY")?></h4>
                                        <div class="col-12 text-center">
                                            <div class="row">
                                                <div class="col-12 col-sm">
                                                    <h1 id="redesocial-top" class="weight-700 color-medium">0%</h1>
                                                    <h4 class="weight-700"><?=gettext("Social networks")?></h4>
                                                    <!--<p class="weight-600 color-text-second">Tráfego Total: <span class="weight-700">250MB</span></p>-->
                                                    <hr>
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <h1 id="portais-top" class="weight-700 color-medium">0%</h1>
                                                    <h4 class="weight-700"><?=gettext("Portals")?></h4>
                                                    <!--<p class="weight-600 color-text-second">Tráfego Total: <span class="weight-700">250MB</span></p>-->
                                                    <hr>
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <h1  id="stream-top" class="weight-700 color-medium">0%</h1>
                                                    <h4 class="weight-700"><?=gettext("Streaming")?></h4>
                                                    <!--<p class="weight-600 color-text-second">Tráfego Total: <span class="weight-700">250MB</span></p>-->
                                                    <hr>
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <h1 id="musica-top" class="weight-700 color-low">0%</h1>
                                                    <h4 class="weight-700"><?=gettext("Music")?></h4>
                                                    <!--<p class="weight-600 color-text-second">Tráfego Total: <span class="weight-700">250MB</span></p>-->
                                                    <hr>
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <h1  id="remoto-top"class="weight-700 color-low">0%</h1>
                                                    <h4 class="weight-700"><?=gettext("Remote access")?></h4>
                                                    <!--<p class="weight-600 color-text-second">Tráfego Total: <span class="weight-700">250MB</span></p>-->
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <h1  id="outros-top"class="weight-700 color-low">0%</h1>
                                                    <h4 class="weight-700"><?=gettext("Others")?></h4>
                                                    <!--<p class="weight-600 color-text-second">Tráfego Total: <span class="weight-700">250MB</span></p>-->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 px-0 margins-content margins-content-bottom">
                                        <div class="card-border">
                                            <h2 class="text-color-blue weight-600 text-center title-desc-aplication"><?=gettext("CONSUMPTION DETAILS - APPLICATION")?></h2>
                                            <!--<div class="col-12 text-center margins-content-bottom">
                                                <button type="button" class="btn btn-primary weight-600" id="btn-aplications"><?=gettext("Applications")?></button>                                            
                                                <!--<button type="button" class="btn btn-primary weight-600" id="btn-traffic"><?=gettext("Top 10 - Consumption")?></button>
                                                <button type="button" class="btn btn-primary weight-600" id="btn-ips"><?=gettext("Top IPs")?></button
                                            </div>-->
                                            <div class="col-12" id="box-aplications-percent">
                                                <div class="row">
                                                    <div class="col-md-6 margins-content-bottom">
                                                        <h4 class="margins-content-top"><?=gettext("Social networks")?></h4>
                                                        <div class="bg-white padding-15 border-left-5 size-height-box-primary">
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
								    <div class="col-2 text-center">
									<img src="images/icon-facebook.png" alt="Facebook" data-toggle="tooltip" data-placement="top" title="Facebook">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="facebook-graph"  class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.facebook.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_facebook" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-facebook"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'facebook'}; ?> </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-tiktok.png" alt="TikTok" data-toggle="tooltip" data-placement="top" title="Tiktok">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="tiktok-graph"  class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.tiktok.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_tiktok" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-tiktok"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'tiktok'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-instagram.png" alt="Instagram" data-toggle="tooltip" data-placement="top" title="Instagram">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="instagram-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.instagram.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_instagram" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-instagram"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'instagram'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-whatsapp.png" alt="WhatsApp" data-toggle="tooltip" data-placement="top" title="WhatsApp">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="whatsapp-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.whatsapp.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_whatsapp" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-whatsapp"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'whatsapp'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-telegram.png" alt="Telegram" data-toggle="tooltip" data-placement="top" title="Telegram">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="telegram-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.telegram.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_telegram" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-telegram"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'telegram'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-twitter.png" alt="Twitter" data-toggle="tooltip" data-placement="top" title="Twitter">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="twitter-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.twitter.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_twitter" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-twitter"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'twitter'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-linkedin.png" alt="Linkedin" data-toggle="tooltip" data-placement="top" title="Linkedin">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="linkedin-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.linkedin.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_linkedin" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-linkedin"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'linkedin'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6 margins-content-bottom">
                                                        <h4 class="margins-content-top"><?=gettext("Portals")?></h4>
                                                        <div class="bg-white padding-15 border-left-5 size-height-box-primary">
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-microsoft.png" alt="Microsoft" data-toggle="tooltip" data-placement="top" title="Microsoft">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="microsoft-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">     
                                                                                <i id="news.microsoft.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_news_microsoft" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-microsoft"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'microsoft'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-uol.png" alt="Uol" data-toggle="tooltip" data-placement="top" title="Uol">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="uol-graph"  class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.uol.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_uol" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-uol"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'uol'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="social-google col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-google.png" alt="Google" data-toggle="tooltip" data-placement="top" title="Google">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="google-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.google.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_google" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-google"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'google'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-g1.png" alt="G1" data-toggle="tooltip" data-placement="top" title="G1">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="globo-g1-graph"  class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.g1.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_g1" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-g1"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'g1'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-amazon.png" alt="Amazon" data-toggle="tooltip" data-placement="top" title="Amazon">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="amazon-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.amazon.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_amazon" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-amazon"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'amazon'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-yahoo.png" alt="Yahoo" data-toggle="tooltip" data-placement="top" title="Yahoo">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="yahoo-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.yahoo.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_yahoo" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-yahoo"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'yahoo'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    

                                                    <div class="col-md-6 margins-content-bottom">
                                                        <h4 class="margins-content-top"><?=gettext("Streaming")?></h4>
                                                        <div class="bg-white padding-15 border-left-5 size-height-box-secondary">
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-primevideo.png" alt="Prime Video" data-toggle="tooltip" data-placement="top" title="Prime Video">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="primevideo-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.primevideo.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_primevideo" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-primevideo"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'primevideo'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-netflix.png" alt="Netflix" data-toggle="tooltip" data-placement="top" title="Netflix">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="netflix-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.netflix.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_netflix" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-netflix"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'netflix'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-youtube.png" alt="Youtube" data-toggle="tooltip" data-placement="top" title="Youtube">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="youtube-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.youtube.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_youtube" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-youtube"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'youtube'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-disneyplus.png" alt="Disney Plus" data-toggle="tooltip" data-placement="top" title="Disney Plus">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="disney-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.disneyplus.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_disneyplus" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-disney"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'disney'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-twitch.png" alt="Twitch" data-toggle="tooltip" data-placement="top" title="Twitch">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="twitch-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.twitch.tv" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_twitch" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-twitch"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'twitch'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6 margins-content-bottom">
                                                        <h4 class="margins-content-top"><?=gettext("Music")?></h4>
                                                        <div class="bg-white padding-15 border-left-5 size-height-box-secondary">
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-deezer.png" alt="Deezer" data-toggle="tooltip" data-placement="top" title="Deezer">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="deezer-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.deezer.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_deezer" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-deezer"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'deezer'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-amazonmusic.png" alt="Amazon Music" data-toggle="tooltip" data-placement="top" title="Amazon Music">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="amazonmusic-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="music.amazon.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_music_amazon" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-amazonmusic"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'amazonmusic'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-spotify.png" alt="Spotify" data-toggle="tooltip" data-placement="top" title="Spotify">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="spotify-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.spotify.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_spotify" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-spotify"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'spotify'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6 margins-content-bottom">
                                                        <h4 class="margins-content-top"><?=gettext("Remote access")?></h4>
                                                        <div class="bg-white padding-15 border-left-5 margin-bottom-20">
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-teamviewer.png" alt="Team Viewer" data-toggle="tooltip" data-placement="top" title="Team Viewer">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="teamviewer-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.teamviewer.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_teamviewer" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-teamviewer"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'teamviewer'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/icon-anydesk.png" alt="Any Desk" data-toggle="tooltip" data-placement="top" title="Any Desk">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="anydesk-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.anydesk.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_anydesk" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-anydesk"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'anydesk'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6 margins-content-bottom">
                                                        <h4 class="margins-content-top"><?=gettext("Others")?></h4>
                                                        <div class="bg-white padding-15 border-left-5 margin-bottom-20">
                                                            <div class="col-12 margin-bottom-20">
                                                                <div class="row">
                                                                    <div class="col-2 text-center">
                                                                        <img src="images/fapp_img1.png" style="width: 28px;" alt="Outros" data-toggle="tooltip" data-placement="top" title="Outros">
                                                                    </div>
                                                                    <div class="col-10">
                                                                        <div style="display:flex;">
                                                                            <div class="progress">
                                                                                <div id="outros-graph" class="progress-bar bg-info2" style="width:0%;">0%</div>
                                                                            </div>
                                                                            <div style="display:flex;">
                                                                                <i id="www.teste.com" class='' style='margin-left: 10px;' title=''></i>
                                                                                <i id="qos_sites" class='' style='margin-left: 10px;' title=''></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="weight-600" id="access-instagram"><?=gettext("Access")?>: <?php echo $json_data_fapp->{'instagram'}; ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                            <!--Nao estao em uso no momento-->
                                            <!-- Content Traffic - MB
                                            <div class="col-12 no-display" id="box-top-traffic">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h4 class="text-center bottom-50"><?=gettext("Top 10 - Traffic Consumption")?></h4>
                                                        <div class="bottom-50" id="chart-traffic" style="width:100%; height:350px;"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            -->

                                            <!-- Content IPs 
                                            <div class="col-12 no-display" id="box-top-ips">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h4 class="text-center bottom-50"><?=gettext("Top 10 - Consumption by IP")?></h4>
                                                        <div class="bottom-50" id="chart-ips" style="width:100%; height:350px;"></div>
                                                    </div>
                                                </div>
                                            </div>
                                            -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Fim 1ª ABA -->
                    <?php  $list_top5 = explode(",",file_get_contents("/usr/local/www/list_top5")); $list_top_val_top5 = explode(",",file_get_contents("/usr/local/www/list_top_val_top5")); ?>
                    <!-- 2ª ABA -->
                    <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab">
                        <div class="col-12 px-0" id="Detail-Access">
                            <div class="bg-gray-2 p-2">
                                <div class="col-12">
                                    <div class="bg-white padding-15 margin-bottom-20 margins-content-top" id="descriptions-top-aplications">
                                        <h4 class="text-center margins-content-bottom"><?=gettext("TOP 5 - MOST ACCESSED APPLICATIONS")?></h4>
                                        <div class="col-12 text-center margins-content-top">
                                            <div class="row">
                                                <div class="col-12 col-sm">
                                                    <?php
                                                    $file = "/usr/local/www/firewallapp/images/icon-" . $list_top5[0] . ".png";
                                                    if (file_exists($file)) { ?>
                                                        <img src="images/icon-<?=$list_top5[0]?>.png" id="list_top_val_top5_img_1">
                                                    <?php } else { ?>
                                                        <img src="images/icon-www.png" id="list_top_val_top5_img_1">
                                                    <?php } ?>
                                                    <h4 class="weight-700" id="list_top_val_top5_text_1"><?=$list_top5[0]?></h4>
                                                    <p class="weight-600 color-text-second" id="list_top_val_top5_counter_1">(<?=$list_top_val_top5[0]?>)</p>
                                                    <hr>
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <?php
                                                    $file = "/usr/local/www/firewallapp/images/icon-" . $list_top5[1] . ".png";
                                                    if (file_exists($file)) { ?>
                                                        <img src="images/icon-<?=$list_top5[1]?>.png" id="list_top_val_top5_img_2">
                                                    <?php } else { ?>
                                                        <img src="images/icon-www.png" id="list_top_val_top5_img_2">
                                                    <?php } ?>
                                                    <h4 class="weight-700" id="list_top_val_top5_text_2"><?=$list_top5[1]?></h4>
                                                    <p class="weight-600 color-text-second" id="list_top_val_top5_counter_2">(<?=$list_top_val_top5[1]?>)</p>
                                                    <hr>
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <?php
                                                    $file = "/usr/local/www/firewallapp/images/icon-" . $list_top5[2] . ".png";
                                                    if (file_exists($file)) { ?>
                                                        <img src="images/icon-<?=$list_top5[2]?>.png" id="list_top_val_top5_img_3">
                                                    <?php } else { ?>
                                                        <img src="images/icon-www.png" id="list_top_val_top5_img_3">
                                                    <?php } ?>
                                                    <h4 class="weight-700" id="list_top_val_top5_text_3"><?=$list_top5[2]?></h4>
                                                    <p class="weight-600 color-text-second" id="list_top_val_top5_counter_3">(<?=$list_top_val_top5[2]?>)</p>
                                                    <hr>
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <?php
                                                    $file = "/usr/local/www/firewallapp/images/icon-" . $list_top5[3] . ".png";
                                                    if (file_exists($file)) { ?>
                                                        <img src="images/icon-<?=$list_top5[3]?>.png" id="list_top_val_top5_img_4">
                                                    <?php } else { ?>
                                                        <img src="images/icon-www.png" id="list_top_val_top5_img_4">
                                                    <?php } ?>
                                                    <h4 class="weight-700" id="list_top_val_top5_text_4"><?=$list_top5[3]?></h4>
                                                    <p class="weight-600 color-text-second" id="list_top_val_top5_counter_4">(<?=$list_top_val_top5[3]?>)</p>
                                                    <hr>
                                                </div>
                                                <div class="col-12 col-sm">
                                                    <?php
                                                    $file = "/usr/local/www/firewallapp/images/icon-" . $list_top5[4] . ".png";
                                                    if (file_exists($list_top5[4])) { ?>
                                                        <img src="images/icon-<?=$list_top5[4]?>.png" id="list_top_val_top5_img_5">
                                                    <?php } else { ?>
                                                        <img src="images/icon-www.png" id="list_top_val_top5_img_5">
                                                    <?php } ?>
                                                    <h4 class="weight-700" id="list_top_val_top5_text_5"><?=$list_top5[4]?></h4>
                                                    <p class="weight-600 color-text-second" id="list_top_val_top5_counter_5">(<?=$list_top_val_top5[4]?>)</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12" id="cards-informations-access">
                                    <div class="row">
                                        <div class="col-md-4 text-center">
                                            <div class="bg-white padding-15 border-left-5" id="card-1">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-sm-4 px-0 border-outline-right" id="information-real-time">
                                                            <h2 class="weight-600 information-number" id="access_all"><?php echo $json_data_fapp->{'access_all'}; ?></h2>
                                                        </div>
                                                        <div class="col-sm-8 weight-600 px-0">
                                                            <img src="images/icon-clock.png" class="margin-bottom-5">
                                                            <h4><?=gettext("Access (Real Time)")?></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div class="bg-white padding-15 border-left-5" id="card-2">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-sm-4 px-0" id="information-access-block">
                                                            <h2 class="weight-600 information-number" id="access_drop"><?php echo $json_data_fapp->{'access_drop'}; ?></h2>
                                                        </div>
                                                        <div class="col-sm-8 weight-600 px-0">
                                                            <img src="images/icon-block.png" class="margin-bottom-5">
                                                            <h4><?=gettext("Blocked Access")?></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div class="bg-white padding-15 border-left-5" id="card-3">
                                                <div class="col-12">
                                                    <div class="row">
                                                        <div class="col-sm-4 px-0" id="information-access-unlock">
                                                            <h2 class="weight-600 information-number" id="access_alerts"><?php echo $json_data_fapp->{'access_alerts'}; ?></h2>
                                                        </div>
                                                        <div class="col-sm-8 weight-600 px-0">
                                                            <img src="images/icon-check.png" class="margin-bottom-5">
                                                            <h4><?=gettext("Released Accesses")?></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 margins-content">
                                    <h4 class="text-color-blue"><?=gettext("ACCESS DETAIL - REAL TIME")?></h4>
                                    <hr>
                                    <div class="col-12 px-0 bottom-50 margins-content-top" id="table-details-access">
                                        <div class="Access-table">
                                            <select style="float:right; width:150px; height: 28px; margin-left: 5px; margin-right: 20px;" id="firewallapp_interface" class="form-control"> <?=gettext("Instances")?>:
                                                <?php
                                                    foreach ($config['installedpackages']['suricata']['rule'] as $key => $interface) {
                                                    $if = get_real_interface($interface['interface']);
                                                    if (!in_array($if, $all_gtw,true)) {
                                                    //if ($interface['interface'] != 'wan') {
                                                ?>
                                                <option value="<?=$key?>"><?=$interface['descr']?></option>
                                                <?php } } ?>
                                            </select>
                                            <input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:200px;" id="search-firewall-app" onkeydown="searchData()" onkeyup="searchData()" placeholder="<?=gettext("Search for...")?>">
                                            <div class="container col-sm-12 pl-0 table-responsive" style="height:218px;margin-bottom:20px;">
                                                <table id="table-access" class="table table-striped table-bordered-alerts">
                                                    <thead>
                                                        <tr>
                                                            <th><?=gettext("Date/Time")?></th>
                                                            <th><?=gettext("Application")?></th>
                                                            <th><?=gettext("IP")?></th>
                                                            <th><?=gettext("Username")?></th>
                                                            <th><?=gettext("Status")?></th>
                                                            <th><?=gettext("Group")?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="geral-table">
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div><br>
                                    </div><br><br>
                                    <h4 class="text-color-blue"><?=gettext("HTTPS INSPECTION")?></h4>
                                    <hr>
                                    <div class="col-12 px-0 margins-content-top" id="table-https">
                                        <div class="col-12 px-0 bottom-50" id="table-details-access-https">
                                            <div class="table-responsive" style="height:218px;">
                                                <table class="table table-striped table-bordered-alerts">
                                                    <thead>
                                                        <tr>
                                                            <th style="background: #177bb4;"><?=gettext("Date/Time")?></th>
                                                            <th style="background: #177bb4;"><?=gettext("Server Name")?></th>
                                                            <th style="background: #177bb4;"><?=gettext("Source")?></th>
                                                            <th style="background: #177bb4;" class="text-center"><?=gettext("Destination")?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tls-table">
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div><br><br>
                                    <h4 class="text-color-blue"><?=gettext("HTTP INSPECTION")?></h4>
                                    <hr>
                                    <div class="col-12 px-0 margins-content-top" id="table-http">
                                        <div class="col-12 px-0 bottom-50" id="table-details-access-http">
                                            <div class="table-responsive" style="height:218px;">
                                                <table class="table table-striped table-bordered-alerts">
                                                    <thead>
                                                        <tr>
                                                            <th style="background: #177bb4;"><?=gettext("Date/Time")?></th>
                                                            <th style="background: #177bb4;"><?=gettext("Host")?></th>
                                                            <th style="background: #177bb4;"><?=gettext("Source")?></th>
                                                            <th style="background: #177bb4;" class="text-center"><?=gettext("Destination")?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="http-table">
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Fim 2ª ABA -->
                </div>
            <!-- Conteúdo -->
            </div>
        </div>
    </div>
</div>

<?php } else { ?>

    <style type="text/css">

    #header-licenses-information { min-height: 165px; margin-bottom: 65px; background:url(../images/bg-header.png) no-repeat; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;}
    #description-information h4 {color: #007dc5;}
    #description-information h6 {color: #333; background-color: #efefef; padding: 12px 55px; font-size: 1.4em;}
    #information-support {margin: 0 auto;}
    #footer { position: absolute; right: 0; bottom: 0; left: 0; padding: 25px; background-color: #007dc5; text-align: center; margin-top:10px; min-height:80px; }
    /* Footer Licenses Control */
    .footer-licenses-control {position: absolute; bottom: 0; right: 0; width: 100%; min-height: 66px; z-index: 0; color:#fff; background-color: #007dc5; padding-top: 30px; margin-top: 20px;}
    @media only screen and (max-width : 768px) {
        body { background: #fff; }
        #content { top:inherit; position:inherit; margin:0; width: 100%; font-size:14px; }
        #img-cloud { height:240px; }
    }
    @media only screen and (max-width : 480px) {
        #img-cloud { height:150px; }
    }
    @media only screen and (max-width : 320px) {
        #img-cloud { height:100px; }   
    }
</style>
<div id="wrapper-licenses-control">
    <div class="container-fluid">
        <div class="row" id="header-licenses-information"></div>
            <div class="col-md-12" id="content">
                <div class="row" id="warning-licenses">
                    <div class="col-12 col-md-12 mt-5 text-center">
                        <div id="description-information">
                            <div class="icon-ilustration">
                                <img src="./images/fapp_img1.png" class="img-fluid text-center">
                            </div>
                            <div class="mt-4 text-center">
                                <h4><?=gettext("FIREWALLAPP INACTIVE OR NOT CONFIGURED")?></h4>
                            </div>
                            <div class="col-12 mt-4 text-center">
                                <div class="row">
                                    <div id="information-support">
                                        <h6><?=gettext("Access the FirewallApp Settings Menu, Enable Services and Reporting.")?></h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php }
?>

<?php //include("foot_monitor.inc"); ?>
<?php include("foot.inc"); ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="../js/echarts/dist/echarts.min.js"></script>
<script type="text/javascript">
    // Buttons contents
    $("#btn-traffic").click(function(){
        $('#box-top-traffic').removeClass('no-display');
        $('#box-aplications-percent').addClass('no-display');
        $('#box-top-ips').addClass('no-display');

        $('#btn-traffic').removeClass('opacity-05');
        $('#btn-aplications').addClass('opacity-05');
        $('#btn-ips').addClass('opacity-05');

        <?php 
        $list_top_values = "/usr/local/www/list_top_values";
        $list_top_categories = "/usr/local/www/list_top_categories";

        $top_values = "";
        $top_name = "";
        if (file_exists($list_top_values) && file_exists($list_top_categories)) {

            $top_values = str_replace("L","",file_get_contents($list_top_values));
            if (strlen($top_values) == 0) {
                $top_values = ""; 
            }
            $top_name = str_replace("L","",file_get_contents($list_top_categories));
            if (strlen($top_name) == 0) {
                $top_name = ""; 
            }
        ?>
        $('#box-top-traffic').show();
        // Chart Traffic
        var data1 = "[]";
        var data2 = "[]";
        <?php if (!empty($top_values)) { ?>
            data1 = <?=$top_values?>;//[600, 500, 400, 350, 330, 280, 250, 200, 155, 80];
        <?php } ?>
        <?php if (!empty($top_name)) { ?>
            data2 = <?=$top_name?>;//[600, 500, 400, 350, 330, 280, 250, 200, 155, 80];
        <?php } ?> 
        //var data2 = <?php echo $top_name; ?>;//['WhatsApp', 'Telegram', 'Linkedin', 'Portal Uol', 'Instagram', 'G1', 'Netflix', 'Spotify', 'Prime Video', 'Youtube'];
        
        trafficOption = {
            grid: {
                left: '15',
                right: '15',
                top: 10,
                bottom: 0,
                containLabel: true,
                color: '#fff',
            },
            tooltip: {
                trigger: 'axis',
                formatter: (comp, value) => {
                    const [serie] = comp;
                    return `${serie.seriesName} ${serie.name}: ${serie.data}MB`
                },
                axisPointer: {
                    type: 'shadow',
                }
            },
            xAxis : [
                {
                    type : 'category',
                    axisLabel:  {
                        interval: 0,
                        rotate: 9,
                        show: true,
                        splitNumber: 15,
                        textStyle: {
                            fontSize: 12,
                            color: '#333',
                            fontWeight:600,
                        },
                    },
                    data : data2 ,
                   
                }
            ],
            yAxis : [
                {
                    name: 'MB:',
                    type : 'value',
                    splitLine: {show: false},
                    axisLabel: {
                        textStyle: {
                            color: '#333',
                        }
                    }
                },
            ],
            series : [
                {
                    name: '<?=gettext("Traffic")?>',
                    type: 'bar',
                    data: data1,
                    itemStyle: {
                        normal: {
                            color: '#fcc85a',
                        }
                    },
                },
            ]
        };

        var TrafficChart = echarts.init(document.getElementById("chart-traffic"));
            console.log(trafficOption);
            TrafficChart.setOption(trafficOption);
            
        $(window).resize(function() {
            TrafficChart.resize();
        });

        <?php
        } else {
        ?>
            $('#box-top-traffic').hide();
        <?php
        }
        ?>

    });

    $("#btn-aplications").click(function(){
        $('#box-top-traffic').addClass('no-display');
        $('#box-aplications-percent').removeClass('no-display');
        $('#box-top-ips').addClass('no-display');

        $('#btn-aplications').removeClass('opacity-05');
        $('#btn-traffic').addClass('opacity-05');
        $('#btn-ips').addClass('opacity-05');
    });

    $("#btn-ips").click(function(){
        $('#box-top-traffic').addClass('no-display');
        $('#box-aplications-percent').addClass('no-display');
        $('#box-top-ips').removeClass('no-display');

        $('#btn-ips').removeClass('opacity-05');
        $('#btn-traffic').addClass('opacity-05');
        $('#btn-aplications').addClass('opacity-05');

        <?php 
        $top_val_ips = "";
        if (file_exists("/usr/local/www/list_top_val_ips")) {
            $top_val_ips = file_get_contents("/usr/local/www/list_top_val_ips"); 
            if (strlen($top_val_ips) == 0) {
                $top_val_ips = ""; 
            }
        }
        $top_ips = "";
        if (file_exists("/usr/local/www/list_top_ips")) {
            $top_ips = file_get_contents("/usr/local/www/list_top_ips");
            if (strlen($top_ips) == 0) {
                $top_ips = ""; 
            }
        }
        
        ?>

        // Chart IP
        var data1 = "[]";
        var data2 = "[]";
        <?php if (!empty($top_val_ips)) { ?>
            data1 = <?=str_replace("L","",$top_val_ips)?>;//[600, 500, 400, 350, 330, 280, 250, 200, 155, 80];
        <?php } ?>
        <?php if (!empty($top_ips)) { ?>
            data2 = <?=str_replace("L","",$top_ips)?>;//[600, 500, 400, 350, 330, 280, 250, 200, 155, 80];
        <?php } ?> 

        IPOption = {
            grid: {
                left: '15',
                right: '15',
                top: 10,
                bottom: 0,
                containLabel: true,
                color: '#fff',
            },
            tooltip: {
                trigger: 'axis',
                formatter: (comp, value) => {
                    const [serie] = comp;
                    return `${serie.seriesName} ${serie.name}: ${serie.data}MB`
                },
                axisPointer: {
                    type: 'shadow',
                }
            },
            xAxis : [
                {
                    type : 'category',
                    axisLabel:  {
                        interval: 0,
                        rotate: 9,
                        show: true,
                        splitNumber: 15,
                        textStyle: {
                            fontSize: 12,
                            color: '#333',
                            fontWeight:600,
                        },
                    },
                    data : data2 ,
                   
                }
            ],
            yAxis : [
                {
                    name: 'MB:',
                    type : 'value',
                    splitLine: {show: false},
                    axisLabel: {
                        textStyle: {
                            color: '#333',
                        }
                    }
                },
            ],
            series : [
                {
                    name: 'Tráfego',
                    type: 'bar',
                    data: data1,
                    itemStyle: {
                        normal: {
                            color: '#fcc85a',
                        }
                    },
                },
            ]
        };

        var IPChart = echarts.init(document.getElementById("chart-ips"));
            console.log(IPOption);
            console.log(IPOption.length);
            IPChart.setOption(IPOption);

        $(window).resize(function() {
            IPChart.resize();
        });
    });

</script>
