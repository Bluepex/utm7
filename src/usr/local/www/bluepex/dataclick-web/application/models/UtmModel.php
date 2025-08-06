<?php

class UtmModel extends CI_Model
{
	private $table = "utm";

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}

	public function insert($data = array())
	{
		if ($this->db->count_all($this->table) == 0) {
			$data['is_default'] = 1;
		}
		$data = $this->defaultInsertValues($data);
		return $this->db->insert($this->table, $data);
	}

	public function getIdInserted()
	{
		return $this->db->insert_id();
	}

	public function find($id)
	{
		$this->db->where('id', $id);
		return $this->db->get($this->table)
			->row();
	}

	public function findAll()
	{
		return $this->db->get($this->table)
			->result();
	}

	public function update($id, $data = array())
	{
		$this->db->where('id', $id);
		return $this->db->update($this->table, $data);
	}

	public function delete($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete($this->table);
	}

	public function setDefault($id)
	{
		$this->db->trans_begin();

		// Set default 0 where default = 1
		$this->db->where('is_default', 1);
		$this->db->update($this->table, ["is_default" => 0]);

		$this->db->where('id', $id);
		$this->db->update($this->table, ["is_default" => 1]);

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			return false;
		} else {
			$this->db->trans_commit();
			return true;
		}
	}

	public function defaultInsertValues($data = array()) {
		$keys_default = ['port', 'logo_name', 'logo_content', 'serial', 'product_key'];
		foreach($keys_default as $key) {
			if (empty($data["{$key}"]) || $data["{$key}"] == 0) {
				$data["{$key}"] = ("{$key}" == "port") ? '80' : '0';
			}
		}
		return $data;
	}

	public function getUtmDefault()
	{
		$this->db->where('is_default', 1);
		return $this->db->get($this->table)
			->row();
	}
}
