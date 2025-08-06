<?php

if ($_POST['returnTablePDF']) {
    $returnTablePDF = [];
    foreach(glob('/usr/local/www/active_protection/relatorios_acp/report_acp_*') as $report_pdf) {
        $report_now = end(explode("/", $report_pdf));
        $file_now = "./relatorios_acp/" . $report_now;
        $returnTablePDF[filemtime($report_pdf)] = "<tr><th>" . date("d/m/Y H:i:s", filemtime($report_pdf)) . "</th><th><a href='$file_now' download>" . $report_now . "</th></tr>";
    }
    ksort($returnTablePDF);
    echo join("", array_reverse($returnTablePDF));
}

if ($_POST['generatePDFReportNow']) {
    file_put_contents("/tmp/generateReportPDFUTM", "");
    shell_exec("/usr/local/bin/php /usr/local/www/active_protection/relatorios_acp/generateReportPDF.php");
    $data_now = date('d_m_Y', strtotime('-1 day'));
    if (file_exists('/usr/local/www/active_protection/filtro_log_report.csv') || file_exists('/usr/local/www/active_protection/filtro_acp_report.csv')) {
        if (strlen(trim(file_get_contents('/usr/local/www/active_protection/filtro_log_report.csv'))) > 0 || strlen(trim(file_get_contents('/usr/local/www/active_protection/filtro_acp_report.csv'))) > 0) {
            if (file_exists("/usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf")) {
                echo "TRUE-Relatório gerado com sucesso.";
            } else {
                echo "FALSE-Não foi possível gerar o relatório.";    
            }
        } else {
            echo "FALSE-Não foi possível gerar o relatório. Não da dados para gerar o arquivo no momento.";    
        }
    } else {
        echo "FALSE-Não foi possível gerar o relatório. Os arquivos necessários para gerar o relatório não existem no momento.";    
    }
}

if ($_POST['sendReportUTM']) {
    $data_now = date('d_m_Y', strtotime('-1 day'));
    if (file_exists("/usr/local/www/active_protection/relatorios_acp/report_acp_{$data_now}.pdf")) {
        if (file_exists('/usr/local/www/active_protection/filtro_log_report.csv') || file_exists('/usr/local/www/active_protection/filtro_acp_report.csv')) {
            if (strlen(trim(file_get_contents('/usr/local/www/active_protection/filtro_log_report.csv'))) > 0 || strlen(trim(file_get_contents('/usr/local/www/active_protection/filtro_acp_report.csv'))) > 0) {
                file_put_contents("/tmp/sendReportUTM", "");
                shell_exec("/usr/local/bin/php /usr/local/www/active_protection/relatorios_acp/generateReportPDF.php");
                if (file_exists("/etc/errorGenerateEmail")) {
                    unlink("/etc/errorGenerateEmail");
                    echo "FALSE-Não foi possível enviar o email do relatório diário ao email registrado.";    
                } else {
                    echo "TRUE-Relatório foi enviado com sucesso ao email registrado.";
                }
            } else {
                echo "FALSE-Não foi possível enviar o email do relatório diário ao email registrado por não haver dados nos arquivos enviar.";    
            }
        } else {
            echo "FALSE-Não foi possível enviar o email do relatório diário ao email registrado por não haver arquivos a enviar.";    
        }
    } else {
        echo "FALSE-Não a relatório gerado para enviar ao email registrado.";    
    }
}
