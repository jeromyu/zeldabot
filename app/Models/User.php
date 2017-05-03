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
}