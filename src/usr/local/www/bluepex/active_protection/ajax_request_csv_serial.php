<?php

require_once("config.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("system.inc");
require_once("firewallapp_webservice.inc");

ini_set('memory_limit', '2048M');

//Exemplo de valores a serem pesquisados
//02/07/2022-07:00:05.607114  [**] [8:9000080:1] linkedin [**] [Classification: linkedin-group] [Priority: 3] {TCP} 10.0.2.15:2438 -> 13.107.42.14:443
//"7_08_02_2022~8_51,,,1000000118,em0,WAN,match,block,in,4,0x0,,58,0,0,DF,17,UDP,53,172.217.30.170,192.168.0.116,443,28635,172.217.30.170:443,192.168.0.116:28635,33"

//Pegar o token
function get_token() {
	$url = "http://wsutm.bluepex.com:33777/api/login";

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POSTFIELDS => "user=devutm&pwd=bddda08abb3cfbc5f04ad561d880cead",
		CURLOPT_HTTPHEADER => array("Content-Type: application/x-www-form-urlencoded"),
	));

	$resp = json_decode(curl_exec($curl), true);
	curl_close($curl);

	//Confirmando se existe o token	
	$token = "";
	if (isset($resp["token"])) {
		$token = $resp["token"];
	}

	return $token;
}

function get_min_max($serial, $date_target) {
	$get_return_min_max = [];

	if (empty($serial) ||
	    empty($date_target)) {
		return $get_return_min_max;
	}
	$url = "http://wsutm.bluepex.com:33777/api/threats_id/{$serial}&{$date_target}";

	$token = get_token();
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 600,
		CURLOPT_MAXREDIRS => 100,
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_HTTPHEADER => array("Content-Type: application/json;charset=utf-8","x-access-token:$token")
	));

	$get_return_min_max = json_decode(curl_exec($curl), true);

	curl_close($curl);
	return $get_return_min_max;
}

function get_values_fast($serial, $idmin, $idmax) {
	$get_return_min_max = [];

	if (empty($serial) ||
	    empty($idmin) ||
	    empty($idmax)) {
		return $get_return_min_max;
	}
	$url = "http://wsutm.bluepex.com:33777/api/threats_fast/{$serial}&{$idmin}&{$idmax}";

	$token = get_token();
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 600,
		CURLOPT_MAXREDIRS => 100,
		CURLOPT_FOLLOWLOCATION => 1,
		CURLOPT_HTTPHEADER => array("Content-Type: application/json;charset=utf-8","x-access-token:$token")
	));

	$get_return_min_max = json_decode(curl_exec($curl), true);

	curl_close($curl);
	return $get_return_min_max;
}

/*
File -> Arquivo
ColumnCounter -> Coluna que tem os valores
columnValue -> Coluna do tipo de valor
*/

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
}

if (isset($_POST['filterACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo "<p>Arquivo de " . date('d/m/Y - H:i:s', filemtime('/usr/local/www/active_protection/filtro_acp.csv')) . "</p><a href='../active_protection/filtro_acp.csv' class='btn btn-success' download><i class='fa fa-save'></i> Download CSV Filtro ACP</a>";
    } else {
        echo "<p>Arquivo de filtro de ACP não está disponível para download.<br>Faça uma requisição para obter o arquivo.</p>";
    }
}

if (isset($_POST['filterLOG'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_log.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_log.csv')) > 0) {
        echo "<p>Arquivo de " . date('d/m/Y - H:i:s', filemtime('/usr/local/www/active_protection/filtro_log.csv')) . "</p><a href='../active_protection/filtro_log.csv' class='btn btn-success' download><i class='fa fa-save'></i> Download CSV Filtro Log</a>";
    } else {
        echo "<p>Arquivo de filtro de logs não está disponível para download.<br>Faça uma requisição para obter o arquivo.</p>";
    }
}



//Quantidade de registros LOG
if (isset($_POST['qtdValuesfilter'])) {
    $returnJson = [];
    if (file_exists('/usr/local/www/active_protection/filtro_log.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_log.csv')) > 0) {
        $valuesLines = [];
        foreach(explode("\n",shell_exec("awk -F\",\" '{print $1}' /usr/local/www/active_protection/filtro_log.csv")) as $value) {
            $valuesLines[] = intval($value);
        }
        $returnValue = 0;
        if (array_sum($valuesLines) > 0) {
            $returnValue = array_sum($valuesLines);
        }
		$returnJson[0]["value"] = $returnValue;
        $returnJson[0]["name"] = "Filtro_LOG";
    } else {
        $returnJson[0]["value"] = 0;
        $returnJson[0]["name"] = "Filtro_LOG";
    }
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        $valuesLines = [];
        foreach(explode("\n",shell_exec("awk -F\",\" '{print $2}' /usr/local/www/active_protection/filtro_acp.csv")) as $value) {
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

if (isset($_POST['qtdProtocolfilterACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo getValuesByColumn("filtro_acp.csv", 2, 6);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdProtocolfilterLOG'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_log.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_log.csv')) > 0) {
        echo getValuesByColumn("filtro_log.csv", 1, 5);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdInterfacefilterLOG'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_log.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_log.csv')) > 0) {
        echo getValuesByColumn("filtro_log.csv", 1, 4);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdPriotityfilterACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo getValuesByColumn("filtro_acp.csv", 2, 5);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdAccessRulefilterACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo getValuesByColumn("filtro_acp.csv", 2, 3);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdIPAccessExternalfilterACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo getValuesByColumn("filtro_acp.csv", 2, 9);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdIPExternalfilterACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo getValuesByColumn("filtro_acp.csv", 2, 7);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdPortInternalfilterACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo getValuesByColumn("filtro_acp.csv", 2, 10, true, false, true);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdPortExternalfilterACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo getValuesByColumn("filtro_acp.csv", 2, 8, true, false, true);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdAccessTimeLineACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo getValuesByColumn("filtro_acp.csv", 2, 1);
    } else {
        echo "false";
    }
}

if (isset($_POST['qtdAccessTimeLineLOG'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_log.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_log.csv')) > 0) {
        echo getValuesByColumn("filtro_log.csv", 1, 2);
    } else {
        echo "false";
    }
}

if (isset($_POST['displayNone'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') || file_exists('/usr/local/www/active_protection/filtro_log.csv')) {
        echo "true";
    } else {
        echo "false";
    }
}

if (isset($_POST['displayNoneACP'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo "true";
    } else {
        echo "false";
    }
}

if (isset($_POST['displayNoneLOG'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_log.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_log.csv')) > 0) {
        echo "true";
    } else {
        echo "false";
    }
}

if (isset($_POST['displayNoneAmbos'])) {
    if (file_exists('/usr/local/www/active_protection/filtro_acp.csv') && file_exists('/usr/local/www/active_protection/filtro_log.csv') && strlen(file_get_contents('/usr/local/www/active_protection/filtro_log.csv')) > 0 && strlen(file_get_contents('/usr/local/www/active_protection/filtro_acp.csv')) > 0) {
        echo "true";
    } else {
        echo "false";
    }
}

if (isset($_POST['displayRequesInRunning'])) {
	if (file_exists('/tmp/lckRequestAjaxDash')) {
		echo "true";
	} else {
		echo "false";
	}
}

if (isset($_POST['displayRequesInRunningReport'])) {
	if (file_exists('/tmp/lckRequestGenerateReport')) {
		echo "true";
	} else {
		echo "false";
	}
}

if (isset($_POST['getCSV'])) {
    if (file_exists('/etc/serial') && strlen(trim(file_get_contents('/etc/serial'))) > 0) {
        if (!file_exists('/tmp/lckRequestAjaxDash')) {
            file_put_contents('/tmp/lckRequestAjaxDash', '');
        } else {
            exit;
        }

	$serial = trim(file_get_contents('/etc/serial'));

        $todos_valores = "";
        $valores_a_se_trabalhar_acp = [];
        $valores_a_se_trabalhar_filter = [];
        
        $valores_filtrados_acp = [];
        $valores_filtrados_log = [];

	if (!isset($_POST['data_range']) ||
	    empty($_POST['data_range'])) {
		$data_filter = 1;
	}

	$data_filter = intval($_POST['data_range']);

	if ($data_filter > 90) { $data_filter = 90; }

	for (; $data_filter >= 1; $data_filter--) {
		$data_range = date('Y-m-d', strtotime("-{$data_filter} day"));
		$values = get_min_max($serial, $data_range);
		$idmin = $values[0]['idmin'];
		$idmax = $values[0]['idmax'];

		if (empty($idmin) ||
		    empty($idmax)) {
			continue;
		}

		$todos_valores = get_values_fast($serial, $idmin, $idmax);

		$quantidade_valores = count($todos_valores)-1;
		$quantidade_limite_valores = 0;
		$limite = 0;

		foreach($todos_valores as $valueNow) {
			if ($valueNow['type'] == 0) {
				if (strlen($valueNow['reg']) > 20) {
					$valores_a_se_trabalhar_acp[] = $valueNow['reg'];
				}
			}
			if ($valueNow['type'] == 1) {
				if (strlen($valueNow['reg']) > 20) {
					$valores_a_se_trabalhar_filter[] = $valueNow['reg'];
				}
			}
		}
		unset($todos_valores);

		foreach ($valores_a_se_trabalhar_acp as $linha) {
			$atual_registro = [];
			$valores = explode("_[", $linha);
			$valores = implode('___', $valores);
			$valores = explode("]", $valores);
			$valores = implode('___', $valores);
			$valores = explode('___', $valores);

			$prioridade = explode(':_', $valores[5])[1];
			$data_qtd = explode('_', $valores[0]);
			$data = $data_qtd[1] . '/' . $data_qtd[0] . '/' . $data_qtd[2];
			$qtd_acessos = $data_qtd[3];
			$destination = explode('_->_', $valores[6])[1];
			$range_destination = explode(':', $destination);
			$service_address = implode(' ', explode('_', $valores[2]));

			$classificacao = implode(' ',explode('_', explode(':_', $valores[3])[1]));
			$protocolo = explode('_{', explode('}_', $valores[6])[0])[1];
			$source = explode('}_',explode('_->_', $valores[6])[0])[1];
			$range_source = explode(':', $source);

			$atual_registro['data'] = $data;
			$atual_registro['qtd_acessos'] = $qtd_acessos;
			$atual_registro['service_address'] = $service_address;
			$atual_registro['classificacao'] = $classificacao;
			$atual_registro['prioridade'] = $prioridade;
			$atual_registro['protocolo'] = $protocolo;
			$atual_registro['source'] = $range_source[0];
			$atual_registro['port_source'] = $range_source[1];
			$atual_registro['destination'] = $range_destination[0];
			$atual_registro['port_destination'] = $range_destination[1];

			$valores_filtrados_acp[] = $atual_registro;
		}
		unset($valores_a_se_trabalhar_acp);

		foreach ($valores_a_se_trabalhar_filter as $linha) {
			$registrar = true;
			$atual_registro = [];
			$valores = explode(",", $linha);
			$horario_data = explode('~',$valores[0]);
			$data_qtd = explode('_',$horario_data[0]);
			$qtd = $data_qtd[0];
			$data = $data_qtd[1] . '/' . $data_qtd[2] . '/' . $data_qtd[3];
			if (strlen($data_acesso) > 0) {
				if ($data_acesso != $data) {
					$registrar = false;
				}
			}
			$grupo = $valores[3];
			$interface = $valores[5] . '(' . $valores[4] . ')';
			$protocolo = $valores[17];
			$atual_registro['qtd'] = $qtd;
			$atual_registro['data'] = $data;
			$atual_registro['grupo'] = $grupo;
			$atual_registro['interface'] = $interface;
			$atual_registro['protocolo'] = $protocolo;
			$valores_filtrados_filterlog[] = $atual_registro;
		}
		unset($valores_a_se_trabalhar_filter);
	}

        //Gerar csv
        if (file_exists("/usr/local/www/active_protection/filtro_acp.csv")) {
            unlink("/usr/local/www/active_protection/filtro_acp.csv");
        }
        $arquivo = fopen('/usr/local/www/active_protection/filtro_acp.csv', 'w+');
        foreach ($valores_filtrados_acp as $linha) {
            fputcsv($arquivo, $linha, $delimiter=',');
        }
        fclose($arquivo);
        if (file_exists("/usr/local/www/active_protection/filtro_log.csv")) {
            unlink("/usr/local/www/active_protection/filtro_log.csv");
        }
        $arquivo = fopen('/usr/local/www/active_protection/filtro_log.csv', 'w+');
        foreach ($valores_filtrados_filterlog as $linha) {
            fputcsv($arquivo, $linha, $delimiter=',');
        }
        fclose($arquivo);
        if (file_exists('/tmp/lckRequestAjaxDash')) {
            unlink('/tmp/lckRequestAjaxDash');
        }
    }
}   

function generateDefaultReport() {
    if (file_exists('/etc/serial') && strlen(trim(file_get_contents('/etc/serial'))) > 0) {
        if (!file_exists('/tmp/lckRequestGenerateReport')) {
            file_put_contents('/tmp/lckRequestGenerateReport', '');
        } else {
            exit;
        }

	$serial = trim(file_get_contents('/etc/serial'));

        $todos_valores = "";
        $valores_a_se_trabalhar_acp = [];
        $valores_a_se_trabalhar_filter = [];

        $valores_filtrados_acp = [];
        $valores_filtrados_log = [];

	$data_filter = (file_exists("/etc/report_acp_range") && !empty(file_get_contents("/etc/report_acp_range"))) ? intval(file_get_contents("/etc/report_acp_range")) : 1;

	for (; $data_filter >= 1; $data_filter--) {
		$data_range = date('Y-m-d', strtotime("-{$data_filter} day"));
		$values = get_min_max($serial, $data_range);
		$idmin = $values[0]['idmin'];
		$idmax = $values[0]['idmax'];

		if (empty($idmin) ||
		    empty($idmax)) {
			continue;
		}

		$todos_valores = get_values_fast($serial, $idmin, $idmax);

		$quantidade_valores = count($todos_valores)-1;
		$quantidade_limite_valores = 0;
		$limite = 0;

		foreach($todos_valores as $valueNow) {
			if ($valueNow['type'] == 0) {
				if (strlen($valueNow['reg']) > 20) {
					$valores_a_se_trabalhar_acp[] = $valueNow['reg'];
				}
			}
			if ($valueNow['type'] == 1) {
				if (strlen($valueNow['reg']) > 20) {
					$valores_a_se_trabalhar_filter[] = $valueNow['reg'];
				}
			}
		}
		unset($todos_valores);

		foreach ($valores_a_se_trabalhar_acp as $linha) {
			$atual_registro = [];
			$valores = explode("_[", $linha);
			$valores = implode('___', $valores);
			$valores = explode("]", $valores);
			$valores = implode('___', $valores);
			$valores = explode('___', $valores);

			$prioridade = explode(':_', $valores[5])[1];
			$data_qtd = explode('_', $valores[0]);
			$data = $data_qtd[1] . '/' . $data_qtd[0] . '/' . $data_qtd[2];
			$qtd_acessos = $data_qtd[3];
			$destination = explode('_->_', $valores[6])[1];
			$range_destination = explode(':', $destination);
			$service_address = implode(' ', explode('_', $valores[2]));

			$classificacao = implode(' ',explode('_', explode(':_', $valores[3])[1]));
			$protocolo = explode('_{', explode('}_', $valores[6])[0])[1];
			$source = explode('}_',explode('_->_', $valores[6])[0])[1];
			$range_source = explode(':', $source);

			$atual_registro['data'] = $data;
			$atual_registro['qtd_acessos'] = $qtd_acessos;
			$atual_registro['service_address'] = $service_address;
			$atual_registro['classificacao'] = $classificacao;
			$atual_registro['prioridade'] = $prioridade;
			$atual_registro['protocolo'] = $protocolo;
			$atual_registro['source'] = $range_source[0];
			$atual_registro['port_source'] = $range_source[1];
			$atual_registro['destination'] = $range_destination[0];
			$atual_registro['port_destination'] = $range_destination[1];

			$valores_filtrados_acp[] = $atual_registro;
		}
		unset($valores_a_se_trabalhar_acp);

		foreach ($valores_a_se_trabalhar_filter as $linha) {
			$registrar = true;
			$atual_registro = [];
			$valores = explode(",", $linha);
			$horario_data = explode('~',$valores[0]);
			$data_qtd = explode('_',$horario_data[0]);
			$qtd = $data_qtd[0];
			$data = $data_qtd[1] . '/' . $data_qtd[2] . '/' . $data_qtd[3];
			if (strlen($data_acesso) > 0) {
				if ($data_acesso != $data) {
					$registrar = false;
				}
			}
			$grupo = $valores[3];
			$interface = $valores[5] . '(' . $valores[4] . ')';
			$protocolo = $valores[17];
			$atual_registro['qtd'] = $qtd;
			$atual_registro['data'] = $data;
			$atual_registro['grupo'] = $grupo;
			$atual_registro['interface'] = $interface;
			$atual_registro['protocolo'] = $protocolo;
			$valores_filtrados_filterlog[] = $atual_registro;
		}
		unset($valores_a_se_trabalhar_filter);
	}

        //Gerar csv
        if (file_exists("/usr/local/www/active_protection/filtro_acp_report.csv")) {
            unlink("/usr/local/www/active_protection/filtro_acp_report.csv");
        }
        $arquivo = fopen('/usr/local/www/active_protection/filtro_acp_report.csv', 'w+');
        foreach ($valores_filtrados_acp as $linha) {
            fputcsv($arquivo, $linha, $delimiter=',');
        }
        if (!file_exists("/usr/local/www/active_protection/filtro_acp.csv")) {
            shell_exec("cp /usr/local/www/active_protection/filtro_acp_report.csv /usr/local/www/active_protection/filtro_acp.csv");
        }
        fclose($arquivo);
        if (file_exists("/usr/local/www/active_protection/filtro_log_report.csv")) {
            unlink("/usr/local/www/active_protection/filtro_log_report.csv");
        }
        $arquivo = fopen('/usr/local/www/active_protection/filtro_log_report.csv', 'w+');
        foreach ($valores_filtrados_filterlog as $linha) {
            fputcsv($arquivo, $linha, $delimiter=',');
        }
        if (!file_exists("/usr/local/www/active_protection/filtro_log.csv")) {
            shell_exec("cp /usr/local/www/active_protection/filtro_log_report.csv /usr/local/www/active_protection/filtro_log.csv");
        }
        fclose($arquivo);
        if (file_exists('/tmp/lckRequestGenerateReport')) {
            unlink('/tmp/lckRequestGenerateReport');
        }
    }
}
