<?php

namespace App\Repositories;

use App\Models\Link;
use Carbon\Carbon;

class LinkRepository extends BaseRepository
{
	function __construct()
	{
		$this->model = new Link;
	}

	public function save($data_object)
	{
		if (property_exists($data_object, 'id')) {
			$link = $this->model->find($id);
		} else {
			$link = $this->model;
		}

		if (property_exists($data_object, 'url')) {
			$link->url = $data_object->url;
		}

		if (property_exists($data_object, 'user_id')) {
			$link->user_id = $data_object->user_id;
		}

		$link->save();

		if (property_exists($data_object, 'tags')) {
			$link->tags()->sync($data_object->tags);
		}

		return $link;
	}
}