<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
	protected $table = 'users';

	protected $fillable = ['slack_user_id', 'slack_username'];

	public function links()
	{
		return $this->hasMany(Link::class, 'user_id');
	}

	public function favorites()
	{
		return $this->belongsToMany(Link::class, 'favorites', 'user_id', 'link_id')->withTimestamps();
	}

	public function preferences()
	{
		return $this->belongsToMany(Tag::class, 'preferences', 'user_id', 'tag_id');
	}
}