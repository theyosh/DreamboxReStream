<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Channel extends Model
{
    //
    protected $fillable = ['name', 'service', 'position', 'picon'];

    protected $appends = array('currentprogram','nextprogram','is_hd');

    private $now_next;

    // Caching function to get the data only once from the database...
    private function get_now_and_next_programs()
    {
        $this->now_next = ['now' => null, 'next' => null];
        $programs = $this->programs()->limit(2)->get();
        foreach($programs as $program) {
            $this->now_next[($this->now_next['now'] == null ? 'now' : 'next')] = $program;
        }
    }

    public function getCurrentProgramAttribute()
    {
        if (!isset($this->now_next))
        {
            $this->get_now_and_next_programs();
        }
        return $this->now_next['now'];
    }

    public function getNextProgramAttribute()
    {
        if (!isset($this->now_next))
        {
            $this->get_now_and_next_programs();
        }
        return $this->now_next['next'];

    }

    public function getIsHdAttribute()
    {
        return strpos($this->service, '1:0:19:') === 0;
    }

    public function programs()
    {
        return $this->hasMany('App\Program')->where('stop','>',Carbon::now());
    }

    public function recordings()
    {
        return $this->hasMany('App\Recording');
    }

    public function bouquets()
    {
        return $this->belongsToMany('App\Bouquet')->orderBy('name');
    }

}
