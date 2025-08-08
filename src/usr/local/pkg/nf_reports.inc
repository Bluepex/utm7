<?php

/*  
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */

require_once('cg2_util.inc');
require_once('nf_db.inc');
require_once('nf_defines.inc');
require_once('nf_util.inc');

class NetfilterReportBackend
{
	var $database;
	var $where;
	var $config, $active_tab;

	function init()
	{
		$page_status = get_page_status();
		$this->config = $page_status['config'];
		$this->active_tab = $page_status['active_tab'];

		$display = $page_status['form_posted']
			&& empty($page_status['input_errors']);
		if (!$display) return false;

		$this->database = new NetfilterDatabase();
		$this->setWhere();

		return true;
	}

	function getLimit()
	{
		return 25;
	}

	function getCount()
	{
		$select = $this->getCountSelect();
		$res = $this->database->Query($select);

		$count = $this->database->FetchArray($res);
		$count = $count[0];

		return intval($count);
	}

	function getContent($offset)
	{
		if (isset($this->config['export_csv'])) {
			header('Content-Type: application/octet-stream');
			header('Content-Disposition: attachment; filename=webfilter.csv');
			print '"' . implode('","', $this->getHeaders()). "\"\n";
			$select = $this->getCsvSelect();
			$res = $this->database->Query($select);
			while (($row = $this->database->FetchRow($res)) != false) {
				$this->parseRow($row);
				print '"' . implode('","', $row) . "\"\n";
			}
			ob_flush();
			exit();
		}

		$select = $this->getContentSelect($offset);
		$res = $this->database->Query($select);

		$content = array();
		while (($row = $this->database->FetchRow($res)) != false) {
			$this->parseRow($row);
			$content[] = $row;
		}

		return $content;
	}

	function getWhere()
	{
		return $this->where;
	}

	function getHeaders() {}
	function setWhere() {}
	function getCsvSelect() {}
	function getCountSelect() {}
	function getTableSelect() {}

	function getContentSelect($offset)
	{
		$select = $this->getCsvSelect();
		$limit = $this->getLimit();
		return "$select LIMIT $limit OFFSET $offset";
	}

	function parseRow(&$row) {}
}

class NetfilterContentReportBackend extends NetfilterReportBackend
{
	function setWhere()
	{
		$config = $this->config;

		if ($this->active_tab == 2) {
			$where = '1 = 1';
		}
		else {
			$from = date('Y-m-d H:i:s', $config['from']);
			$upto = date('Y-m-d H:i:s', $config['upto']);
			$where = "time_date BETWEEN '$from' AND '$upto'";
		}

		if ($this->active_tab != 2) {
			if ($config['match'] == 'ip')
				$where .= " AND ip = '{$config['ip']}'";
			else if ($config['match'] == 'user')
				$where .= " AND username = '{$config['user']}'";
		}

		$conditions = array(
			'contain' => array(
				'prefix' => '%',
				'suffix' => '%',
				'operator' => 'LIKE',
				'not_operator' => 'NOT LIKE',
			),
			'start_with' => array(
				'prefix' => '',
				'suffix' => '%',
				'operator' => 'LIKE',
				'not_operator' => 'NOT LIKE',
			),
			'end_with' => array(
				'prefix' => '%',
				'suffix' => '',
				'operator' => 'LIKE',
				'not_operator' => 'NOT LIKE',
			),
			'match_exactly' => array(
				'prefix' => '',
				'suffix' => '',
				'operator' => '=',
				'not_operator' => '<>',
			),
		);
		foreach (array('filter', 'exclude') as $field) {
			$condition = $conditions[$config["{$field}_how"]];
			$operator =
				$condition[$field == 'filter' ? 'operator' : 'not_operator'];
			$prefix = $condition['prefix'];
			$suffix = $condition['suffix'];

			$what_array = explode(',', $config["{$field}_what"]);

			foreach ($what_array as $i => $what) {
				$what = trim($what);
				if ($what == '') {
					unset($what_array[$i]);
					continue;
				}
				$what_array[$i] = "url $operator '$prefix$what$suffix'";
			}

			if (!empty($what_array)) {
				if ($field == 'filter')
					$where .= ' AND (' . implode(' OR ', $what_array) . ')';
				else
					$where .= ' AND ' . implode(' AND ', $what_array);
			}
		}

		if ($this->active_tab != 2 && $this->active_tab != 3) {
			switch ($config['status']) {
				case 'blocked':
					$where .= ' AND blocked';
					break;
				case 'allowed':
					$where .= ' AND NOT blocked';
					break;
				case 'all':
					break;
				default:
					exit(); /* Not reached */
			}
		}

		$where .= ' AND elapsed > 0';

		$this->where = $where;
	}

    function getOrderBy()
    {
        switch ($this->config['order_what']) {
            case 'date':
                $order = 'time_date';
                break;
            case 'user':
                $order = 'username';
                break;
            case 'ip':
                $order = 'ip';
                break;
            case 'bytes':
                $order = 'bytes';
                break;
            case 'url':
                $order = 'url';
                break;
			case 'hits':
				$order = 'hits';
				break;
			case 'size':
				$order = 'size';
				break;
			case 'elapsed':
				$order = 'elapsed';
				break;
            default:
                exit(); /* Not reached */
        }
        if ($this->config['order_how'] == 'desc')
            $order .= ' DESC';
        else
            $order .= ' ASC';
        return $order;
    }

	function getCountSelect()
	{
		$where = $this->getWhere();
		if ($this->active_tab == 2)
			$select = 'SELECT count(*) FROM topsites_view WHERE ' . $where;
		else
            $select = 'SELECT count(*) FROM complete_url WHERE ' . $where;
		return $select;
	}

	function getCsvSelect()
	{
		$blocked = 'Blocked';
		$allowed = 'Allowed';
		$table = NETFILTER_CONTENT_TABLE;
		$where = $this->getWhere();
        $order = $this->getOrderBy();
		switch ($this->active_tab) {
			case 0:
				$select = "SELECT time_date, elapsed, bytes, CASE blocked WHEN '1' THEN '$blocked' ELSE '$allowed' END AS status, category, username, ip, url FROM complete_url WHERE $where ORDER BY $order";
				break;
			case 1:
				$select = "SELECT DATE(time_date) as time_date, SUM(elapsed) AS elapsed, SUM(bytes) AS bytes, CASE blocked WHEN '1' THEN '$blocked' ELSE '$allowed' END AS blocked, category, username, ip, url AS domain FROM domain_only WHERE $where GROUP BY time_date, blocked, category, username, ip, domain ORDER BY $order";
				break;
			case 2:
				$select = "SELECT * FROM topsites_view WHERE $where ORDER BY $order";
				break;
			case 3:
				$select = "SELECT DATE(time_date) as time_date, SUM(elapsed) AS elapsed, SUM(bytes) AS bytes, username FROM complete_url WHERE $where GROUP BY DATE(time_date), username ORDER BY $order";
				break;
			default:
				exit(); /* Not reached */
		}
		return $select;
	}

	function getHeaders()
	{
		switch ($this->active_tab) {
			case 0:
				$headers = array('Date', 'El. time', 'Bytes', 'Status', 'Categories', 'User', 'IP', 'URL');
				break;
			case 1:
				$headers = array('Date', 'El. time', 'Bytes', 'Status', 'Categories', 'User', 'IP', 'Domain');
				break;
			case 2:
				$headers = array('Site', 'Hits', 'Bandwidth used', 'Elapsed time');
				break;
			case 3:
				$headers = array('Date','El. time','Bytes','User');
				break;
			default:
				exit(); /* Not reached */
		}

        return $headers;
	}

	function formatBytes($bytes)
	{
		$bytes = intval($bytes);
		if ($bytes >= 1048) {
			$bytes = $bytes / 1024;
			$suffix = 'KiB';
			if ($bytes >= 1048) {
				$bytes = $bytes / 1024;
				$suffix = 'MiB';
				if ($bytes >= 1048) {
					$bytes = $bytes / 1024;
					$suffix = 'GiB';
					if ($bytes >= 1048) {
						$bytes = $bytes / 1024;
						$suffix = 'TiB';
					}
				}
			}
		}
		else {
			$suffix = 'B';
		}
		$bytes = intval($bytes * 100 + .5) / 100;
		$bytes = number_format($bytes, 2);
		return "$bytes $suffix";
	}

	function formatElapsed($time)
	{
		$time = intval($time);
		if ($time >= 1000) {
			$time = $time / 1000;
			$suffix = 's';
			if ($time >= 60) {
				$time = $time / 60;
				$suffix = 'm';
				if ($time >= 60) {
					$time = $time / 60;
					$suffix = 'h';
					if ($time >= 24) {
						$time = $time / 24;
						$suffix = 'd';
						if ($time >= 365) {
							$time = $time / 365;
							$suffix = 'y';
						}
					}
				}
			}
		}
		else {
			$suffix = 'ms';
		}
		$time = intval($time * 100 + .5) / 100;
		$time = number_format($time, 2);
		return "$time$suffix";
	}

	function parseRow(&$row)
	{
		if ($this->active_tab == 2) {
			$row[0] = '<a href="#" onclick="javascript:urlreport(\'' . $row[0] . '\')">' . $row[0] . '</a>';
			/* Format the bytes and elapsed time */
			$row[2] = $this->formatBytes($row[2]);
			$row[3] = $this->formatElapsed($row[3]);
		} else if ($this->active_tab == 3) {
			$row[1] = $this->formatElapsed($row[1]);
			$row[2] = $this->formatBytes($row[2]);
		} else {
			/* Format the elapsed time */
			$row[1] = $this->formatElapsed($row[1]);
			$row[2] = $this->formatBytes($row[2]);

			/* Resolve categories */
			$row[4] = implode(', ', NetfilterGetCategoriesFromClass($row[4]));

			/* Format the users */
			if (empty($row[5]))
				$row[5] = 'N/A';

			/* Limit the URL field to chars */
			if ($this->active_tab == 0) {
				if (strlen($row[7]) > 70)
					$row[7] = substr($row[7], 0, 70) . '...';
			}
		}
	}
}
?>

