<?php
# ====================================================================
# Copyright (C) BluePex Security Solutions - All rights reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# Written by Marcos Claudiano <marcos.claudiano@bluepex.com>, 2024
#
# ====================================================================

include('config.php');

session_start();

require "vendor/autoload.php";

use myPHPnotes\Microsoft\Auth;

define(ENV_ENTRAID, '/usr/local/bin/.env');

$data_env = [
    "AUTHORITY" => "",
    "CLIENT_ID" => "",
    "CLIENT_SECRET" => "",
    "USERNAME" => "",
    "PASS" => "",
    "CALLBACK" => ""
];

if (file_exists(ENV_ENTRAID)) {
	$env = explode("\n",file_get_contents(ENV_ENTRAID));

	foreach (array_keys($data_env) as $keys_env) {
		foreach ($env as $values_env) {
			if (strpos($values_env,$keys_env) !== false) {
				$data_env[$keys_env] = trim(explode("{$keys_env}=",$values_env)[1]);
			}
		}
	}
}

$tenant = $data_env["AUTHORITY"];
$client_id = $data_env["CLIENT_ID"];
$client_secret = $data_env["CLIENT_SECRET"];
$callback = $data_env["CALLBACK"];
$scopes = ["User.Read"];

$microsoft = new Auth(
    $tenant,
    $client_id,
    $client_secret,
    $callback,
    $scopes
);

header("location: " . $microsoft->getAuthUrl());
