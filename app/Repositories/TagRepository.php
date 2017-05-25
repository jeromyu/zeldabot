<?php

namespace App\Repositories;

use App\Models\Tag;

class TagRepository extends BaseRepository
{
	public function __construct()
	{
		$this->model = new Tag;
	}

	public function save($data)
	{
		$tag = null;

		if (property_exists($data, 'id')) {
			$tag = $this->model->find($data->id);
		} else {
			$tag = $this->model;
		}

		if (property_exists($data, 'name')) {
			$tag->name = $data->name;
		}

		$tag->save();

		return $tag;
	}

	public function massFirstOrCreate($tag_names) {
		$tag_ids = [];

		foreach ($tag_names as $tag_name) {
			$tag = $this->model->firstOrCreate(['name' => $tag_name]);
			$tag_ids[] = $tag->id;
		}

		return $tag_ids;
	}

	public function getTagsInGroup($tag_names)
	{
		//return $this->model->whereIn('name', $tag_names)->pluck('id')->toArray();
		return $this->model->where(function($query) use ($tag_names){
			foreach ($tag_names as $tag) {
				$query->where('name', 'like', '%' . $tag . '%');
			}
		});
	}

	public function getTagsInIdsGroup($tag_ids)
	{
		return $this->model->whereIn('id', $tag_ids)->pluck('name')->toArray();
	}
}