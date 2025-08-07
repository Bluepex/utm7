<?php

 /*  
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */


define('NETFILTER_DB_DIR', '/usr/local/etc/netfilter/db');
define('NETFILTER_CONF_DIR', '/usr/local/etc/netfilter');
define('NETFILTER_LOG_DIR', '/var/log');

define('NETFILTER_LISTS_DIR', NETFILTER_DB_DIR . '/lists');
define('NETFILTER_CUSTOM_LISTS_DIR', NETFILTER_CONF_DIR . '/custom');

define('NETFILTER_WHITELIST_FILE', NETFILTER_CONF_DIR . '/whitelist.txt');
define('NETFILTER_BLACKLIST_FILE', NETFILTER_CONF_DIR . '/blacklist.txt');

define('NETFILTER_REDIRECTOR_CONF_FILE', NETFILTER_CONF_DIR . '/redirector.conf');
define('NETFILTER_REDIRECTOR_RULES_FILE', NETFILTER_CONF_DIR . '/rules.conf');
define('NETFILTER_REDIRECTOR_GROUPS_FILE', NETFILTER_CONF_DIR . '/groups.conf');

define('NETFILTER_P2P_RULES_FILE', NETFILTER_CONF_DIR . '/p2p_rules.conf');

define('NETFILTER_CONTENT_LISTS_READY_FILE', '/etc/content_lists_ready');
define('NETFILTER_CONTENT_LISTS_IN_PROGRESS_FILE', '/etc/content_lists_in_progress');
define('NETFILTER_CONTENT_LISTS_ERROR_FILE', '/etc/content_lists_error');

define('NETFILTER_USERS_FILE', NETFILTER_CONF_DIR . '/users');
define('NETFILTER_GROUPS_FILE', NETFILTER_REDIRECTOR_GROUPS_FILE);

define('NETFILTER_EXPIRED_URL', 'http://www.netfilter.com.br/nfe3/expired.php?info=');

define('NETFILTER_LISTS_URL', 'http://updates.bluepex.com/webfilter/categories');

define('NETFILTER_MAX_CUSTOM_LISTS', 256);

define('ALLOWED_NO_RULE', 0);
define('ALLOWED_NO_MATCH', 1);
define('ALLOWED_WHITELIST', 2);
define('ALLOWED_CUSTOM_LIST', 3);
define('ALLOWED_ALL_ALLOWED', 4);
define('BLOCKED_MATCH', 1000);
define('BLOCKED_BLACKLIST', 1001);
define('BLOCKED_CUSTOM_LIST', 1002);
define('BLOCKED_ALL_BLOCKED', 1003);
define('BLOCKED_SUBNET_LOCKDOWN', 1004);
define('BLOCKED_EXTENSION', 1005);
define('ERROR_LICENSE', 2000);
define('ERROR_COMMUNICATION', 2001);
define('ERROR_GENERAL', 2002);

function &NetFilterGetReasonMessage($code = NULL) {
	$msgs = array(
		ALLOWED_NO_RULE => "",
		ALLOWED_NO_MATCH => "",
		ALLOWED_WHITELIST => "",
		ALLOWED_CUSTOM_LIST => "",
		ALLOWED_ALL_ALLOWED => "",
		BLOCKED_MATCH => dgettext('BluePexWebFilter',"Rule matches a blocked category"),
		BLOCKED_BLACKLIST => dgettext('BluePexWebFilter',"Blocked by blacklist"),
		BLOCKED_CUSTOM_LIST => dgettext('BluePexWebFilter',"Blocked by custom list"),
		BLOCKED_SUBNET_LOCKDOWN => "",
		BLOCKED_ALL_BLOCKED => dgettext('BluePexWebFilter',"All traffic blocked"),
		BLOCKED_EXTENSION => dgettext('BluePexWebFilter',"Blocked by file extension"),
		ERROR_LICENSE => dgettext('BluePexWebFilter',"License error"),
		ERROR_COMMUNICATION => dgettext('BluePexWebFilter',"Communication error"),
		ERROR_GENERAL => dgettext('BluePexWebFilter',"General error"),
	);

	if($code == NULL || !array_key_exists($code,$msgs)) 
		return "Unknown";
	else
		return $msgs[$code];
}

function &NetfilterGetContentCategories()
{
	$cats = array(
		1 => 'Pornography',
		'Music',
		'Video',
		'Books',
		'Job search',
		'Sports',
		'Games',
		'Entertainment',
		'E-learning',
		'Chat',
		'Newspapers',
		'Magazines',
		'Animations',
		'Tutorials',
		'Classfields',
		'Dating',
		'Curiosities',
		'Shopping',
		'News',
		'Virtual cards',
		'Esoterism',
		'Webmail',
		25 => 'Comics',
		'TV',
		'Recipes',
		'Weapons',
		'Auctions',
		'Travelling',
		'Pets',
		'Hacking',
		'Movies',
		'Photography',
		'Airlines',
		'Art',
		'Cars',
		'Banking',
		'Blogs',
		'Drugs',
		'Social networks',
		'Health',
        'Sects and cults',
        'Advertisement banners',
        'Proxy tunnels',
        'Search engines',
        'Violence',
        'Web portals',
        'Nazism and hate crimes',
        'Download sites'
	);
	return $cats;
}

function &NetfilterGetSpecialContentCategories()
{
	static $cats = array(
		23 => 'Blacklist',
		'Whitelist',
		62 => 'all_allowed',
		'all_blocked',
	);
	return $cats;
}

function &NetfilterGetAllContentCategories()
{
	$cats = NetfilterGetContentCategories()
        + NetfilterGetSpecialContentCategories();
	return $cats;
}

function &NetfilterGetDownloadBlockingCategories()
{
	$cats = array(
		65 => 'Programs',
		'Music',
		'Videos',
	);
	return $cats;
}

function &NetfilterGetProtocolCategories()
{
	static $cats = array(
        //"ftp" => "FTP",
        "pop" => "POP3",
        "smtp" => "SMTP",
        "imap" => "IMAP",
        "dns" => "DNS",
        "ipp" => "IPP",
        //"http" => "HTTP",
        "mdns" => "MDNS",
        "ntp" => "NTP",
        "netbios" => "NetBIOS",
        "nfs" => "NFS",
        "ssdp" => "SSDP",
        "bgp" => "BGP",
        "snmp" => "SNMP",
        "xdmcp" => "XDMCP",
        "smb" => "SMB",
        "syslog" => "SYSLOG",
        "dhcp" => "DHCP",
        "postgres" => "PostgreSQL",
        "mysql" => "MySQL",
        "tds" => "TDS",
        "ddl" => "DirectDownloadLink",
        "i23v5" => "I23V5",
        "apple" => "AppleJuice",
        "directconnect" => "DirectConnect",
        "socrates" => "Socrates",
        "winmx" => "WinMX",
        "manolito" => "Manolito",
        "pando" => "Pando",
        "filetopia" => "Filetopia",
        "iMESH" => "iMESH",
        "kontiki" => "Kontiki",
        "openft" => "OpenFT",
        "fasttrack" => "Kazaa/Fasttrack",
        "gnutella" => "Gnutella",
        "edonkey" => "eDonkey",
        "bittorrent" => "BitTorrent",
        "off" => "OFF",
        "avi" => "AVI",
        "flash" => "Flash",
        "ogg" => "Ogg",
        "mpeg" => "MPEG",
        "quicktime" => "QuickTime",
        "realmedia" => "RealMedia",
        "windowsmedia" => "WindowsMedia",
        "mms" => "MMS",
        "xbox" => "Xbox",
        "qq" => "QQ",
        "move" => "MOVE",
        "rtsp" => "RTSP",
        "feidian" => "Feidian",
        "icecast" => "Icecast",
        "pplive" => "PPLive",
        "ppstream" => "PPStream",
        "zattoo" => "Zatoo",
        "shoutcast" => "SHOUTcast",
        "sopcast" => "SopCast",
        "tvants" => "TVAnts",
        "tvuplayer" => "TVUplayer",
        "veohtv" => "VeohTV",
        "qqlive" => "QQLive",
        "thunder" => "Thunder/Webthunder",
        "soulseek" => "SoulSeek",
        "gadugadu" => "GaduGadu",
        "irc" => "IRC",
        "popo" => "Popo",
        "jabber" => "Jabber",
        "msn" => "MSN Messenger",
        "oscar" => "Oscar (AOL Messenger)",
        "yahoo" => "Yahoo! Messenger",
        "battlefield" => "Battlefield",
        "quake" => "Quake",
        "secondlife" => "Second Life",
        "steam" => "Steam",
        "hl2" => "Half-Life 2",
        "worldofwarcraft" => "World of Warcraft",
        "telnet" => "Telnet",
        "stun" => "STUN",
        "ipsec" => "IPsec",
        "gre" => "GRE",
        "icmp" => "ICMP",
        "igmp" => "IGMP",
        "egp" => "egp",
        "sctp" => "sctp",
        "ospf" => "OSPF",
        "ipip" => "IP in IP",
        "rtp" => "RTP",
        "rdp" => "RDP (Remote Desktop)",
        "vnc" => "VNC",
        "pcanywhere" => "PCAnywhere",
        "ssl" => "SSL",
        "ssh" => "SSH",
        "usenet" => "Usenet",
        "mgcp" => "MGPC",
        "iax" => "IAX",
        'tftp' => 'TFTP',
        'afp' => 'AFP',
        'stealthnet' => 'StealthNet',
        'aimini' => 'Aimini'
	);
	return $cats;
}

function &WebfilterGetCategoriesDef() {
	$cats = array(
		1 => "PORN",
		"MUSICA",
		"VIDEO",
		"LIVRO",
		"EMPREGO",
		"ESPORTE",
		"JOGOS",
		"HUMOR",
		"ENSINOADIS",
		"BATEPAPO",
		"JORNAL",
		"REVISTA",
		"ANIMACOES",
		"TUTORIAIS",
		"CLASSIFICA",
		"NAMOROONLI",
		"CURIOSIDAD",
		"COMPRAS",
		"NOTICIAS",
		"CARTOES_VI",
		"ESOTERISMO",
		"WEBMAIL",
		25 => "QUADRINHOS",
		"TELEVISAO",
		"CULINARIA",
		"ARMAS",
		"LEILOES",
		"VIAGEM",
		"ANIMAIS",
		"HACKERS",
		"FILMES",
		"FOTOGRAFIA",
		"AEREAS",
		"ARTES",
		"AUTOS",
		"BANCOS",
		"BLOGFLOG",
		"DROGAS",
		"RELACIONAM",
		"SAUDE",
		"RITUAISSAT",
		"BANNER",
		"PROXY",
		"SITEBUSCA",
		"VIOLENCIA",
		"PORTAL",
		"NAZISMO",
		"DOWNLOAD"
	);
	return $cats;
}
?>
