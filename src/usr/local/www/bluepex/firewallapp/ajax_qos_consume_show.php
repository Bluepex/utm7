<?php
require_once("config.inc");
global $config;

if (isset($_POST['gerarStatus'])) {
    $returnArray = [];
    $arrayObj = ["qos_instagram","qos_whatsapp","qos_facebook","qos_twitter",
    "qos_tiktok","qos_news_microsoft","qos_uol","qos_google","qos_primevideo",
    "qos_youtube","qos_deezer","qos_telegram","qos_spotify","qos_teamviewer",
    "qos_twitch","qos_anydesk","qos_linkedin","qos_netflix","qos_music_amazon",
    "qos_yahoo","qos_disneyplus","qos_sites","qos_g1","qos_amazon"];
    
    foreach($arrayObj as $qosNow) {
        $tagXML = explode("_",$qosNow)[1];
        if (isset($config['ezshaper']['step7'][$tagXML]) && !empty($config['ezshaper']['step7'][$tagXML])) {
            if ($config['ezshaper']['step7'][$tagXML] != "D") {
                $returnArray[$qosNow] = "True";
            } else {
                $returnArray[$qosNow] = "False";    
            }
        } else {
            $returnArray[$qosNow] = "False";    
        }
    }

    echo json_encode($returnArray);
}
