<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bouquet extends Model
{
    //
    protected $fillable = ['name', 'position','service'];

    protected $touches = ['dreambox'];

    public function channels()
    {
        return $this->belongsToMany('App\Channel','bouquet_channel')->withPivot('position')->withCount('programs')->orderBy('position');
    }
}
