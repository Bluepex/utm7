<?php

if (isset($_POST['gerarStatus'])) {
    shell_exec('python3.8 ./scrapyStatusServicesCode.py');
    $response = [];
    $json_data_fapp_services = json_decode(file_get_contents("/tmp/categorias/status_services_gerais.status.services"));
    foreach (get_object_vars($json_data_fapp_services) as $chave => $valor) {
        $response[$chave] = $valor; 
    }
    echo json_encode($response);
}