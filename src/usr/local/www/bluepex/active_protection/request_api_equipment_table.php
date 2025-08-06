<?php

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("system.inc");
require_once("firewallapp_webservice.inc");

ini_set('memory_limit', '512M');

$pgtitle = array(gettext("Active Protection"), gettext("Active Protection Reports"));
$pglinks = array("./active_protection/ap_services.php", "@self");
include("head.inc");

?>

<style>
h6 {
    font-size: 16px;
    font-weight: normal;
    color: #007dc5;
    text-transform: uppercase;
}
.table-link th {
    border: none;
    border-bottom: none;
    background-color: #177bb4;
    color: #fff;
    vertical-align: middle !important;
}
table.table.table-striped.table-bordered.mt-lg-3.table-link > th, td {
    text-align: center !important;
    vertical-align: middle !important;
}
.card-ameaca, .card-system, .card-link, .card-app {
    height: auto !important;
}
.card-link+.card-link {
    margin-left: unset !important;
}
</style>

<div class="infoblock" style="margin-left: auto;">
    <div class="alert alert-info clearfix" role="alert">
        <div class="pull-left">
            <p><?=gettext("Tabular sampling of CSV files obtained with sporadic sample extractions.")?></p>
            <br>
            <p><?=gettext("The presentation of the data below is limited to a sample exposure of only 100 lines with cross-browser compatibility science.")?></p>
        </div>
    </div>
</div>

<?php
$tab_array = array();
$tab_array[] = array(gettext("Graphic presentation"), false, "./request_api_equipment.php");
$tab_array[] = array(gettext("Presentation of samples"), true, "./request_api_equipment_table.php");
$tab_array[] = array(gettext("Reports"), false, "./request_api_equipment_to_pdf.php");

display_top_tabs($tab_array);

function replace_carater($caracter, $field) {
    return str_replace($caracter, "", $field);
}

?>


<div class="card-link p-3 mb-sm-3">
    <?php if (file_exists("/usr/local/www/active_protection/filtro_acp.csv")): ?>
        <h6><?=gettext("Sampling the FilterACP data:")?> <?=shell_exec("wc -l /usr/local/www/active_protection/filtro_acp.csv | awk -F\" \" '{print $1}'")?></h6>
        <hr class="line-bottom">
        <div class="container col-sm-12 px-0">
        <table class="table table-striped table-bordered mt-lg-3 table-link">
            <thead>
                <tr>
                    <th><?=gettext('Data')?></th>
                    <th><?=gettext('Number of hits')?></th>
                    <th><?=gettext('Service/Address')?></th>
                    <th><?=gettext('Classification')?></th>
                    <th><?=gettext('Priority')?></th>
                    <th><?=gettext('Protocol')?></th>
                    <th><?=gettext('Source')?></th>
                    <th><?=gettext('Port Source')?></th>
                    <th><?=gettext('Destination')?></th>
                    <th><?=gettext('Port Destination')?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $limiter_acp = 0;
                foreach (explode("\n", file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) as $linha) {
                    $linha = explode(",", $linha);
                    if ($limiter_acp != 100) {
                        if (
                            isset($linha[0]) && !empty($linha[0]) && 
                            isset($linha[1]) && !empty($linha[1]) && 
                            isset($linha[2]) && !empty($linha[2]) && 
                            isset($linha[3]) && !empty($linha[3]) && 
                            isset($linha[4]) && !empty($linha[4]) && 
                            isset($linha[5]) && !empty($linha[5]) && 
                            isset($linha[6]) && !empty($linha[6]) && 
                            isset($linha[7]) && !empty($linha[7]) && 
                            isset($linha[8]) && !empty($linha[8]) && 
                            isset($linha[9]) && !empty($linha[9]) 
                        ) {
                        ?>
                        <tr>
                            <td><?=$linha[0]?></td>
                            <td><?=$linha[1]?></td>
                            <td><?=replace_carater('"', $linha[2])?></td>
                            <td><?=replace_carater('"', $linha[3])?></td>
                            <td><?=$linha[4]?></td>
                            <td><?=$linha[5]?></td>
                            <td><?=$linha[6]?></td>
                            <td><?=$linha[7]?></td>
                            <td><?=$linha[8]?></td>
                            <td><?=$linha[9]?></td>
                        </tr>
                        <?php
                        }
                            $limiter_acp++;
                        } else {
                            ?>
                                <tr>
                                    <td colspan="10">...</td>
                                </tr>
                            <?php
                            break;
                        }
                    }
                ?>
            </tbody>
        </table>
        </div>
    <?php else: ?>
        <h6><?=gettext("There is no ACP CSV Filter file")?></h6>
    <?php endif; ?>
</div>
<div class="card-link p-3 mb-sm-3">
    <?php if (file_exists("/usr/local/www/active_protection/filtro_log.csv")): ?>
        <h6><?=gettext("FilterLog data sampling:")?> <?=shell_exec("wc -l /usr/local/www/active_protection/filtro_log.csv | awk -F\" \" '{print $1}'")?></h6>
        <hr class="line-bottom">
        <div class="container col-sm-12 px-0">
            <table class="table table-striped table-bordered mt-lg-3 table-link">
            <thead>
                <tr>
                    <th><?=gettext('Number of hits')?></th>
                    <th><?=gettext('Data')?></th>
                    <th><?=gettext('Group')?></th>
                    <th><?=gettext('Interface')?></th>
                    <th><?=gettext('Protocol')?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $limiter_log = 0;
                foreach (explode("\n", file_get_contents('/usr/local/www/active_protection/filtro_log.csv')) as $linha) {
                    $linha = explode(",", $linha);
                    if ($limiter_log != 100) {

                ?>
                        <tr>
                            <td><?=$linha[0]?></td>
                            <td><?=$linha[1]?></td>
                            <td><?=$linha[2]?></td>
                            <td><?=$linha[3]?></td>
                            <td><?=$linha[4]?></td>
                        </tr>
                <?php
                        $limiter_log++;
                    } else {
                ?>
                    <tr>
                        <td colspan="5">...</td>
                    </tr>
                <?php
                        break;
                    }
                }
                ?>
            </tbody>
        </table>   
        </div>
    <?php else: ?>
        <h6><?=gettext("There is no CSV Log Filter file")?></h6>
    <?php endif; ?>
</div>

<?php include("foot.inc"); ?>
