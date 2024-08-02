<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use GuzzleHttp;
use GuzzleHttp\Exception\ClientException;

class Channel extends Model
{
    //
    protected $fillable = ['name', 'service', 'position', 'picon'];

    protected $appends = ['currentprogram', 'nextprogram', 'is_hd', 'is_4k'];

    private $now_next;

    // Caching function to get the data only once from the database...
    private function getNowAndNextPrograms()
    {
        $this->now_next = ['now' => null, 'next' => null];
        $programs = $this->programs()->where('stop', '>', Carbon::now())->limit(2)->get();
        foreach ($programs as $program) {
            $this->now_next[($this->now_next['now'] == null ? 'now' : 'next')] = $program;
        }
    }

    public function getCurrentProgramAttribute()
    {
        if (! isset($this->now_next)) {
            $this->getNowAndNextPrograms();
        }
        return $this->now_next['now'];
    }

    public function getNextProgramAttribute()
    {
        if (! isset($this->now_next)) {
            $this->getNowAndNextPrograms();
        }
        return $this->now_next['next'];

    }

    public function getIsHdAttribute()
    {
        return strpos($this->service, '1:0:19:') === 0;
    }

    public function getIs4KAttribute()
    {
        return strpos($this->service, '1:0:1F:') === 0;
    }

    public function loadIcon($dreambox)
    {
        $client = new GuzzleHttp\Client([
            'base_uri' => 'http://' . $dreambox->hostname . ':' . $dreambox->port,
            'timeout' => $dreambox->guzzle_http_timeout,
        ]);

        $picon_file = Str::slug($this->name, '_') . '.png';

        if (! Storage::exists('public/icon/' . $picon_file)) {
            //start_measure('load_epg_icon','Dreambox downloading picon channel ' . $channel->name);
            try {
                $pico_response = $client->request('GET', '/picon/' . str_replace(':', '_', trim($this->service, ':')) . '.png', [
                    'auth' => [$dreambox->username, $dreambox->password]
                ]);
            } catch (ClientException $e) {
                return false;
            }
            //stop_measure('load_epg_icon');
            if (200 == $pico_response->getStatusCode()) {
                Storage::put('public/icon/' . $picon_file, $pico_response->getBody());
                $this->picon = Storage::url('icon/' . $picon_file);
                $this->save();
            }
        } else {
            $this->picon = Storage::url('icon/' . $picon_file);
            $this->save();
        }
    }

    public function programs()
    {
        return $this->hasMany('App\Program')->orderBy('start');
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