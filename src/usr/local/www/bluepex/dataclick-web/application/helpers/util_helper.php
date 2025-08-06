<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function redirectBack()
{
	if (isset($_SERVER['HTTP_REFERER'])) {
		redirect($_SERVER['HTTP_REFERER']);
	} else {
		redirect("http://{$_SERVER['SERVER_NAME']}");
	}
}

function loadLanguages($load)
{
	$ci = &get_instance();

	$dataclick_session = $ci->appsession->getSession();
	$lang = isset($dataclick_session['lang']) ? $dataclick_session['lang'] : "pt-br";

	if (is_array($load)) {
		foreach ($load as $file) {
			$ci->lang->load($file, $lang);
		}
	} else {
		$ci->lang->load($load, $lang);
	}
}

function pre($params, $exit=false)
{
	echo "<pre>";
	print_r($params);
	echo "</pre>";
	if ($exit) {
		exit;
	}
}

function convertDate($oldDate, $from, $to)
{
	$newDate = DateTime::createFromFormat($from, $oldDate);
	return $newDate->format($to);
}

function subDate($oldDate, $interval, $value, $format = "Y-m-d H:i:s")
{
	if (empty($oldDate)) {
		return false;
	}
	$date = new DateTime($oldDate);
	switch ($interval) {
		case "days":
			$date = $date->sub(new DateInterval("P{$value}D"));
			break;
		case "weeks":
			$date = $date->sub(new DateInterval("P{$value}W"));
			break;
		case "months":
			$date = $date->sub(new DateInterval("P{$value}M"));
			break;
		case "years":
			$date = $date->sub(new DateInterval("P{$value}Y"));
			break;
		case "hours":
			$date = $date->sub(new DateInterval("P{$value}H"));
			break;
		case "minutes":
			$date = $date->sub(new DateInterval("P{$value}M"));
			break;
		default:
			return false;
	}
	return $date->format($format);
}

function downloadFile($file, $name = "")
{
	if (!file_exists($file)) {
		return;
	}
	if (empty($name)) {
		$name = basename($file);
	}

	$ext = substr(strrchr($file,'.'),1);
	header('Content-Description: File Transfer');
	header("Content-Disposition: attachment; filename=\"" . $name . '.' . $ext . "\"");
	if ($ext == "pdf") {
		header("Content-Type: application/pdf");
	} else if ($ext == "csv") {
		header("Content-Type: text/csv");
	}
	header('Content-Length: ' . filesize($file));
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	header('Expires: 0');
	readfile($file);
}

function bytes_to_mb($value)
{
	return str_replace(',', '', number_format($value / pow(1024, 2), 2));
}

function getWfCategories($category_id = "")
{
	$ci_instance = &get_instance();
	$categories = [];
	$categories[0] = $ci_instance->lang->line('wf_cat_0');
	$categories[1] = $ci_instance->lang->line('wf_cat_1');
	$categories[2] = $ci_instance->lang->line('wf_cat_2');
	$categories[3] = $ci_instance->lang->line('wf_cat_3');
	$categories[4] = $ci_instance->lang->line('wf_cat_4');
	$categories[5] = $ci_instance->lang->line('wf_cat_5');
	$categories[6] = $ci_instance->lang->line('wf_cat_6');
	$categories[7] = $ci_instance->lang->line('wf_cat_7');
	$categories[8] = $ci_instance->lang->line('wf_cat_8');
	$categories[9] = $ci_instance->lang->line('wf_cat_9');
	$categories[10] = $ci_instance->lang->line('wf_cat_10');
	$categories[11] = $ci_instance->lang->line('wf_cat_11');
	$categories[12] = $ci_instance->lang->line('wf_cat_12');
	$categories[13] = $ci_instance->lang->line('wf_cat_13');
	$categories[14] = $ci_instance->lang->line('wf_cat_14');
	$categories[15] = $ci_instance->lang->line('wf_cat_15');
	$categories[16] = $ci_instance->lang->line('wf_cat_16');
	$categories[17] = $ci_instance->lang->line('wf_cat_17');
	$categories[18] = $ci_instance->lang->line('wf_cat_18');
	$categories[19] = $ci_instance->lang->line('wf_cat_19');
	$categories[20] = $ci_instance->lang->line('wf_cat_20');
	$categories[21] = $ci_instance->lang->line('wf_cat_21');
	$categories[22] = $ci_instance->lang->line('wf_cat_22');
	$categories[25] = $ci_instance->lang->line('wf_cat_25');
	$categories[26] = $ci_instance->lang->line('wf_cat_26');
	$categories[27] = $ci_instance->lang->line('wf_cat_27');
	$categories[28] = $ci_instance->lang->line('wf_cat_28');
	$categories[29] = $ci_instance->lang->line('wf_cat_29');
	$categories[30] = $ci_instance->lang->line('wf_cat_30');
	$categories[31] = $ci_instance->lang->line('wf_cat_31');
	$categories[32] = $ci_instance->lang->line('wf_cat_32');
	$categories[33] = $ci_instance->lang->line('wf_cat_33');
	$categories[34] = $ci_instance->lang->line('wf_cat_34');
	$categories[35] = $ci_instance->lang->line('wf_cat_35');
	$categories[36] = $ci_instance->lang->line('wf_cat_36');
	$categories[37] = $ci_instance->lang->line('wf_cat_37');
	$categories[38] = $ci_instance->lang->line('wf_cat_38');
	$categories[39] = $ci_instance->lang->line('wf_cat_39');
	$categories[40] = $ci_instance->lang->line('wf_cat_40');
	$categories[41] = $ci_instance->lang->line('wf_cat_41');
	$categories[42] = $ci_instance->lang->line('wf_cat_42');
	$categories[43] = $ci_instance->lang->line('wf_cat_43');
	$categories[44] = $ci_instance->lang->line('wf_cat_44');
	$categories[45] = $ci_instance->lang->line('wf_cat_45');
	$categories[46] = $ci_instance->lang->line('wf_cat_46');
	$categories[47] = $ci_instance->lang->line('wf_cat_47');
	$categories[48] = $ci_instance->lang->line('wf_cat_48');
	$categories[49] = $ci_instance->lang->line('wf_cat_49');
	$categories[50] = $ci_instance->lang->line('wf_cat_50');
	$categories[99] = $ci_instance->lang->line('wf_cat_99');

	if (is_numeric($category_id)) {
		return $categories[$category_id];
	}
	return $categories;
}

