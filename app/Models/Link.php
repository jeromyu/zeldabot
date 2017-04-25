<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
	protected $table = 'links';

	public function tags()
	{
		return $this->belongsToMany(Tag::class, 'links_tags', 'link_id', 'tag_id');
	}
}