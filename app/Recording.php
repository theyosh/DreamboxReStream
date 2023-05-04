<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{
    //
    protected $fillable = ['name', 'service', 'start', 'stop', 'description', 'filesize'];


    protected $appends = array('duration');

    public function getdurationAttribute()
    {
        return $this->stop->diffInSeconds($this->start);
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'expires_at',
        'start',
        'stop'
    ];

    public function channel()
    {
        return $this->belongsTo('App\Channel');
    }
}