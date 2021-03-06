<?php

namespace App\Repositories;

use App\Models\User;
use Carbon\Carbon;

class UserRepository extends BaseRepository
{
	function __construct()
	{
		$this->model = new User;
	}

	public function save($data_object)
	{
		if (property_exists($data_object, 'id')) {
			$user = $this->model->find($id);
		} else {
			$user = $this->model;
		}

		if (property_exists($data_object, 'slack_user_id')) {
			$user->slack_user_id = $data_object->slack_user_id;
		}

		if (property_exists($data_object, 'slack_username')) {
			$user->slack_username = $data_object->slack_username;
		}

		$user->save();

		return $user;
	}

	public function firstOrCreate(array $data)
	{
		return $this->model->firstOrCreate($data, ['created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
	}

	public function addFavorite($user_id, $link_id)
	{
		$user = $this->model->find($user_id);

		$user->favorites()->attach($link_id, ['created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);

		return $link_id;
	}

	public function syncTags($user_id, $tag_ids)
	{
		$user = $this->model->find($user_id);
		$user->preferences()->sync($tag_ids);

		return $tag_ids;
	}
}