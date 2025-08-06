<?php

class ReportsQueueModel extends CI_Model
{
	private $table = "reports_queue";

	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
    
	public function insert($data = array())
	{
		return $this->db->insert($this->table, $data);
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

	public function delete($id)
	{
		$this->db->where('id', $id);
		return $this->db->delete($this->table);
	}

	public function setProcessId($id, $process_id)
	{
		$this->db->where('id', $id);
		return $this->db->update($this->table, [ "process_id" => $process_id ]);
	}
}

