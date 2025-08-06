<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function logoutDataClick()
{
	$ci_instance = &get_instance();
	$ci_instance->session->unset_userdata('dataclick');
	header("Location: /index.php?logout");
}

function checkPermission($pages, $redirect_denied = false)
{
	$ci_instance = &get_instance();

	$dataclick_session = $ci_instance->appsession->getSession();
	if (!isset($dataclick_session['user']) || empty($dataclick_session['user'])) {
		$ci_instance->load->model("SettingsModel");
		$user_session = $ci_instance->SettingsModel->get("user_session");

		if (!empty($user_session)) {
			$ci_instance->appsession->appendSession("user", unserialize($user_session->value));
			$dataclick_session = $ci_instance->appsession->getSession();
		}
	}
	if (!isset($dataclick_session['user']['page-match'])) {
		logoutDataClick();
	}

	// Admin permission
	if (in_array("*", $dataclick_session['user']['page-match'])) {
		return true;
	}

	$denied = true;
	if (is_array($pages)) {
		foreach ($pages as $page) {
			if (in_array($page, $dataclick_session['user']['page-match'])) {
				$denied = false;
				break;
			}
		}
	} else {
		if (in_array($pages, $dataclick_session['user']['page-match'])) {
			$denied = false;
		}
	}
	if ($denied && $redirect_denied) {
		redirect("denied-page");
	} elseif (!$denied) {
		return true;
	}
}

function showFlashMessages($messages)
{
	foreach ($messages as $status => $message)
	{
		echo alertMessage($status, $message);
	}
}

function alertMessage($status, $msg)
{
	switch ($status) {
		case "ok":
			$status = "success";
			break;
		case "error":
			$status = "danger";
			break;
		case "alert":
		case "warning":
			$status = "warning";
			break;
		default:
			$status = "default";
	}
	if (is_array($msg)) {
		$msg = implode("<br />", $msg);
	}
	$message = <<<EOF
<div class="alert alert-{$status} alert-dismissible" role="alert">\n
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    {$msg}
</div>
EOF;
	return $message;
}

function checkIfUtmDefaultExists()
{
	$ci_instance = &get_instance();
	if ($ci_instance) {
		$ci_instance->load->model("UtmModel");
		$utm = $ci_instance->UtmModel->getUtmDefault();
		if (empty($utm)) {
			$ci_instance->lang->load("utm", $ci_instance->config->item("language"));
			$ci_instance->session->set_flashdata('messages', ["alert" => $ci_instance->lang->line('utm_dash_err_default_utm')]);
			redirect("utm/create");
		} else {
			return true;
		}
	}
}
