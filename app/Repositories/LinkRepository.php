<?php

namespace App\Repositories;

use App\Models\Link;
use Carbon\Carbon;
use App\Repositories\UserRepository;
use App\Repositories\TagRepository;

class LinkRepository extends BaseRepository
{
	function __construct(UserRepository $user_repository, TagRepository $tag_repository)
	{
		$this->model = new Link;
		$this->user_repository = $user_repository;
		$this->tag_repository = $tag_repository;
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

	public function recommendations($user_id)
	{
		$user = $this->user_repository->find($user_id);
		$favorite_ids = $user->favorites()->pluck('id')->toArray();
		$tag_ids = $user->preferences()->pluck('id')->toArray();

		return $this->model->leftJoin('favorites', 'links.id', 'favorites.link_id')
		                   ->whereNotIn('favorites.link_id',$favorite_ids)
		                   ->with(['tags' => function($query) use ($tag_ids){
		                    	$query->whereIn('id', $tag_ids);
		                    }])
		                    ->get()
		                    ->filter(function($item, $key){
		                    	return !$item->tags->isEmpty();
		                    });
	}
}