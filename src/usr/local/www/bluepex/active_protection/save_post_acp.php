<?php

require_once("guiconfig.inc");


if (isset($_POST['save_rule'])) {

    mwexec("/bin/sh /etc/mergeListsOfACP.sh"); 	

    $customizado = "/usr/local/share/suricata/rules_acp/_emerging.customize.rules";
    $temporario = "/usr/local/share/suricata/rules_acp/_emerging.temp.rules";
    
    $valores = explode("_", $_POST['save_rule']);
    $status_change = $valores[0];
    $line_change = $valores[1];

    $regraOriginal = explode(" ", trim(shell_exec("/usr/bin/sed -n {$line_change}p {$customizado}")));
    $regraGrep =[$regraOriginal[0], $regraOriginal[1]]; 
    $regraAlterada =[$status_change, $regraOriginal[1]]; 
    $regraOriginal = implode(" ", $regraGrep);
    $regraAlterada = implode(" ", $regraAlterada);
    
    mwexec("/usr/bin/sed '{$line_change}s/{$regraOriginal}/{$regraAlterada}/' {$customizado} > {$customizado}.tmp");
    mwexec("/bin/mv {$customizado}.tmp {$customizado}");	
    mwexec("/bin/cp {$customizado} {$temporario}");	
}

if (isset($_POST['apply_rule'])) {
    $fonte = "/usr/local/share/suricata/rules_acp/_emerging.rules"; 
    $temporario = "/usr/local/share/suricata/rules_acp/_emerging.temp.rules";
    mwexec("/bin/cp {$temporario} {$fonte}");	
}


if (isset($_POST['save_ameacas_ext'])) {

    mwexec("/bin/sh /etc/mergeListsOfACP.sh"); 	

    $customizado = "/usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules";
    $temporario = "/usr/local/share/suricata/rules_acp/_ameacas_ext.temp.rules";
    
    $valores = explode("_", $_POST['save_ameacas_ext']);
    $status_change = $valores[0];
    $line_change = $valores[1];

    $regraOriginal = explode(" ", trim(shell_exec("/usr/bin/sed -n {$line_change}p {$customizado}")));
    $regraGrep =[$regraOriginal[0], $regraOriginal[1]]; 
    $regraAlterada =[$status_change, $regraOriginal[1]]; 
    $regraOriginal = implode(" ", $regraGrep);
    $regraAlterada = implode(" ", $regraAlterada);
    
    mwexec("/usr/bin/sed '{$line_change}s/{$regraOriginal}/{$regraAlterada}/' {$customizado} > {$customizado}.tmp");
    mwexec("/bin/cp {$customizado}.tmp {$customizado}");	
    mwexec("/bin/cp {$customizado} {$temporario}");	
}

if (isset($_POST['apply_ameacas_ext'])) {
    $fonte = "/usr/local/share/suricata/rules_acp/_ameacas_ext.rules"; 
    $temporario = "/usr/local/share/suricata/rules_acp/_ameacas_ext.temp.rules";
    mwexec("/bin/cp {$temporario} {$fonte}");	
}


