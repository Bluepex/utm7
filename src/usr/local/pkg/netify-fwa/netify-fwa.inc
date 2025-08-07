<?php
/*
 * netify-fwa.inc
 *
 * Netify FWA for pfSense
 * Copyright (C) 2015-2019 eGloo Incorporated
 *
 * This program comes with ABSOLUTELY NO WARRANTY.
 *
 * This is free software, and you are welcome to redistribute it
 * under certain conditions according to the GNU General Public
 * License version 3, or (at your option) any later version.
 */

require_once("config.inc");
require_once("pfsense-utils.inc");
require_once("util.inc");
require_once('services.inc');
require_once("service-utils.inc");

require_once("/usr/local/pkg/netify-fwa/netify-fwa_defs.inc");

function netify_fwa_create_rc()
{
    $rcfile['file'] = 'netify-fwa.sh';
    $rcfile['start'] = 'mkdir -p ' .
        NETIFY_FWA_VOLATILE_STATEDIR . "\n\t" .
        NETIFY_FWA_PREFIX . "etc/rc.d/netify-fwa onestart\n\t";
    $rcfile['stop'] = NETIFY_FWA_PREFIX . "etc/rc.d/netify-fwa onestop\n\t";
    write_rcfile($rcfile);
}

function netify_fwa_make_directories()
{
    safe_mkdir(NETIFY_FWA_VOLATILE_STATEDIR);
}

function netify_fwa_is_running()
{
    #return isvalidpid(NETIFY_FWA_VOLATILE_STATEDIR . 'netify-fwa.pid');
    if (intval(trim(shell_exec("ps aux | grep nfa_main.py | grep -v ps | grep -v grep | wc -l"))) > 0) {
        return true;
    } else {
        return false;
    }
}

function netify_fwa_reload()
{
    if (! isvalidpid(NETIFY_FWA_VOLATILE_STATEDIR . 'netify-fwa.pid'))
        return;

    $pid = trim(file_get_contents(NETIFY_FWA_VOLATILE_STATEDIR . 'netify-fwa.pid'));
    posix_kill($pid, SIGHUP);
}

function netify_fwa_generate_filter_rules($type)
{
    if ($type == 'pfearly')
        return "anchor \"nfa/*\"\n";
    return '';
}

function netify_fwa_package_sync()
{
    netify_fwa_make_directories();
    netify_fwa_create_rc();
}

function netify_fwa_package_install()
{
    netify_fwa_create_rc();
}

function netify_fwa_package_deinstall()
{
}

function netify_fwa_fetch_icon($url)
{
    if (($fh = tmpfile()) === false)
        throw new exception('Error creating temp file');

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

    $response = curl_exec($ch);

    if (! curl_errno($ch)) {
        fwrite($fh, $response);
        fflush($fh);
    }
    else
        throw new exception('Fetch error: ' . $url . ': ' . curl_error($ch));

    curl_close($ch);

    return $fh;
}

function netify_fwa_load_icon($url, $fetch = true)
{
    if (strlen(trim($url)) == 0) return '';

    if (! file_exists(NETIFY_FWA_WWW_ICONS))
        if (! mkdir(NETIFY_FWA_WWW_ICONS)) return $url;

    if (($url_parts = parse_url($url)) === false)
        return $url;

    if (($ext = pathinfo($url_parts['path'], PATHINFO_EXTENSION)) === false)
        $ext = 'png';

    $id = sha1($url);
    $path = sprintf('%s/%s.%s', NETIFY_FWA_WWW_ICONS, $id, $ext);

    if (! file_exists($path)) {
        if (! $fetch) return $url;

        try {
            $fh = netify_fwa_fetch_icon($url);
            $fh_md = stream_get_meta_data($fh);

            if (copy($fh_md['uri'], $path) === false)
                return $url;
        }
        catch (exception $e) {
            return $url;
        }
    }

    $path = sprintf('icons/%s.%s', $id, $ext);

    if (($info = pathinfo($_SERVER['SCRIPT_NAME'])) === false)
        return $url;

    return (array_key_exists('extension', $info) ?
        str_replace($info['filename'] . '.' . $info['extension'],
            $path, $_SERVER['SCRIPT_NAME']) : $url);
}

function netify_fwa_load_rules()
{
    $rules = array();

    if (file_exists(NETIFY_FWA_JSON_RULES)) {
        $rules = json_decode(
            file_get_contents(NETIFY_FWA_JSON_RULES), true
        );
    }

    return $rules;
}

function netify_fwa_save_rules($rules)
{
    if (($data = json_encode($rules, JSON_PRETTY_PRINT)) === false)
        return false;

    if (file_put_contents(NETIFY_FWA_JSON_RULES, $data, LOCK_EX) === false)
        return false;

    return true;
}

function netify_fwa_update_rule($rule)
{
    $rules = netify_fwa_load_rules();

    $exists = false;

    foreach ($rules['rules'] as $id => $existing) {

        if ($rule['type'] != $existing['type']) continue;

        if ((array_key_exists('application', $rule) &&
            ! array_key_exists('application', $existing)) ||
            (! array_key_exists('application', $rule) &&
            array_key_exists('application', $existing))) continue;

        if (array_key_exists('application', $rule) &&
            array_key_exists('application', $existing) &&
            $rule['application'] != $existing['application']) continue;

        if ((array_key_exists('protocol', $rule) &&
            ! array_key_exists('protocol', $existing)) ||
            (! array_key_exists('protocol', $rule) &&
            array_key_exists('protocol', $existing))) continue;

        if (array_key_exists('protocol', $rule) &&
            array_key_exists('protocol', $existing) &&
            $rule['protocol'] != $existing['protocol']) continue;

        if ((array_key_exists('application_category', $rule) &&
            ! array_key_exists('application_category', $existing)) ||
            (! array_key_exists('application_category', $rule) &&
            array_key_exists('application_category', $existing))) continue;

        if (array_key_exists('application_category', $rule) &&
            array_key_exists('application_category', $existing) &&
            $rule['application_category'] != $existing['application_category']) continue;

        if ((array_key_exists('protocol_category', $rule) &&
            ! array_key_exists('protocol_category', $existing)) ||
            (! array_key_exists('protocol_category', $rule) &&
            array_key_exists('protocol_category', $existing))) continue;

        if (array_key_exists('protocol_category', $rule) &&
            array_key_exists('protocol_category', $existing) &&
            $rule['protocol_category'] != $existing['protocol_category']) continue;

        $exists = $id;
        break;
    }

    if ($exists === false)
        $rules['rules'][] = $rule;
    else
        unset($rules['rules'][$id]);

    $rules['rules'] = array_values($rules['rules']);

    if (netify_fwa_save_rules($rules)) {
        netify_fwa_reload();
        return true;
    }

    return false;
}

function netify_fwa_load_conf()
{
    $rules = netify_fwa_load_rules();

    $app_proto_data = json_decode(
        file_get_contents(NETIFY_FWA_JSON_APP_PROTO_DATA), true
    );

    foreach ($app_proto_data as $key => $type) {
        foreach ($type as $id => $entry)
            $app_proto_data[$key][$id]['type'] = '';
    }

    foreach ($rules['rules'] as $rule) {

        if (array_key_exists('application', $rule) &&
            array_key_exists($rule['application'], $app_proto_data['applications'])) {
            $app_proto_data['applications'][$rule['application']]['type'] = $rule['type'];
        }

        if (array_key_exists('protocol', $rule) &&
            array_key_exists($rule['protocol'], $app_proto_data['protocols']))
            $app_proto_data['protocols'][$rule['protocol']]['type'] = $rule['type'];

        if (array_key_exists('application_category', $rule) &&
            array_key_exists($rule['application_category'], $app_proto_data['application_category']))
            $app_proto_data['application_category'][$rule['application_category']]['type'] = $rule['type'];

        if (array_key_exists('protocol_category', $rule) &&
            array_key_exists($rule['protocol_category'], $app_proto_data['protocol_category']))
            $app_proto_data['protocol_category'][$rule['protocol_category']]['type'] = $rule['type'];
    }

    return $app_proto_data;
}

function netify_fwa_load_whitelist()
{
    $rules = netify_fwa_load_rules();

    if (array_key_exists('whitelist', $rules))
        return $rules['whitelist'];

    return array();
}

function netify_fwa_save_whitelist($whitelist)
{
    $rules = netify_fwa_load_rules();
    $rules['whitelist'] = array_values($whitelist);

    if (netify_fwa_save_rules($rules)) {
        netify_fwa_reload();
        return true;
    }

    return false;
}

function netify_fwa_add_whitelist_entry($entry)
{
    $cidr = '';
    if (($i = strpos($entry, '/')) !== false) {
        $cidr = substr($entry, $i + 1);
        $entry = substr($entry, 0, $i);
    }

    if (($ip = inet_pton($entry)) === false)
        return false;

    $type = 'ipv4';
    if (strstr($entry, ':') !== false)
        $type = 'ipv6';

    if (strlen($cidr)) {

        $value = intval($cidr);
        if ($value == 0) return false;

        switch ($type) {
        case 'ipv4':
            if ($value > 32) return false;
            break;
        case 'ipv6':
            if ($value > 128) return false;
            break;
        }

        $cidr = "/$cidr";
    }

    $whitelist = netify_fwa_load_whitelist();
    $whitelist[] = array('type' => $type, 'address' => $entry . $cidr);

    return netify_fwa_save_whitelist($whitelist);
}

function netify_fwa_delete_whitelist_entry($id)
{
    $whitelist = netify_fwa_load_whitelist();

    if (array_key_exists(intval($id), $whitelist))
        unset($whitelist[intval($id)]);

    return netify_fwa_save_whitelist($whitelist);
}

?>
