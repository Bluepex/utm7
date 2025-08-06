<?php

class SettingsModel extends CI_Model
{
	private $table = "settings";

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function save($key, $value)
	{
		$this->db->where('key', $key);
		$query = $this->db->get($this->table);
		if ($query->num_rows() > 0) {
			$this->db->where('key', $key);
			return $this->db->update($this->table, [ "value" => $value ]);
		} else {
			$data = [
				'key' => $key,
				'value' => $value,
			];
			return $this->db->insert($this->table, $data);
		}
	}

	public function get($key)
	{
		$this->db->where('key', $key);
		return $this->db->get($this->table)->row();
	}

	public function delete($key)
	{
		$this->db->where('key', $key);
		return $this->db->delete($this->table);
	}
}

