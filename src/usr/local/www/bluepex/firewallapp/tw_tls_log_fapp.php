<?php

//Necessario para pegar a interface do acp
require_once("firewallapp_webservice.inc");

ini_set('memory_limit', '256M');

//Lock generate a new process and lifetime process if slow executation
$valuesArrayDie = [];
foreach(array_filter(explode(" ", shell_exec("ps ax | grep tw_tls_log_fapp.php | grep -v grep"))) as $valuesInArrayNow) {
	$valuesArrayDie[] = $valuesInArrayNow;
}
if (floatval(explode(":", $valuesArrayDie[2])[1]) >= 49.00) {
	shell_exec("pkill -9 -af tw_tls_log_fapp");
}

if (intval(trim(shell_exec("ps ax | grep tw_tls_log_fapp | grep -v grep -c"))) > 1) {
	die;
}

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

//Enviar valores
function set_values($token, $valores) {
	$url = "http://wsutm.bluepex.com:33777/api/tls_new/$valores";
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_HTTPHEADER => array("x-access-token:$token"),
	));
	curl_exec($curl);
	curl_close($curl);
	return;
}


//Funcao principal
function push_priority_warning() {

	//Valores necessarios para fazer a operacao
	//Interface do acp
	$interface_fapp = getInterfaceNameRepFapp_tw();
	$files_target = ['tls.log','http.log'];

	//Tipo
	$type_post = 0;
	//Serial do produto
	$serial = "";
	if (file_exists("/etc/serial")) {
		$serial = trim(file_get_contents("/etc/serial"));
	}

	//Confirma se e um produto valido
	if (count($interface_fapp) > 0) {
		if (strlen($serial) > 0) {
			
			$token = get_token();
			$contador = 0;
		
			//Confirma se tem comunicacao
			if (strlen($token) > 0) {

				//Passar por todas as interfaces acp ativas
				foreach($interface_fapp as $interface_fapp_now) {
					foreach($files_target as $files_target_now) {

						//Pegando os valores da interface
						$data_grep = date('m/d/Y');#, strtotime('-1 days', strtotime(date("d-m-Y"))));
						$interfaces = explode("_",$interface_fapp_now);
						$arquivo_log = "/var/log/suricata/{$interfaces[0]}_{$interfaces[2]}{$interfaces[1]}/{$files_target_now}";
						
						if ('tls.log' == $files_target_now) {
							if (file_exists($arquivo_log)) {

								//Confirmando que existe valores no arquivo
								$contador_linha = intval(trim(shell_exec("wc -l $arquivo_log")));
								if ($contador_linha > 0) {

									$array_valores_grupo_ip = array_filter(explode("\n", shell_exec("/usr/bin/grep -r '{$data_grep}' {$arquivo_log} | grep SNI | awk -F\"> \" '{print \$2}' | awk -F\" \" '{print \$1}' | awk -F\":\" '{print \$1}'")));
									$array_valores_grupo_sni = array_filter(explode("\n", shell_exec("/usr/bin/grep -r '{$data_grep}' {$arquivo_log} | grep SNI | awk -F\"SNI='\" '{print \$2}' | awk -F\"'\" '{print \$1}'")));
									$array_valores_return = [];
									for($counter=0;$counter <= count($array_valores_grupo_ip)-1;$counter++) {
										$array_valores_return[] = "{$array_valores_grupo_ip[$counter]}___{$array_valores_grupo_sni[$counter]}";
									}
									unset($array_valores_grupo_ip);
									unset($array_valores_grupo_sni);
									$array_valores_return_tmp = [];

									$array_valores_return = array_count_values($array_valores_return);
									foreach($array_valores_return as $KeyDesc => $countValue) {
										$array_valores_return_tmp[] = "{$countValue}___{$KeyDesc}";
									}
									$array_valores_return = $array_valores_return_tmp;
									unset($array_valores_return_tmp);

									if (count($array_valores_return) > 0) {

										$contador = 0;

										foreach($array_valores_return as $array_valores_return_now) {

											if($contador==10) {
												$contador=0;
												$token = get_token();
											}

											ob_start();
											set_values($token, $serial .  "&" . $type_post . "&" . $array_valores_return_now);
											ob_end_clean();

											$contador++;

										}
									
									}
									unset($array_valores_return);								

								} else {
									echo "Nao ha valores a serem enviados";
								}
							} else {
								echo "Arquivo log não existe vazio";
							}
						} elseif ('http.log' == $files_target_now) {
							if (file_exists($arquivo_log)) {

								//Confirmando que existe valores no arquivo
								$contador_linha = intval(trim(shell_exec("wc -l $arquivo_log")));
								if ($contador_linha > 0) {

									$array_valores_grupo_ip = array_filter(explode("\n", shell_exec("/usr/bin/grep -r '{$data_grep}' {$arquivo_log} | awk '{\$1=\" \"; \$0 = $0; \$1 = \$1; print \$0}' | awk -F\"[\" '{print \$NF}' | awk -F\"> \" '{print \$2}' | awk -F\":\" '{print \$1}'")));
									$array_valores_grupo_http = array_filter(explode("\n", shell_exec("/usr/bin/grep -r '{$data_grep}' {$arquivo_log} | awk '{\$1=\" \"; \$0 = $0; \$1 = $1; print \$0}' | awk -F\"[\" '{print \$1}'")));
									$array_valores_return = [];
									for($counter=0;$counter <= count($array_valores_grupo_ip)-1;$counter++) {
										$array_valores_return[] = "{$array_valores_grupo_ip[$counter]}___{$array_valores_grupo_http[$counter]}";
									}
									unset($array_valores_grupo_ip);
									unset($array_valores_grupo_http);
									$array_valores_return_tmp = [];

									$array_valores_return = array_count_values($array_valores_return);
									foreach($array_valores_return as $KeyDesc => $countValue) {
										$array_valores_return_tmp[] = "{$countValue}___{$KeyDesc}";
									}
									$array_valores_return = $array_valores_return_tmp;
									unset($array_valores_return_tmp);

									if (count($array_valores_return) > 0) {

										$contador = 0;

										foreach($array_valores_return as $array_valores_return_now) {

											if($contador==10) {
												$contador=0;
												$token = get_token();
											}

											ob_start();
											set_values($token, $serial .  "&" . $type_post . "&" . $array_valores_return_now);
											ob_end_clean();

											$contador++;

										}
									
									}
									unset($array_valores_return);								

								} else {
									echo "Nao ha valores a serem enviados";
								}
							} else {
								echo "Arquivo log não existe vazio";
							}
						}
					}
				}
			} else {
				echo "Nao foi possivel estabelecer conexao";
			}
		} else {
			echo "Equipamento invalido";
		}
	} else {
		echo "Nenhum interface com o serviço ativado";
	}

	return;
}

push_priority_warning();