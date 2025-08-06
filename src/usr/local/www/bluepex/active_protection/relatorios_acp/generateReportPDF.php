<?php
require("config.inc");

function generatePDFUTM() {
    global $config;

    require_once("/usr/local/www/active_protection/ajax_request_csv_serial.php");
    $data_now = date('d_m_Y', strtotime('-1 day'));
    generateDefaultReport();
    sleep(10);

    if (!file_exists("/usr/local/www/active_protection/geo_ameacas_map")) {
        file_put_contents("/usr/local/www/active_protection/geo_ameacas_map",'{"data":[]}');
    }
    if (!file_exists("/usr/local/www/active_protection/tentativas_invasao")) {
        file_put_contents("/usr/local/www/active_protection/tentativas_invasao",'{"data":[]}');
    }
    shell_exec("/bin/chmod -R 777 /usr/local/www/active_protection/relatorios_acp/");
    if (file_exists("/usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf")) {
        unlink("/usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf");
    }

    if (file_exists('/usr/local/www/active_protection/filtro_log_report.csv') || file_exists('/usr/local/www/active_protection/filtro_acp_report.csv')) {
        if (strlen(trim(file_get_contents('/usr/local/www/active_protection/filtro_log_report.csv'))) > 0 || strlen(trim(file_get_contents('/usr/local/www/active_protection/filtro_acp_report.csv'))) > 0) {
            if (isset($config["system"]["language"]) && $config["system"]["language"] == "en_US") {
                shell_exec("/usr/local/bin/php /usr/local/www/active_protection/relatorios_acp/form_page_echarts_english.php > /usr/local/www/active_protection/relatorios_acp/index.html && wkhtmltopdf --disable-smart-shrinking --encoding utf-8 --custom-header 'meta' 'charset=utf-8' --margin-bottom '0mm' --margin-left '0mm' --margin-right '0mm' --margin-top '0mm' --enable-local-file-access --debug-javascript --javascript-delay 10000 --enable-javascript --no-stop-slow-scripts /usr/local/www/active_protection/relatorios_acp/index.html /usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf");
            } elseif (isset($config["system"]["language"]) && $config["system"]["language"] == "pt_BR") {
                shell_exec("/usr/local/bin/php /usr/local/www/active_protection/relatorios_acp/form_page_echarts_portuguese.php > /usr/local/www/active_protection/relatorios_acp/index.html && wkhtmltopdf --disable-smart-shrinking --encoding utf-8 --custom-header 'meta' 'charset=utf-8' --margin-bottom '0mm' --margin-left '0mm' --margin-right '0mm' --margin-top '0mm' --enable-local-file-access --debug-javascript --javascript-delay 10000 --enable-javascript --no-stop-slow-scripts /usr/local/www/active_protection/relatorios_acp/index.html /usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf");
            } else {
                shell_exec("/usr/local/bin/php /usr/local/www/active_protection/relatorios_acp/form_page_echarts_portuguese.php > /usr/local/www/active_protection/relatorios_acp/index.html && wkhtmltopdf --disable-smart-shrinking --encoding utf-8 --custom-header 'meta' 'charset=utf-8' --margin-bottom '0mm' --margin-left '0mm' --margin-right '0mm' --margin-top '0mm' --enable-local-file-access --debug-javascript --javascript-delay 10000 --enable-javascript --no-stop-slow-scripts /usr/local/www/active_protection/relatorios_acp/index.html /usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf");
            }
            shell_exec("/bin/chmod -R 777 /usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf");
            unlink("/usr/local/www/active_protection/relatorios_acp/index.html");
        }
    }

    shell_exec("/usr/bin/find /usr/local/www/active_protection/relatorios_acp/report_acp* -type f -atime +7d -exec rm {} \;");
}

if (file_exists("/tmp/generateReportPDFUTM")) {
    generatePDFUTM();
    unlink("/tmp/generateReportPDFUTM");
}

if (file_exists('/etc/report_acp_time') && strlen(file_get_contents('/etc/report_acp_time')) > 1 && file_exists("/etc/report_acp_enable") && trim(file_get_contents('/etc/report_acp_enable')) == "on") {
    $now = time();
    if (file_exists('/etc/report_acp_time')) {
        $times_now = explode("_", trim(file_get_contents('/etc/report_acp_time')));
        $start = strtotime( date('Y-m-d' . $times_now[0]) );
        $end = strtotime( date('Y-m-d' . $times_now[1]) );
    } else {
        $start = strtotime( date('Y-m-d' . '01:00:00') );
        $end = strtotime( date('Y-m-d' . '01:06:00') );
    }
    if ( $start <= $now && $now <= $end ) {
        $data_now = date('d_m_Y', strtotime('-1 day'));
        if (!file_exists("/usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf")) {
            sleep(rand(1,1800));
            generatePDFUTM();
            if (file_exists("/usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf")) {
                if (file_exists("/etc/report_acp_send_enable") && (trim(file_get_contents("/etc/report_acp_send_enable")) == "on")) {
                    sendFileToEmailSMTP();
                }
            }
        }
    }
}

if (file_exists("/tmp/sendReportUTM")) {
    $data_now = date('d_m_Y', strtotime('-1 day'));
    if (file_exists("/usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf")) {
        sendFileToEmailSMTP();
    }
    unlink("/tmp/sendReportUTM");
}

function sendFileToEmailSMTP() {
    if (file_exists("/etc/errorGenerateEmail")) {
        unlink("/etc/errorGenerateEmail");
    }
    $data_now = date('d_m_Y', strtotime('-1 day'));
    if (file_exists("/usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf")) {

        require_once("/usr/local/share/pear/Mail.php");
        require_once("/usr/local/share/pear/Mail/mime.php");

        global $config;
        
        $oldDate = date('d/m/Y', strtotime('-1 day', strtotime('now')));
        $message = "Segue em anexo no formato PDF o arquivo de relatório do Active Protection referente a $oldDate, o mesmo relatório está disponível na aba de relatórios do Active protection com o tempo de vida de 7 dias antes de ser removido.";
        $subject = "Report Active Protection - BluePex - " . date("d/m/Y - H:i");  

        if (isset($config['notifications']['smtp']['disable']) && !$force) {
            return;
        }

        if (!$config['notifications']['smtp']['ipaddress']) {
            return;
        }

        if (!$config['notifications']['smtp']['notifyemailaddress']) {
            return;
        }

        $to = $config['notifications']['smtp']['notifyemailaddress'];

        if (empty($config['notifications']['smtp']['username']) || empty($config['notifications']['smtp']['password'])) {
            $auth = false;
            $username = '';
            $password = '';
        } else {
            $auth = isset($config['notifications']['smtp']['authentication_mechanism']) ? $config['notifications']['smtp']['authentication_mechanism'] : 'PLAIN';
            $username = $config['notifications']['smtp']['username'];
            $password = $config['notifications']['smtp']['password'];
        }

        $params = array(
            'host' => (isset($config['notifications']['smtp']['ssl'])
                ? 'ssl://'
                : '')
                . $config['notifications']['smtp']['ipaddress'],
            'port' => empty($config['notifications']['smtp']['port'])
                ? 25
                : $config['notifications']['smtp']['port'],
            'auth' => $auth,
            'username' => $username,
            'password' => $password,
            'localhost' => $config['system']['hostname'] . "." .
                $config['system']['domain'],
            'timeout' => !empty($config['notifications']['smtp']['timeout'])
                ? $config['notifications']['smtp']['timeout']
                : 20,
            'debug' => false,
            'persist' => false
        );

        if ($config['notifications']['smtp']['sslvalidate'] == "disabled") {
            $params['socket_options'] = array(
                'ssl' => array(
                    'verify_peer_name' => false,
                    'verify_peer' => false
            ));
        }

        if ($config['notifications']['smtp']['fromaddress']) {
            $from = $config['notifications']['smtp']['fromaddress'];
        } else {
            $from = "BluePexUTM@{$config['system']['hostname']}.{$config['system']['domain']}";
        }

        $headers = array(
            "From"    => $from,
            "To"      => $to,
            "Subject" => $subject,
            "Date"    => date("r"),
            "text_charset"  => 'UTF-8',
            "html_charset"  => 'UTF-8',
            "head_charset"  => 'UTF-8',
            "Content-Type"  => 'text/html; charset="UTF-8"'
        );

        $error_text = 'Could not send the message to %1$s -- Error: %2$s';

        try {

            $mime = new Mail_mime(array("text_charset" => "utf-8", "html_charset" => "utf-8", 'eol' => "\n"));
            $mime->setTXTBody($message);
            $mime->addAttachment("/usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf", 'application/pdf', "report_acp_{$data_now}.pdf", true);
            $body = $mime->get();
            
            $hdrs = $mime->headers($headers);


            $smtp =& Mail::factory('smtp', $params);
            @$smtp->send($to, $hdrs, $body);

            //$mail = @$smtp->send($to, $headers, $body);

            //if (PEAR::isError($mail)) {
            //	$err_msg = sprintf(gettext($error_text),
            //	    $to, $mail->getMessage());
            //}
        } catch (Exception $e) {
            $err_msg = sprintf(gettext($error_text), $to, $e->getMessage());
        }

        if (!empty($err_msg)) {
            log_error($err_msg);
            file_put_contents("/etc/errorGenerateEmail", "");
            return true;
        }
        log_error(sprintf(gettext("Message sent to %s OK"), $to));
        return true;
    }
    file_put_contents("/etc/errorGenerateEmail", "");
}
