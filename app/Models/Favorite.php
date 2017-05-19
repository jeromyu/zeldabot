<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
	protected $table = 'favorites';

	public function link()
	{
		return $this->belongsTo(Link::class, 'link_id');
	}
}