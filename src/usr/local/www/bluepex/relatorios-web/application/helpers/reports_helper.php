<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function getReports($report = "")
{
	$ci_instance = &get_instance();
	if (!$ci_instance)
		return;

	$reports = [
		"P0001" => $ci_instance->lang->line('reports_helpers_prel1'),
		"P0002" => $ci_instance->lang->line('reports_helpers_prel2'),
		"P0003" => $ci_instance->lang->line('reports_helpers_prel3'),
		"P0004" => $ci_instance->lang->line('reports_helpers_prel4'),
		"P0005" => $ci_instance->lang->line('reports_helpers_prel5'),
		"P0006" => $ci_instance->lang->line('reports_helpers_prel6'),
		"P00061" => $ci_instance->lang->line('reports_helpers_prel61'),
		"P0007" => $ci_instance->lang->line('reports_helpers_prel7'),
		"P0008" => $ci_instance->lang->line('reports_helpers_prel8'),
		"P0009" => $ci_instance->lang->line('reports_helpers_prel9'),
		"P0010" => $ci_instance->lang->line('reports_helpers_prel10'),
		"0002" => $ci_instance->lang->line('reports_helpers_crel2'),
		"0005" => $ci_instance->lang->line('reports_helpers_crel5'),
		//"E0001" => $ci_instance->lang->line('reports_helpers_erel1'),
		"E0002" => $ci_instance->lang->line('reports_helpers_erel2'),
		//"E0003" => $ci_instance->lang->line('reports_helpers_erel3'),
	];
	if (isset($reports[$report])) {
		return $reports[$report];
	}
	return $reports;
}

function getPeriods($period = "")
{
    $ci_instance = &get_instance();
	if (!$ci_instance)
		return;

	$periods = [
		"daily"   => $ci_instance->lang->line('reports_helpers_recent'),
		"weekly"  => $ci_instance->lang->line('reports_helpers_7days'),
		"monthly" => $ci_instance->lang->line('reports_helpers_30days')
	];
	if (isset($periods[$period])) {
		return $periods[$period];
	}
	return $periods;
}

