<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

class BaseRepository
{
	function __construct()
	{
		$this->model = new Model;
	}

	public function all()
	{
		return $this->model->all();
	}

	public function find($id)
	{
		return $this->model->find($id);
	}

	public function delete($id)
	{
		return $this->model->delete($id);
	}

	public function findByColumns($columns = [])
	{
		return $this->model->where($columns)->get();
	}
}