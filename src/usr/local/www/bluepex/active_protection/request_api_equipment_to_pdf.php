<?php

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("system.inc");
require_once("firewallapp_webservice.inc");

$savemsg = "";

if ($_POST['save']) {

    if (isset($_POST['hour_operation_acp'])) {
        file_put_contents('/etc/report_acp_time', $_POST['hour_operation_acp']);
    } else {
        file_put_contents('/etc/report_acp_time', '01:00:00_01:06:00');
    }
    if (isset($_POST['enable_report_acp'])) {
        file_put_contents('/etc/report_acp_enable', $_POST['enable_report_acp']);
    } else {
        file_put_contents('/etc/report_acp_enable', 'off');
    }
    if (isset($_POST['report_acp_send_enable'])) {
        file_put_contents('/etc/report_acp_send_enable', $_POST['report_acp_send_enable']);
    } else {
        file_put_contents('/etc/report_acp_send_enable', 'off');
    }
    if (isset($_POST['report_acp_range'])) {
        file_put_contents('/etc/report_acp_range', $_POST['report_acp_range']);
    } else {
        file_put_contents('/etc/report_acp_range', '1');
    }
    $savemsg = "Save configuration of automatic reports Active Protection";
}

$pgtitle = array(gettext("Active Protection"), gettext("Active Protection Reports"));
$pglinks = array("./active_protection/ap_services.php", "@self");
include("head.inc");
?>

<style>
.table-striped-virus > tbody > tr:nth-child(2n+1) > td, .table-striped > tbody > tr:nth-child(2n+1) > th {
    background-color: transparent;
}
.spaceMarginLeftCM {
    margin-left: 100px !important;
}
</style>

<div class="infoblock" style="margin-left: auto;">
    <div class="alert alert-info clearfix" role="alert">
        <div class="pull-left">
            <p><?=gettext("This page refers to the control of generating PDF reports of Active Protection data.")?></p>
            <p><?=gettext("A description follows:")?></p>
            <ul>
                <li><?=gettext("The reports generated will always be from the day before the current date;")?></li>
                <li><?=gettext("If the manual generation of the report is activated and there is already one with the same date, the old one will be overwritten;")?></li>
                <li><?=gettext("To send an email with a report, it is necessary to configure it on the 'Notifications' page;")?></li>
                <li><?=gettext("If there is no data to be sampled in the Dash of the reports and a PDF report is requested to be generated, the data obtained will be presented in the Dash;")?></li>
                <li><?=gettext("When the actions are triggered manually, a warning will be displayed that the operation was successful or not a short time later.")?></li>
            </ul>
            <hr>
            <p style='color:red;'><?=gettext("NOTE: Be aware that the reports will only have 7 working days to live for each file, this always guarantees the generation of the most recent reports.")?></p>
        </div>
    </div>
</div>

<div class="alert alert-warning clearfix" role="alert" name="displayRequesInRunningReport">
	<div class="pull-left">
		<p><?=gettext("The information is being obtained to generate the report...")?></p>
	</div>
</div>

<?php

$tab_array = array();
$tab_array[] = array(gettext("Graphic presentation"), false, "./request_api_equipment.php");
$tab_array[] = array(gettext("Presentation of samples"), false, "./request_api_equipment_table.php");
$tab_array[] = array(gettext("Reports"), true, "./request_api_equipment_to_pdf.php");

display_top_tabs($tab_array);

if (intval(trim(shell_exec("pkg info | grep wkhtmltopdf -c"))) > 0) {

    $hourReload = array(
        '02:30:00_02:36:00' => '02:30:00 (02:30 AM)',
        '03:00:00_03:06:00' => '03:00:00 (03:00 AM)',
        '03:30:00_03:36:00' => '03:30:00 (03:30 AM)',
        '04:00:00_04:06:00' => '04:00:00 (04:00 AM)',
        '04:30:00_04:36:00' => '04:30:00 (04:30 AM)',
        '05:00:00_05:06:00' => '05:00:00 (05:00 AM)',
        '05:30:00_05:36:00' => '05:30:00 (05:30 AM)',
        '06:00:00_06:06:00' => '06:00:00 (06:00 AM)',
        '06:30:00_06:36:00' => '06:30:00 (06:30 AM)'
    );

    $form = new Form;

    $section = new Form_Section(gettext("Settings"));

    $status_enable_report_acp = false;
    if (file_exists("/etc/report_acp_enable") && trim(file_get_contents("/etc/report_acp_enable")) == "on") {
        $status_enable_report_acp = true;
    }

    $section->addInput(new Form_Checkbox(
        'enable_report_acp',
        gettext('Enable automatic report generation'),
        gettext('Enabling this option will automatically generate a report for the set period every day.'),
        $status_enable_report_acp,
        'on'
    ))->setHelp(gettext('While this option is enabled, a report will be generated automatically based on the time set in the field below.'));

    $hour_enable_report_acp = '00:01:00_00:10:00';
    if (file_exists("/etc/report_acp_time") && strlen(trim(file_get_contents("/etc/report_acp_time"))) > 0) {
        $hour_enable_report_acp = trim(file_get_contents("/etc/report_acp_time"));
    }

    $section->addInput(new Form_Select(
        'hour_operation_acp',
        gettext('Generation Time'),
        $hour_enable_report_acp,
        $hourReload
    ))->setHelp(gettext('Select a time to generate the report.'));

    $status_enable_send_report_acp = false;
    if (file_exists("/etc/report_acp_send_enable") && trim(file_get_contents("/etc/report_acp_send_enable")) == "on") {
        $status_enable_send_report_acp = true;
    }

    $section->addInput(new Form_Checkbox(
        'report_acp_send_enable',
        gettext('Enable automatic submission of daily report'),
        gettext('Enabling this option will send the generated daily reports.'),
        $status_enable_send_report_acp,
        'on'
    ))->setHelp(gettext('As long as this option is enabled, the entire report generated automatically will be sent by email as soon as it becomes available.'));

    $emailACP = "";
    if (isset($config['notifications']['smtp']['notifyemailaddress']) && strlen($config['notifications']['smtp']['notifyemailaddress'])) {
        $emailACP = trim($config['notifications']['smtp']['notifyemailaddress']);
    }

    $section->addInput(new Form_Input(
        'emailACP',
        gettext('Sending email'),
        'email',
        $emailACP
    ))->setHelp(gettext("Email that will receive the reports. If the email displayed is blank or different from the desired one, <a href='../system_advanced_notifications.php'>click here</a> to confirm the sending of the files."));

    $range_time_select = [
        "1" => gettext("1 Day"),
        "7" => gettext("7 Days"),
        "14" => gettext("14 Days"),
        "30" => gettext("30 Days"),
    ];

    $range_time_value = (file_exists("/etc/report_acp_range") && !empty(file_get_contents("/etc/report_acp_range"))) ? file_get_contents("/etc/report_acp_range") : "1";

    $section->addInput(new Form_Select(
        'report_acp_range',
        gettext('Filter period'),
        $range_time_value,
        $range_time_select
    ))->setHelp(gettext('Enter the period for obtaining data. <p class="text-danger">Note: You need to save the field change to obtain a report of the desired range.</p>'));

    $form->add($section);
    
    print($form);

    ?>
    <div class="panel panel-default">
	    <div class="panel-heading"><h2 class="panel-title"><?=gettext('Services BluePex')?></h2></div>
    	    <div class="panel-body">
	            <div class="panel-body panel-default">
		            <div class="table-responsive">
                        <table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
                            <thead>
                                <tr>
                                    <th><?=gettext("Date")?></th>
                                    <th><?=gettext("Reports")?></th>
                                </tr>
                            </thead>
                            <tbody id="table-pdf">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_send_reportpdf" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body text-center my-5">
                    <h3 style="color:#007DC5" class='text_modal_send_reportpdf'><?=gettext('Requested to send the daily report to the registered email.')?></h3>
                    <br>
                    <img id="img_modal_send_reportpdf" src="../images/spinner.gif"/>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="modal_generate_reportpdf" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body text-center my-5">
                    <h3 style="color:#007DC5" class="text_modal_generate_reportpdf"><?=gettext('Please wait a moment, the daily report is being generated and will be presented in the table below.')?></h3>
                    <br>
                    <img id="img_modal_generate_reportpdf" src="../images/spinner.gif"/>
                </div>
            </div>
        </div>
    </div>

    <?php include("foot.inc")?>;

    <script>

    $("#emailACP").attr('disabled','disabled');

    var btn_implementacao = document.createElement("button");
	btn_implementacao.className = 'btn btn-primary';
	btn_implementacao.innerHTML = '<i class="fa fa-pencil"></i> Gerar relatório';
	btn_implementacao.id ='generateReportPDFUTM';
	btn_implementacao.name ='generateReportPDFUTM';
	document.getElementsByClassName("col-sm-12 form-button text-center mt-5")[0].append(btn_implementacao);
    document.getElementById("generateReportPDFUTM").classList.add("spaceMarginLeftCM");

    document.getElementById("generateReportPDFUTM").addEventListener("click", function(event){
		event.preventDefault();
        $('#modal_generate_reportpdf').modal('show');
        setTimeout(() => {
            setTimeout(() => {
                $('#modal_generate_reportpdf').modal('hide');
            }, 15000);
            $.ajax(
                "./ajax_report_pdf.php",
                {
                type: 'post',
                data: {
                    generatePDFReportNow: true,
                },
            }).done(function(data) {
                returnsStates = data.split("-");
                $('#modal_generate_reportpdf .text_modal_generate_reportpdf').text(returnsStates[1]);
                if (returnsStates[0] == "TRUE") { 
                    $('#modal_generate_reportpdf #img_modal_generate_reportpdf').attr('src', '../images/update_rules_ok.png');
                } else {
                    $('#modal_generate_reportpdf #img_modal_generate_reportpdf').attr('src', '../images/bp-logout.png');
                }
                $('#modal_generate_reportpdf #img_modal_generate_reportpdf').attr('style', 'width:64px!important;');
                $('#modal_generate_reportpdf').modal('show');
                returnPdfsTable();
                setTimeout(() => {
                    $('#modal_generate_reportpdf').modal('hide');
                }, 5000);
                setTimeout(() => {
                    $('#modal_generate_reportpdf .text_modal_generate_reportpdf').text('<?=gettext('Please wait a moment, the daily report is being generated and will be presented in the table below.')?>');
                    $('#modal_generate_reportpdf #img_modal_generate_reportpdf').attr('src', '../images/spinner.gif');
                    $('#modal_generate_reportpdf #img_modal_generate_reportpdf').removeAttr('style');
                }, 6000);
            });
        }, 1000);
    });

    btn_implementacao = document.createElement("button");
	btn_implementacao.className = 'btn btn-primary';
	btn_implementacao.innerHTML = '<i class="fa fa-pencil"></i> Enviar relatório diário';
	btn_implementacao.id ='sendReportPDFUTM';
	btn_implementacao.name ='sendReportPDFUTM';
	document.getElementsByClassName("col-sm-12 form-button text-center mt-5")[0].append(btn_implementacao);

    document.getElementById("sendReportPDFUTM").addEventListener("click", function(event){
		event.preventDefault();
        $('#modal_send_reportpdf').modal('show');
        setTimeout(() => {
            $('#modal_send_reportpdf').modal('hide');
            $.ajax(
                "./ajax_report_pdf.php",
                {
                type: 'post',
                data: {
                    sendReportUTM: true,
                },
            }).done(function(data) {
                returnsStates = data.split("-");
                $('#modal_send_reportpdf .text_modal_send_reportpdf').text(returnsStates[1]);
                if (returnsStates[0] == "TRUE") { 
                    $('#modal_send_reportpdf #img_modal_send_reportpdf').attr('src', '../images/update_rules_ok.png');
                } else {
                    $('#modal_send_reportpdf #img_modal_send_reportpdf').attr('src', '../images/bp-logout.png');
                }
                $('#modal_send_reportpdf #img_modal_send_reportpdf').attr('style', 'width:64px!important;');                
                $('#modal_send_reportpdf').modal('show');
                setTimeout(() => {
                    $('#modal_send_reportpdf').modal('hide');
                }, 5000);
                setTimeout(() => {
                    $('#modal_send_reportpdf .text_modal_send_reportpdf').text('<?=gettext('Requested to send the daily report to the registered email.')?>');
                    $('#modal_send_reportpdf #img_modal_send_reportpdf').attr('src', '../images/spinner.gif');
                    $('#modal_send_reportpdf #img_modal_send_reportpdf').removeAttr('style');
                }, 6000);
            });
        }, 5000);
    });

    function returnPdfsTable(){
        $.ajax(
            "./ajax_report_pdf.php",
            {
			type: 'post',
			data: {
				returnTablePDF: true,
			},
		}).done(function(data) {
            $("#table-pdf").html(data);
        });
    }

    function displayRequesInRunning() {
	$.ajax(
		"./ajax_request_csv_serial.php",
		{
			type: 'post',
			data: {
				displayRequesInRunningReport: true,
			},
		}).done(function(data) {
			data = data.split("\n")[2];
			if (data == "true") {
				$("div[name=displayRequesInRunningReport]").removeAttr("style").attr("style", "display:block;");
			} else {
				$("div[name=displayRequesInRunningReport]").removeAttr("style").attr("style", "display:none;");
			}
		}
	);
    }

    setTimeout(() => { returnPdfsTable(); }, 100);
    setTimeout(() => { displayRequesInRunning(); }, 100);

    window.setInterval("returnPdfsTable()", 5000);
    window.setInterval("displayRequesInRunning()", 5000);

    </script>

    <?php


} else {
    echo "<p style='color:red;'>OBS: Packages necessary to generate the reports are not installed, be aware that without them, it is not possible to generate a PDF file with the data returned from the equipment. Please contact support if you need this tool.</p>";
    include("foot.inc");
}

?>
