<?php

//reports.php - controller
$lang['reports_generating'] = "Gerando relatório";
$lang['reports_err_generating'] = "Não foi possível gerar o relatório";
$lang['reports_success_removed'] = "Relatório removido com sucesso!";
$lang['reports_success_removed_all'] = "Relatórios removidos com sucesso!";
$lang['reports_confirm_all_files_remove'] = "Você realmente deseja deletar todos os relatórios?";
$lang['reports_file_not_search'] = "Arquivo não encontrado!";
$lang['reports_queue_success_remove'] = "Relatório removido da fila com sucesso!";
$lang['reports_queue_error_remove'] = "Não foi possível remover o relatório da fila!";
$lang['reports_provide_interv_from'] = "Você deve fornecer um intervalo (De)";
$lang['reports_provide_interv_until'] = "Você deve fornecer um intervalo (até)";

//reports.php - view
$lang['reports_header'] = "Relatórios";
$lang['reports_pre_title'] = "Relatórios";
$lang['reports_title_sub1'] = "Resumidos";
$lang['reports_title_sub2'] = "Detalhados";
$lang['reports_input_filters'] = "Filtros";
$lang['reports_input_period'] = "Período";
$lang['reports_input_registers'] = "Total de registros";
$lang['reports_input_ext'] = "Formato";
$lang['reports_btn_process'] = "Processar";
$lang['reports_btn_loading'] = "Carregando...";
$lang['reports_custom_title'] = "Exportação de Dados";
$lang['reports_input_interval'] = "Intervalo";
$lang['reports_input_from'] = "De:";
$lang['reports_input_until'] = "Até:";
$lang['reports_input_user'] = "Usuário";
$lang['reports_input_ip'] = "IP";
$lang['reports_input_categories'] = "Categorias";
$lang['reports_input_total_records'] = "Total de registros";
$lang['reports_msg_limit'] = "O limite de intervalo para consulta é de 3 mêses!";
$lang['reports_msg_select_rel'] = "Selecione o Relatório!";
$lang['reports_zone'] = "Zona";
$lang['report_check_processing'] = "Gerando Relatórios";
$lang['report_title_exports'] = "Listagem dos relatórios de exportação de dados vazios:";

//report_files.php
$lang['reports_files_header'] = "Lista de Relatórios";
$lang['reports_files_date'] = "Data/Hora";
$lang['reports_files_report'] = "Relatório";
$lang['reports_files_download'] = "Download";
$lang['reports_files_remove'] = "Remover Arquivo";
$lang['reports_all_files_remove'] = "Remover Todos os Arquivos";
$lang['reports_files_processing'] = "Processando";
$lang['reports_files_filesize'] = "Tamanho (bytes)";

//reports_helper.php
$lang['reports_helpers_prel1'] = "TOP Usuários (Consumo MB)";
$lang['reports_helpers_prel2'] = "TOP Redes Sociais (Acessos)";
$lang['reports_helpers_prel3'] = "TOP Categorias (Acessos)";
$lang['reports_helpers_prel4'] = "TOP Domínios (Consumo MB)";
$lang['reports_helpers_prel5'] = "TOP Sites (Acessos)";
$lang['reports_helpers_prel6'] = "TOP Usuários VPN (Tempo de Conexão)";
$lang['reports_helpers_prel7'] = "TOP Acessos Facebook";
$lang['reports_helpers_prel8'] = "TOP Acessos Youtube";
$lang['reports_helpers_prel9'] = "TOP Acessos Instagram";
$lang['reports_helpers_prel10'] = "TOP Acessos Linkedin";
$lang['reports_helpers_crel1'] = "Acessos por Usuários";
$lang['reports_helpers_crel2'] = "Acessos por Endereço IP";
$lang['reports_helpers_crel3'] = "Tempo de conexão VPN";
$lang['reports_helpers_crel4'] = "Captive Portal/Auditoria";
$lang['reports_helpers_crel5'] = "Top Domínios Acessados";
$lang['reports_helpers_crel6'] = "Justificativas do WebFilter";
$lang['reports_helpers_crel7'] = "Tempo de conexão VPN Detalhado";
$lang['reports_helpers_crel8'] = "Informativo de atividades do UTM";
$lang['reports_helpers_erel1'] = "Acessos por Usuários (Analítico)";
$lang['reports_helpers_erel2'] = "Acessos por Endereço IP (Analítico)";
$lang['reports_helpers_erel3'] = "Acessos por Grupo de Usuários";
$lang['reports_helpers_recent'] = "Recente";
$lang['reports_helpers_7days'] = "7 dias";
$lang['reports_helpers_30days'] = "30 dias";

//ReportsCommandLine.php
$lang['reports_cmd_err1'] = "Não foi possível executar o comando:";
$lang['reports_cmd_err2'] = "Não foi possível setar o PID do processo!";
$lang['reports_cmd_err3'] = "Não foi possível criar o PID do processo!";
$lang['reports_cmd_err4'] = "Couldn't to get the report data!";
$lang['reports_cmd_footer'] = "Gerado pelo BluePex DataClick|Página {PAGENO} de {nb}";
$lang['reports_cmd_consumer'] = "Total Consumido (MB)";
$lang['reports_cmd_access'] = "Total Acessado";
$lang['reports_cmd_categ'] = "Categorias";
$lang['reports_cmd_ip'] = "Endereço IP";
$lang['reports_cmd_username'] = "Usuário";
$lang['reports_cmd_session'] = "Sessão";
$lang['reports_cmd_zone'] = "Zona";
$lang['reports_cmd_mac'] = "Endereço MAC";
$lang['reports_cmd_beg_connect'] = "Conexão Começou em";
$lang['reports_cmd_last_active'] = "Última Atividade";
$lang['reports_cmd_disp'] = "Dispositivo";
$lang['reports_cmd_rem_ip'] = "Endereço IP Remoto";
$lang['reports_cmd_domain'] = "Domínio";
$lang['reports_cmd_connect_time'] = "Tempo de Conexão";
$lang['reports_cmd_port'] = "Porta";
$lang['reports_cmd_bytes_send'] = "Bytes Enviados";
$lang['reports_cmd_bytes_rec'] = "Bytes Recebidos";
$lang['reports_cmd_yes'] = "Sim";
$lang['reports_cmd_no'] = "Não";
$lang['reports_cmd_blocked'] = "Bloqueado";
$lang['reports_cmd_local_ip'] = "Endereço IP Local";
$lang['reports_cmd_group'] = "Grupo";
$lang['reports_cmd_date'] = "Data/Hora";
$lang['reports_cmd_default_utm'] = "UTM Default not exists!";
$lang['reports_cmd_time_connect'] = "Conexão";
$lang['reports_cmd_time_disconnect'] = "Desconexão";
$lang['reports_cmd_text_title_inputs_block'] = "Selecione um tipo de relatorio para habilitar os campos";
$lang['reports_cmd_text_title_inputs_block_user'] = "Campo bloqueado por motivos que um relatorio de tipo usuário está selecionado, altere os relatorios para liberar novamente o campo";
$lang['reports_cmd_text_title_inputs_block_group'] = "Campo bloqueado por motivos que um relatorio de tipo grupo está selecionado, altere os relatorios para liberar novamente o campo";
$lang['reports_cmd_text_title_inputs_block_ip'] = "Campo bloqueado por motivos que um relatorio de tipo IP está selecionado, altere os relatorios para liberar novamente o campo";
$lang['reports_cmd_descr'] = "Descrição";

//000x.php
$lang['reports_c_period'] = "Período (de):";
$lang['reports_c_period_until'] = "Período (até):";
$lang['reports_c_user'] = "Usuário:";
$lang['reports_c_ip'] = "IP:";
$lang['reports_c_address'] = "Endereço:";
$lang['reports_c_user'] = "Usuário";
$lang['reports_c_session'] = "Sessão";
$lang['reports_c_group'] = "Grupo / Centro de Custo";
$lang['reports_c_access'] = "Qtde. Acessos";
$lang['reports_c_consumer'] = "Consumo (MB)";
$lang['reports_c_totalmb'] = "Total (MB):";
$lang['reports_c_date'] = "Data/Hora";
$lang['reports_c_categ'] = "Categorias";
$lang['reports_c_access_time'] = "Tempo de acesso (seg)";
$lang['reports_c_remote_ip'] = "IP Remoto";
$lang['reports_c_local_ip'] = "IP local";
$lang['reports_c_port'] = "Porta";
$lang['reports_c_bytes_send'] = "Dados Enviados (bytes)";
$lang['reports_c_bytes_rec'] = "Dados Recebidos (bytes)";
$lang['reports_c_temp'] = "tempo";
$lang['reports_c_mac'] = "Endereço MAC";
$lang['reports_c_begin_activities'] = "Conexão Começou em";
$lang['reports_c_last_activities'] = "Última Atividade";
$lang['reports_c_disp'] = "Dispositivo";
$lang['reports_c_rejected'] = "Rejeitado";
$lang['reports_c_justification'] = "Justificativa";
$lang['reports_c_source'] = "Origem";
$lang['reports_c_name'] = "Nome";
$lang['reports_c_last_name'] = "Sobrenome";
$lang['reports_c_birthday'] = "Aniversário";
$lang['reports_c_form1'] = "Campo 1";
$lang['reports_c_form2'] = "Campo 2";
$lang['reports_c_time_connect'] = "Conexão";
$lang['reports_c_time_disconnect'] = "Desconexão";
$lang['reports_c_descr'] = "Descrição";

//P000x.php
$lang['reports_p_period'] = "Período (de):";
$lang['reports_p_interval_of'] = "Intervalo (de):";
$lang['reports_p_interval_until'] = "Intervalo (até):";
$lang['reports_p_utm'] = "UTM:";
$lang['reports_p_address'] = "Endereço:";
$lang['reports_p_user'] = "Usuário";
$lang['reports_p_consumer'] = "Consumo (MB)";
$lang['reports_p_access'] = "Qtde. Acessos";
$lang['reports_p_domain'] = "Domínio";
$lang['reports_p_consumer'] = "Consumo (MB)";
$lang['reports_p_totalmb'] = "Total (MB):";
$lang['reports_p_connect_time'] = "Tempo de conexão (s)";
$lang['reports_p_ip'] = "IP:";

//Report 0008
$lang['report_0008_acp_fapp'] = '%1$s: Adicionada interface: \'%2$s\'';

$lang['report_0008_session_logout'] = 'Sessão: Finalizada';
$lang['report_0008_session_login'] = 'Sessão: Iniciada';

$lang['report_0008_firewall_obj_delete'] = 'Objetos de firewall: Excluido objeto \'%1$s\'';
$lang['report_0008_firewall_obj_edit'] = 'Objetos de firewall: Editado alias \'%1$s\'';
$lang['report_0008_firewall_obj_new'] = 'Objetos de firewall: Novo alias \'%1$s\'';
$lang['report_0008_firewall_obj_dup'] = 'Objetos de firewall: Duplicado alias \'%1$s\' para \'%2$s\'';
$lang['report_0008_firewall_obj_reload_filter'] = 'Objetos de firewall: Filtro de regras do firewall recarregado';

$lang['report_0008_firewall_nat_1to1_filter_reload'] = 'Regras de Firewall Nat 1to1: Filtro de regras do firewall recarregado';
$lang['report_0008_firewall_nat_1to1_filter_reordered'] = 'Regras de Firewall Nat 1to1: Regras de firewall reordenadas';
$lang['report_0008_firewall_nat_1to1_filter_toggle_enabled'] = 'Regras de Firewall Nat 1to1: Regra \'%1$s (%2$s)\' habilitada';
$lang['report_0008_firewall_nat_1to1_filter_toggle_disabled'] = 'Regras de Firewall Nat 1to1: Regra \'%1$s (%2$s)\' desativada';
$lang['report_0008_firewall_nat_1to1_filter_delete'] = 'Regras de Firewall Nat 1to1: Regra \'%1$s (%2$s)\' excluída ';

$lang['report_0008_firewall_nat_ntp_filter_reload'] = 'Regras de Firewall Nat NTP: Filtro de regras do firewall recarregado';
$lang['report_0008_firewall_nat_ntp_filter_reordered'] = 'Regras de Firewall Nat NPT: Regras de firewall reordenadas';
$lang['report_0008_firewall_nat_ntp_filter_toggle_enabled'] = 'Regras de Firewall Nat NTP: Regra \'%1$s (%2$s)\' habilitada';
$lang['report_0008_firewall_nat_ntp_filter_toggle_disabled'] = 'Regras de Firewall Nat NTP: Regra \'%1$s (%2$s)\' desativada';
$lang['report_0008_firewall_nat_ntp_filter_delete'] = 'Regras de Firewall Nat NTP: Regra \'%1$s (%2$s)\' excluída';

$lang['report_0008_firewall_nat_ports_filter_reload'] = 'Regras de Firewall Nat Portas: Filtro de regras do firewall recarregado';
$lang['report_0008_firewall_nat_ports_filter_reordered'] = 'Regras de Firewall Nat Portas: Regras de firewall Nat reordenadas';
$lang['report_0008_firewall_nat_ports_filter_toggle_enabled'] = 'Regras de Firewall Nat Portas: Regra \'%1$s (%2$s / %3$s)\' habilitada';
$lang['report_0008_firewall_nat_ports_filter_toggle_disabled'] = 'Regras de Firewall Nat Portas: Regra \'%1$s (%2$s / %3$s)\' desativada';
$lang['report_0008_firewall_nat_ports_filter_delete'] = 'Regras de Firewall Nat Portas: Regra \'%1$s (%2$s / %3$s)\' excluída';

$lang['report_0008_acp_fapp_stream'] = '%1$s: Interfaces de fluxo/fluxo de limite de memória: \'%2$s (%3$s)\'';
$lang['report_0008_acp_fapp_edit'] = '%1$s: Editada interface: \'%2$s (%3$s)\'';
$lang['report_0008_acp_fapp_dup'] = '%1$s: Interface duplicada: \'%2$s (%3$s)\' para \'%4$s (%5$s)\'';
$lang['report_0008_acp_fapp_enabled'] = '%1$s: Interface habilitada: \'%2$s (%3$s)\'';
$lang['report_0008_acp_fapp_disabled'] = '%1$s: interface desativada: \'%2$s (%3$s)\'';
$lang['report_0008_acp_fapp_removed'] = '%1$s: interface removida: \'%2$s (%3$s)\'';
$lang['report_0008_acp_fapp_start'] = '%1$s: Iniciar interface: \'%2$s (%3$s)\'';
$lang['report_0008_acp_fapp_restart'] = ' %1$s: Reiniciar interface: \'%2$s (%3$s)\'';
$lang['report_0008_acp_fapp_stop'] = '%1$s: Interface parada: \'%2$s (%3$s)\'';
$lang['report_0008_acp_fapp_gtw_fapp_to_acp'] = 'Active Protection: Interface convertida do FirewallApp: \'%1$s\'';
$lang['report_0008_acp_fapp_gtw_acp_to_fapp'] = 'FirewallApp: Interface convertida do Active Protection: \'%1$s\'';
$lang['report_0008_acp_fapp_no_gtw_fapp_to_acp'] = 'Active Protection: Interface convertida para FirewallApp: \'%1$s\'';
$lang['report_0008_acp_fapp_no_gtw_acp_to_fapp'] = 'FirewallApp: Interface convertida para Active Protection: \'%1$s\'';

$lang['report_0008_webfilter_setting_new'] = 'Webfilter: Nova configuração de instância \'%1$s\'';
$lang['report_0008_webfilter_setting_edit'] = 'Webfilter: Editada configuração da instância \'%1$s\'';
$lang['report_0008_webfilter_setting_remove'] = 'Webfilter: Configuração de instância removida \'%1$s\'';

$lang['report_0008_webfilter_server_new'] = 'Webfilter: Nova configuração do servidor proxy \'%1$s\'';
$lang['report_0008_webfilter_server_edit'] = 'Webfilter: Editada configuração do servidor proxy \'%1$s\'';

$lang['report_0008_webfilter_server_remove'] = 'Webfilter: Deletado servidor proxy \'%1$s\'';
$lang['report_0008_webfilter_server_reorder'] = 'Webfilter: Reordenado servidores proxy';

$lang['report_0008_openvpn_wizard_new'] = 'OpenVPN: Novo servidor \'%1$s (%2$s)\'';

$lang['report_0008_firewall_nat_1to1_filter_edit'] = 'Regras de Firewall Nat 1to1: Editada regra \'%1$s (%2$s)\'';
$lang['report_0008_firewall_nat_1to1_filter_new'] = 'Regras de Firewall Nat 1to1: Nova Regra \'%1$s (%2$s)\'';
$lang['report_0008_firewall_nat_1to1_filter_dup'] = 'Regras de firewall Nat 1to1: Regra duplicada \'%1$s (%2$s)\' para \'%3$s (%4$s)\'';

$lang['report_0008_firewall_nat_ports_filter_edit'] = 'Regras de Firewall Nat Portas: Editada regra \'%1$s (%2$s / %3$s)\'';
$lang['report_0008_firewall_nat_ports_filter_new'] = 'Regras de Firewall Nat Portas: Nova Regra \'%1$s (%2$s / %3$s)\'';
$lang['report_0008_firewall_nat_ports_filter_dup'] = 'Regras de Firewall Nat Portas: Regra Duplicada \'%1$s (%2$s / %3$s)\' para \'%4$s (%5$s / %6$s)\'';

$lang['report_0008_firewall_nat_ntp_filter_edit'] = 'Regras de Firewall Nat NTP: Editada regra \'%1$s (%2$s)\'';
$lang['report_0008_firewall_nat_ntp_filter_new'] = 'Regras de Firewall Nat NTP: Nova Regra \'%1$s (%2$s)\'';
$lang['report_0008_firewall_nat_ntp_filter_dup'] = 'Regras de firewall Nat NTP: Regra duplicada \'%1$s (%2$s)\' para \'%3$s (%4$s)\'';

$lang['report_0008_firewall_nat_outbound_filter_mode'] = 'Regras de Firewall de Saída Nat: Modo de saída Nat definido como \'%1$s\'';

$lang['report_0008_firewall_nat_outbound_filter_edit'] = 'Regras de Firewall de Saída Nat: Editada regra \'%1$s (%2$s)\'';
$lang['report_0008_firewall_nat_outbound_filter_new'] = 'Regras de Firewall de Saída Nat: Nova Regra \'%1$s (%2$s)\'';
$lang['report_0008_firewall_nat_outbound_filter_dup'] = 'Regras de Firewall de Saída Nat: Regra duplicada \'%1$s (%2$s)\' para \'%3$s (%4$s)\'';

$lang['report_0008_firewall_nat_outbound_filter_reordered'] = "Regras de Firewall de Saída Nat: Regras de firewall reordenadas";
$lang['report_0008_firewall_nat_outbound_filter_reload_filter'] = "Regras de Firewall de Saída Nat: Filtro de regras do firewall recarregado";
$lang['report_0008_firewall_nat_outbound_filter_delete'] = 'Regras de Firewall de Saída Nat: Regra \'%1$s (%2$s)\' excluída';
$lang['report_0008_firewall_nat_outbound_filter_enabled'] = 'Regras de Firewall de Saída Nat: Regra \'%1$s (%2$s)\' habilitada';
$lang['report_0008_firewall_nat_outbound_filter_disabled'] = 'Regras de Firewall de Saída Nat: Regra \'%1$s (%2$s)\' desativada';

$lang['report_0008_firewall_rules_rename'] = 'Regras de Firewall: Renomeada regra \'%1$s (%2$s / %3$s)\' para \'%4$s (%5$s / %6$s)\'';
$lang['report_0008_firewall_rules_edit'] = 'Regras de Firewall: Edita-da Regra \'%1$s (%2$s / %3$s)\'';
$lang['report_0008_firewall_rules_new'] = 'Regras de Firewall: Nova Regra \'%1$s (%2$s / %3$s)\'';
$lang['report_0008_firewall_rules_dup'] = 'Regras de Firewall: Regra Duplicada \'%1$s (%2$s / %3$s)\' para \'%4$s (%5$s / %6$s)\'';

$lang['report_0008_firewall_rules_reload_filter'] = 'Regras de Firewall: Filtro de regras do firewall recarregado';
$lang['report_0008_firewall_rules_delete'] = 'Regras de Firewall: Regra \'%1$s (%2$s / %3$s)\' excluída';
$lang['report_0008_firewall_rules_killed'] = 'Regras de Firewall: O processo de filtro \'%1$s (%2$s / %3$s)\' foi eliminado';
$lang['report_0008_firewall_rules_enabled'] = 'Regras de Firewall: Regra \'%1$s (%2$s / %3$s)\' habilitada';
$lang['report_0008_firewall_rules_disabled'] = 'Regras de Firewall: Regra \'%1$s (%2$s / %3$s)\' desativada';
$lang['report_0008_firewall_rules_reordered'] = 'Regras de firewall: Regras de firewall reordenadas';
$lang['report_0008_firewall_rules_copy'] = 'Regras de Firewall: Regra Duplicada \'%1$s (%2$s / %3$s)\' para \'%4$s (%5$s / %6$s)\'';

$lang['report_0008_interface_assign_new'] = 'Interfaces de controle: Adicionar nova interface: \'%1$s (%2$s)\'';
$lang['report_0008_interface_assign_remove'] = 'Interfaces de controle: Remover interface: \'%1$s (%2$s)\'';
$lang['report_0008_interface_assign_reload_filter'] = "Interfaces de controle: Filtro de regras do firewall recarregado";
$lang['report_0008_interface_assign_enabled'] = 'Interfaces de controle: Habilitar interface: \'%1$s (%2$s)\'';
$lang['report_0008_interface_assign_disabled'] = 'Interfaces de controle: Interface desativada: \'%1$s (%2$s)\'';
$lang['report_0008_interface_assign_edit'] = 'Interfaces de controle: Editada interface: \'%1$s (%2$s)\'';

$lang['report_0008_captiveportal_zone_added_filemanager'] = 'Gerenciador de arquivos do Captive Portal: Adicionar arquivo \'%1$s\' à zona \'%2$s\'';
$lang['report_0008_captiveportal_zone_delete_filemanager'] = 'Gerenciador de arquivos do Captive Portal: Exclua o arquivo \'%1$s\' para a zona \'%2$s\'';

$lang['report_0008_captiveportal_zone_high_avaiabled_enabled'] = 'Captive Portal Altamente Avaliado: Habilitar condição para zona \'%1$s\'';
$lang['report_0008_captiveportal_zone_high_avaiabled_disabled'] = 'Captive Portal Altamente Avaliado: Condição desativada para zona \'%1$s\'';
$lang['report_0008_captiveportal_zone_high_avaiabled_edit'] = 'Captive Portal Altamente Avaliado: Edição da zona \'%1$s\'';

$lang['report_0008_captiveportal_zone_hostname_edit'] = 'Regras de nome de host do Captive Portal: Editada regra \'%1$s\' da zona \'%2$s\'';
$lang['report_0008_captiveportal_zone_hostname_new'] = 'Regras de nome de host do Captive Portal: Nova regra \'%1$s\' da zona \'%2$s\'';
$lang['report_0008_captiveportal_zone_hostname_del'] = 'Regras de nome de host do Captive Portal: Excluir regra \'%1$s\' da zona \'%2$s\'';

$lang['report_0008_captiveportal_ip_edit'] = 'Regras de IP do Captive Portal: Editada regra \'%1$s\' da zona \'%2$s\'';
$lang['report_0008_captiveportal_ip_new'] = 'Regras de IP do Captive Portal: Nova Regra \'%1$s\' da zona \'%2$s\'';
$lang['report_0008_captiveportal_ip_del'] = 'Regras de IP do Captive Portal: Excluir regra \'%1$s\' da zona \'%2$s\'';

$lang['report_0008_captiveportal_mac_edit'] = 'Regras do Captive Portal Mac: Editada regra \'%1$s\' da zona \'%2$s\'';
$lang['report_0008_captiveportal_mac_new'] = 'Regras do Captive Portal Mac: Nova regra \'%1$s\' da zona \'%2$s\'';
$lang['report_0008_captiveportal_mac_del'] = 'Regras do Captive Portal Mac: Excluir regra \'%1$s\' da zona \'%2$s\'';

$lang['report_0008_captiveportal_control_user_changed'] = 'Controle de usuário do Captive Portal: Senha do usuário \'%1$s\' alterada e desconectado da zona \'%2$s\'';
$lang['report_0008_captiveportal_control_user_applied'] = 'Controle de usuário do Captive Portal: Ações de quarentena aplicadas a usuários cativos da zona \'%1$s\'';
$lang['report_0008_captiveportal_control_user_deleted'] = 'Controle de usuário do Captive Portal: Excluir usuário \'%1$s\' e desconectado da zona \'%2$s\'';

$lang['report_0008_captiveportal_voucher_edit'] = 'Vouchers do Captive Portal: Editada lista de vouchers \'%1$s\' para login da zona \'%2$s\'';
$lang['report_0008_captiveportal_voucher_new'] = 'Vouchers do Captive Portal: Nova lista de vouchers \'%1$s\' para login da zona \'%2$s\'';
$lang['report_0008_captiveportal_voucher_del'] = 'Vouchers do Captive Portal: Excluir lista \'%1$s\' da zona \'%2$s\'';
$lang['report_0008_captiveportal_voucher_enabled'] = 'Vouchers do Captive Portal: Vouchers habilitados para login da zona \'%1$s\'';
$lang['report_0008_captiveportal_voucher_disabled'] = 'Vouchers do Captive Portal: Vouchers desativados para login da zona \'%1$s\'';
$lang['report_0008_captiveportal_voucher_edit_voucher'] = 'Vouchers do Captive Portal: Editado vouchers para login da zona \'%1$s\'';

$lang['report_0008_captiveportal_zone_added'] = 'Captive Portal: Adicionar nova zona \'%1$s\'';
$lang['report_0008_captiveportal_zone_remove'] = 'Captive Portal: Remover instância \'%1$s\'';

$lang['report_0008_captiveportal_edit'] = 'Captive Portal: Editada zona \'%1$s\'';
$lang['report_0008_captiveportal_enabled'] = 'Captive Portal: Zona habilitada \'%1$s\'';
$lang['report_0008_captiveportal_disabled'] = 'Captive Portal: Zona desativada \'%1$s\'';

$lang['report_0008_service_dhcp_edit'] = 'Serviço DHCP: Editado mapeamento \'%1$s (%2$s)\' para DHCP estático do servidor em \'%3$s (%4$s)\'';
$lang['report_0008_service_dhcp_new'] = 'Serviço DHCP: Novo mapeamento \'%1$s (%2$s)\' para DHCP estático do servidor em \'%3$s (%4$s)\'';
$lang['report_0008_service_dhcp_enabled'] = 'Serviço DHCP: Habilitar serviço na interface \'%1$s (%2$s)\'';
$lang['report_0008_service_dhcp_disabled'] = 'Serviço DHCP: Serviço desabilitado na interface \'%1$s (%2$s)\'';

$lang['report_0008_service_dhcp_pool_edit'] = 'Serviço DHCP: Editado pool DHCP \'%1$s\' na interface \'%2$s (%3$s)\'';
$lang['report_0008_service_dhcp_pool_new'] = 'Serviço DHCP: Adicionar novo pool DHCP \'%1$s\' na interface \'%2$s (%3$s)\'';
$lang['report_0008_service_dhcp_pool_edit_service'] = 'Serviço DHCP: Editado serviço na interface \'%1$s (%2$s)\'';
$lang['report_0008_service_dhcp_reload_filter'] = 'Serviço DHCP: Filtro de regras do firewall recarregado';
$lang['report_0008_service_dhcp_pool_delete'] = 'Serviço DHCP: Excluir pool de servidores DHCP \'%1$s\' na interface \'%2$s (%3$s)\'';
$lang['report_0008_service_dhcp_mapping_delete'] = 'Serviço DHCP: Excluir mapeamento para DHCP estático do servidor \'%1$s (%2$s)\' na interface \'%3$s (%4$s)\'';

$lang['report_0008_services_db_update'] = 'Serviços Bluepex: Atualizado status dos processoss';
$lang['report_0008_services_db_create'] = 'Serviços Bluepex: Criado gerenciamento dos processos';
$lang['report_0008_services_db_add'] = 'Serviços Bluepex: Adicionado novo processo ao gerenciamento';

$lang['report_0008_filter_status_filter_reload'] = 'Status do Firewall: Filtro de regras do firewall recarregado';
$lang['report_0008_filter_status_sync'] = 'Status do Firewall: Filtro de sincronização';

$lang['report_0008_services_internal_restart'] = 'Serviço Interno: \'%1$s\' sendo reiniciado';
$lang['report_0008_services_internal_start'] = 'Serviço Interno: \'%1$s\' sendo iniciado';
$lang['report_0008_services_internal_stop'] = 'Serviço Interno: \'%1$s\' sendo interrompido';

$lang['report_0008_system_auth_dup'] = 'Conexão remota LDAP/Radius: Autenticação duplicada \'%1$s\' para \'%2$s (%3$s)\'';
$lang['report_0008_system_auth_edit'] = 'Conexão remota LDAP/Radius: Editado autenticação \'%1$s (%2$s)\'';
$lang['report_0008_system_auth_new'] = 'Conexão remota LDAP/Radius: Nova autenticação \'%1$s (%2$s)\'';
$lang['report_0008_system_auth_del'] = 'Conexão remota LDAP/Radius: Excluir autenticação com \'%1$s (%2$s)\'';

$lang['report_0008_system_auth_connection_no_first_autentication_method'] = 'Conexão remota LDAP/Radius: Adicionado método de autenticação \'%1$s\'';
$lang['report_0008_system_auth_connection'] = 'Conexão remota LDAP/Radius: Método de autenticação alterado de \'%1$s\' para \'%2$s\'';

$lang['report_0008_system_user_del'] = 'Controle de usuário: Usuário(s) removido(s): \'%1$s\'';
$lang['report_0008_system_user_added_cert'] = 'Controle de usuário: Certificado adicionado \'%1$s\' para o usuário \'%2$s\'';
$lang['report_0008_system_user_remove_cert'] = 'Controle de usuário: Certificado removido \'%1$s\' do usuário \'%2$s\'';
$lang['report_0008_system_user_added_priv'] = 'Controle de usuário: Privilégio adicionado \'%1$s\' para o usuário \'%2$s\'';
$lang['report_0008_system_user_remove_priv'] = 'Controle de usuário: Privilégio removido \'%1$s\' do usuário \'%2$s\'';
$lang['report_0008_system_user_remove_edited'] = 'Controle de usuário: Usuário \'%1$s\' editado com sucesso';
$lang['report_0008_system_user_remove_created'] = 'Controle de usuário: Usuário \'%1$s\' criado com sucesso';
$lang['report_0008_system_user_renewed_pass'] = 'Controle de usuário: Renovada senha do usuário \'%1$s\'';

$lang['report_0008_system_import_sync_enabled'] = 'Conexão Remota LDAP/Radius: Sincronização habilitada com LDAP';
$lang['report_0008_system_import_sync_edit'] = 'Conexão remota LDAP/Radius: Configuração editada com LDAP';
$lang['report_0008_system_import_sync_disabled'] = 'Conexão Remota LDAP/Radius: Sincronização com LDAP desativada';

$lang['report_0008_system_import_sync_convert_users'] = 'Conexão Remota LDAP/Radius: Usuários convertidos em locais';
$lang['report_0008_system_import_sync_convert_groups'] = 'Conexão Remota LDAP/Radius: Grupos convertidos para locais';

$lang['report_0008_system_import_sync_remove_users'] = 'Conexão remota LDAP/Radius: Remover conexão do usuário LDAP \'%1$s\'';
$lang['report_0008_system_import_sync_remove_groups'] = 'Conexão remota LDAP/Radius: Remover conexão de grupo LDAP \'%1$s\'';

$lang['report_0008_openvpn_server_client_del'] = 'Cliente OpenVPN: Excluir servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_client_disabled'] = 'Cliente OpenVPN: Servidor desabilitado \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_client_enabled'] = 'Cliente OpenVPN: Servidor habilitado \'%1$s (%2$s)\'';

$lang['report_0008_openvpn_server_client_edit'] = 'Cliente OpenVPN: Editado servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_client_new'] = 'Cliente OpenVPN: Novo servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_client_edit_dup'] = 'Cliente OpenVPN: Duplicado/Editado servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_client_new_dup'] = 'Cliente OpenVPN: servidor duplicado/novo \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_client_dup'] = 'Cliente OpenVPN: Servidor duplicado de \'%1$s (%2$s)\' para \'%3$s (%4$s)\'';

$lang['report_0008_openvpn_server_csc_delete'] = 'OpenVPN CSC: Excluir servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_csc_disabled'] = 'OpenVPN CSC: Servidor desabilitado \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_csc_enabled'] = 'OpenVPN CSC: Servidor habilitado \'%1$s (%2$s)\'';

$lang['report_0008_openvpn_server_csc_edit'] = 'OpenVPN CSC: Editado servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_csc_new'] = 'OpenVPN CSC: Novo servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_csc_edit_dup'] = 'OpenVPN CSC: Servidor duplicado/editado  \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_csc_new_dup'] = 'OpenVPN CSC: Servidor duplicado/novo \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_csc_dup'] = 'OpenVPN CSC: Servidor duplicado de \'%1$s (%2$s)\' para \'%3$s (%4$s)\'';

$lang['report_0008_openvpn_server_del'] = 'OpenVPN: Excluir servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_disabled'] = 'OpenVPN: Servidor desabilitado \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_enabled'] = 'OpenVPN: Servidor habilitado \'%1$s (%2$s)\'';

$lang['report_0008_openvpn_server_edit'] = 'OpenVPN: Editado servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_new'] = 'OpenVPN: Novo servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_edit_dup'] = 'OpenVPN: Duplicado/Editado servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_new_dup'] = 'OpenVPN: Duplicado/Novo servidor \'%1$s (%2$s)\'';
$lang['report_0008_openvpn_server_dup'] = 'OpenVPN: Servidor duplicado de \'%1$s (%2$s)\' para \'%3$s (%4$s)\'';
