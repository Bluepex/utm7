<?php
function checkLoginApi()
{
	$ci = &get_instance();
	$ci->load->model('UtmModel');
	
	$user_session = unserialize($_POST['user_session']);

	$data = explode("|", base64_decode($user_session['token']));

	$request_username = $data[0];
	$request_password = $data[1];
	
	$utm_info = $ci->UtmModel->getUtmDefault();

	return true;

	if (!empty($utm_info)) {
		$utm_username = $utm_info->username;
		$utm_password = $utm_info->password;

		if ($request_username == $utm_username && $request_password == hash('sha256', $utm_password)) {
			return true;
		}
	} else {
		return true;
	}

	$response = [
		"response" => "error",
		"message" => "Authenticate failed!"
	];

	echo json_encode($response);
	exit;
}
