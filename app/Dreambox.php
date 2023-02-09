<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use GuzzleHttp;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;

class Dreambox extends Model
{
    protected $fillable = ['name', 'hostname', 'port', 'username', 'password',
                           'multiple_tuners','audio_language','subtitle_language',
                           'epg_limit','dvr_length','buffer_time','exclude_bouquets',
                           'transcoding_profiles','interface_language'];

    private $guzzle_http_timeout = 5;
    private $status = null;
    private $online = false;

    private function zap_first($source)
    {
        // With multiple tuners, zapping is not needed, so the 'action' is always true/valid
        if ($this->multiple_tuners)
        {
            return true;
        }

        //Single tuner needs a zap first... :(
        $client = new GuzzleHttp\Client([
                        'base_uri' => 'http://' . $this->hostname . ':' . $this->port,
                        'timeout'  => $this->guzzle_http_timeout,
                    ]);

        // if (config('app.debug'))
        // {
        //     start_measure('zap_first','Zapping Dreambox to right channel');
        // }
        try
        {
            $start = microtime(true);
            $response = $client->request('GET', '/api/zap',[
                         'auth'  => [$this->username, $this->password],
                         'query' => ['sRef' => $source->service]
            ]);
            Log::debug('zap_first(): Got data from url \'/api/zap\' in ' . (microtime(true) - $start) . ' seconds');
        }
        catch (\Exception $e)
        {
            Log::exception('zap_first(): Exception zapping: ' . $e);
            $this->status = null;
            return false;
        }
        // if (config('app.debug'))
        // {
        //     stop_measure('zap_first');
        // }
        return 200 == $response->getStatusCode();
    }

    private function load_playlist($source)
    {
        $client = new GuzzleHttp\Client([
                            'base_uri' => 'http://' . $this->hostname . ':' . $this->port,
                            'timeout'  => $this->guzzle_http_timeout,
                        ]);
        // if (config('app.debug'))
        // {
        //     start_measure('load_playlist','Get streaming url from playlist');
        // }
        try
        {
            if ($source instanceof Channel)
            {
                $response = $client->request('GET', '/web/stream.m3u',[
                             'auth'  => [$this->username, $this->password],
                             'query' => ['ref' => $source->service]
                ]);
            }
            elseif ($source instanceof Recording)
            {
                $response = $client->request('GET', '/web/ts.m3u',[
                             'auth'  => [$this->username, $this->password],
                             'query' => ['file' =>  str_replace(' ','%20',$source->service)]
                ]);
            }
        }
        catch (\Exception $e)
        {
            return false;
        }
        if (config('app.debug'))
        {
            stop_measure('load_playlist');
        }

        if (200 == $response->getStatusCode())
        {
            $re = '/(?P<stream_url>http:\/\/' . $this->hostname . '.*)/m';
            preg_match_all($re, $response->getBody()->getContents(), $matches, PREG_SET_ORDER);
            if ($matches)
            {
                return trim($matches[0]['stream_url']);
            }
        }
        return false;
    }

    public function is_online()
    {
        $cache_key = 'http://' . $this->hostname . ':' . $this->port;
        if (Cache::get($cache_key)) {
            Log::debug('Return online due to caching...');
            $this->online = true;
            return True;
        }

        $client = new GuzzleHttp\Client([
                            'base_uri' => 'http://' . $this->hostname . ':' . $this->port,
                            'timeout'  => $this->guzzle_http_timeout,
                        ]);

        // if (config('app.debug'))
        // {
        //     start_measure('is_online','Dreambox online check');
        // }
        try
        {
            $start = microtime(true);
            $response = $client->request('GET', '/api/about',['auth' => [$this->username, $this->password]]);
            $this->online = true;
            Log::debug('is_online(): Got data from url \'/api/about\' in ' . (microtime(true) - $start) . ' seconds');
        }
        catch (ConnectException $e)
        {
            $this->status = null;
            return false;
        }
        catch (ClientException $e)
        {
            $this->status = null;
            return false;
        }
        // if (config('app.debug'))
        // {
        //     stop_measure('is_online');
        // }

        if (200 == $response->getStatusCode())
        {
            try
            {
                $this->status = json_decode($response->getBody()->getContents());

            }
            catch (\Exception $e)
            {
                print_r($e);
            }
        }
        Cache::put($cache_key, true, 60);
        return true;
    }

    public function status()
    {
        $status = ['online' => $this->is_online(),'running' => false];
        if (!$status['online'])
        {
            return $status;
        }
        $streamer = new Streamer($this->hostname,'');
        $streamer_status = $streamer->status();

        if ($streamer_status !== false)
        {
            // Streamer is running....
            $status = Channel::where('service',$streamer_status['service'])->first();
            if (!$status) {
                $status = Recording::where('service',str_replace('%20',' ',$streamer_status['service']))->first();//->loadMissing('channel');
            }
            $status['encoder'] = $streamer_status['encoder'];
            $status['online'] = true;
            $status['type'] = (isset($status->filesize) ? 'recording' : 'channel');
            $status['running'] = true;

        }
        return $status;
    }

    public function load_bouquets($all = true)
    {
        if (!$this->online)
        {
            return false;
        }

        $update = false;

        // For now we only load the dreambox once a day. Program data is loaded normal.
        if (Carbon::now()->floatDiffInHours(Carbon::parse($this->updated_at)) > 24 || $this->bouquets()->count() == 0) {
            $update = true;
            $client = new GuzzleHttp\Client([
                                'base_uri' => 'http://' . $this->hostname . ':' . $this->port,
                                'timeout'  => $this->guzzle_http_timeout,
                            ]);

            try
            {
                $start = microtime(true);
                $response = $client->request('GET', '/api/getservices',['auth' => [$this->username, $this->password]]);
                Log::debug('load_bouquets(): Got data from url \'http://' . $this->hostname . ':' . $this->port . '/api/getservices\' in ' . (microtime(true) - $start) . ' seconds');
            }
            catch (\Exception $e)
            {
                return false;
            }

            if (200 == $response->getStatusCode())
            {
                try
                {
                    $data = json_decode($response->getBody()->getContents());
                    $existing_bouquets = [];
                    $start = microtime(true);
                    foreach($this->bouquets()->get() as $bouquet)
                    {
                        $existing_bouquets[$bouquet->service] = $bouquet;
                    }
                    Log::debug('load_bouquets(): Loaded ' . sizeof($existing_bouquets) . ' known bouquets from DB in ' . (microtime(true) - $start) . ' seconds');
                    $position = 0;
                    $seen_bouqets = [];
                    // Create a single regex line for matching excluding bouquets
                    $exclude_bouquets = '/' . implode('|',array_map('trim',explode(',',strtolower($this->exclude_bouquets)))) . '/';

                    $start = microtime(true);
                    DB::beginTransaction();
                    foreach($data->services as $bouquet_data)
                    {
                        preg_match('/\\"(?P<bouquet>.*)\\"/', $bouquet_data->servicereference, $matches);
                        if ($matches)
                        {
                            if ('' != $this->exclude_bouquets && preg_match($exclude_bouquets,strtolower(trim($bouquet_data->servicename))) == 1)
                            {
                                continue;
                            }

                            $bouquet = $this->bouquets()->updateOrCreate(
                                ['service' => $matches['bouquet']],
                                ['name' => $bouquet_data->servicename,
                                 'position' => $position++]
                            );

                            $seen_bouqets[] = $bouquet->id;
                        }
                    }
                    DB::commit();
                    Log::debug('load_bouquets(): Added ' . sizeof($seen_bouqets) . ' new bouquets from DB in ' . (microtime(true) - $start) . ' seconds');

                }
                catch (\Exception $e)
                {
                    print_r($e);
                }
                // Clean up outdated bouquets
                $this->bouquets()->whereNotIn('id',$seen_bouqets)->delete();
                Log::debug('load_bouquets(): Loaded new ' . sizeof($seen_channels) . ' bouquets, total bouquets ' . $this->bouquets()->count() . ' known channels in ' . (microtime(true) - $start) . ' seconds');

            }
            $this->touch();
        }

        foreach($this->bouquets as $bouquet)
        {
            if ($update)
            {
                $this->load_channels($bouquet);
            }
            $this->load_programs($bouquet);
            if (!$all)
            {
                break;
            }
        }
    }

    public function load_channels(Bouquet $bouquet)
    {
        if (!$this->online)
        {
            return false;
        }

        $client = new GuzzleHttp\Client([
                            'base_uri' => 'http://' . $this->hostname . ':' . $this->port,
                            'timeout'  => $this->guzzle_http_timeout,
                        ]);

        try
        {
            Log::debug('load_channels(): Start....');
            $start = microtime(true);
            $response = $client->request('GET', '/api/getservices',[
                         'auth'  => [$this->username, $this->password],
                         'query' => ['sRef' => '1:7:1:0:0:0:0:0:0:0:FROM%20BOUQUET%20%22' . $bouquet->service . '%22%20ORDER%20BY%20bouquet']
            ]);
            Log::debug('load_channels(): Got bouquet \'' . $bouquet->name . '\' data from url \'http://' . $this->hostname . ':' . $this->port . '/api/getservices?sRef=1:7:1:0:0:0:0:0:0:0:FROM%20BOUQUET%20%22' . $bouquet->service . '%22%20ORDER%20BY%20bouquet\' in ' . (microtime(true) - $start) . ' seconds');
        }
        catch (\Exception $e)
        {
            return false;
        }

        if (200 == $response->getStatusCode())
        {
            try
            {
                $data = json_decode($response->getBody()->getContents());
                $start = microtime(true);
                $position = 0;
                $seen_channels = [];

                Log::debug('load_channels(): Processing ' . sizeof($data->services) . ' services');

                DB::beginTransaction();
                foreach($data->services as $channel_data)
                {
                    if ($channel_data->program <= 0 || '' == $channel_data->servicename) continue;

                    $channel = $this->channels()->updateOrCreate(
                        ['service' => $channel_data->servicereference],
                        ['name' => $channel_data->servicename]
                    );

                    $start_2 = microtime(true);
                    $bouquet->channels()->syncWithoutDetaching([$channel->id => ['position' => $position++]]);
                    Log::debug('load_channels(): Saved ' . $channel_data->servicename . ' channel order in ' . (microtime(true) - $start_2) . ' seconds');
                    $channel->loadIcon($this);
                    $seen_channels[] = $channel->id;
                }
                DB::commit();
                // Clean up outdated/non existing channels
                $this->channels()->whereNotIn('id', $seen_channels)->delete();
                Log::debug('load_channels(): Loaded new ' . sizeof($seen_channels) . ' channels, total channels ' . $this->channels()->count() . ' known channels in ' . (microtime(true) - $start) . ' seconds');
            }
            catch (\Exception $e)
            {
                print_r($e);
            }
        }
    }

    public function load_programs(Bouquet $bouquet, $type = 'now')
    {
        if (!$this->online)
        {
            return false;
        }

        $client = new GuzzleHttp\Client([
                            'base_uri' => 'http://' . $this->hostname . ':' . $this->port,
                            'timeout'  => $this->guzzle_http_timeout,
                        ]);

        try
        {
            $start = microtime(true);
            $response = $client->request('GET', '/api/epg' . ('now' == $type ? 'now' : 'next') ,[
                         'auth'  => [$this->username, $this->password],
                         'query' => ['bRef' => '1:7:1:0:0:0:0:0:0:0:FROM%20BOUQUET%20%22' . $bouquet->service . '%22%20ORDER%20BY%20bouquet']
            ]);
            Log::debug('load_programs(): Got bouquet ' . $bouquet->name . ' data from url \'/api/epg' . ('now' == $type ? 'now' : 'next') . '?bRef=1:7:1:0:0:0:0:0:0:0:FROM%20BOUQUET%20%22' . $bouquet->service . '%22%20ORDER%20BY%20bouquet\' in ' . (microtime(true) - $start) . ' seconds');
        }
        catch (\Exception $e)
        {
            return false;
        }

        if (200 == $response->getStatusCode())
        {
            try
            {
                $data = json_decode($response->getBody()->getContents());
                $existing_channels = [];
                foreach($this->channels()->get() as $channel)
                {
                    $existing_channels[$channel->service] = $channel;
                }

                Log::debug('load_programs(): Loaded ' . sizeof($existing_channels) . ' known channels from DB');
                $start = microtime(true);
                $program_counter = 0;
                $channel_filter = array_keys($existing_channels);
                DB::beginTransaction();
                foreach($data->events as $program_data)
                {
                    if ('' == $program_data->title || '' == $program_data->begin_timestamp || !in_array($program_data->sref, $channel_filter)) continue;
                    $channel = $existing_channels[$program_data->sref];

                    $program = $channel->programs()->updateOrCreate(
                        ['epg_id' => $program_data->id],
                        ['name' => $program_data->title,
                         'start' => $program_data->begin_timestamp,
                         'stop' => $program_data->begin_timestamp + $program_data->duration_sec,
                         'description' => $program_data->longdesc]
                    );
                    $program_counter++;
                }
                DB::commit();
                Log::debug('load_programs(): Loaded ' . $program_counter . ' programs into DB in ' . (microtime(true) - $start) . ' seconds');
            }
            catch (\Exception $e)
            {
                print_r($e);
            }

            if ('now' == $type)
            {
                $this->load_programs($bouquet,'next');
                // Delete expired programs
                DB::table('programs')->where('stop', '<', Carbon::now())->delete();
            }
        }
    }

    public function load_epg(Channel $channel)
    {
        if (!$this->online)
        {
            return false;
        }

        $client = new GuzzleHttp\Client([
                            'base_uri' => 'http://' . $this->hostname . ':' . $this->port,
                            'timeout'  => $this->guzzle_http_timeout,
                        ]);

        $last_program = $channel->programs()->orderBy('start', 'desc')->first();
        // Reload the data when less then 50% of epg limit time is left....
        if ($last_program != null && Carbon::now()->floatDiffInHours(Carbon::parse($last_program['stop'])) > ($this->epg_limit / 2.0))
        {
            return;
        }

        try
        {
            $start = microtime(true);
            $response = $client->request('GET', '/api/epgservice',[
                         'auth'  => [$this->username, $this->password],
                         'query' => ['sRef' => $channel->service]
            ]);
            Log::debug('load_epg(): Got program data ' . $channel->name . ' data from url \'/api/epgservice?sRef=' . $channel->service . '\' in ' . (microtime(true) - $start) . ' seconds');
        }
        catch (\Exception $e)
        {
            return false;
        }

        if (200 == $response->getStatusCode())
        {
            try
            {
                $data = json_decode($response->getBody()->getContents());
                DB::beginTransaction();
                foreach($data->events as $program_data)
                {
                    if ('' == $program_data->title || '' == $program_data->begin_timestamp) continue;

                    if (Carbon::now()->floatDiffInHours(Carbon::parse($program_data->begin_timestamp)) > $this->epg_limit) continue;

                    $channel->programs()->updateOrCreate(
                        ['epg_id' => $program_data->id],
                        ['channel_id' => $channel->id,
                         'name' => $program_data->title,
                         'start' => $program_data->begin_timestamp,
                         'stop' => $program_data->begin_timestamp + $program_data->duration_sec,
                         'description' => $program_data->longdesc]
                    );
                }
                DB::commit();
            }
            catch (\Exception $e)
            {
              return false;
            }
        }
    }

    public function load_recordings()
    {
        if (!$this->online)
        {
            return false;
        }

        $client = new GuzzleHttp\Client([
                            'base_uri' => 'http://' . $this->hostname . ':' . $this->port,
                            'timeout'  => $this->guzzle_http_timeout,
                        ]);

        try
        {
            $response = $client->request('GET', '/api/movielist',['auth' => [$this->username, $this->password]]);
        }
        catch (\Exception $e)
        {
            return false;
        }

        if (200 == $response->getStatusCode())
        {
            try
            {
                $data = json_decode($response->getBody()->getContents());
                $seen_recordings = [];

                $existing_channels = [];
                foreach($this->channels()->get() as $channel)
                {
                    $existing_channels[$channel->name] = $channel;
                }

                DB::beginTransaction();
                foreach($data->movies as $recording_data)
                {
                    if ($recording_data->eventname == 'epg.dat') continue;

                    // Cleanup data. Make sure we have always numbers to work with
                    $duration = array_map(function($item) {
                        return is_numeric($item) ? (int)$item : 0;
                    }, explode(':',$recording_data->length));

                    if (count($duration) == 2)
                    {
                        $duration = ($duration[0] * 60) + $duration[1];
                    }
                    else
                    {
                        $duration = $duration[0];
                    }

                    $recording = $this->recordings()->updateOrCreate(
                        ['service' => $recording_data->filename],
                        ['name' => $recording_data->eventname,
                         'start' => $recording_data->recordingtime,
                         'stop' => $recording_data->recordingtime + $duration,
                         'description' => $recording_data->description,
                         'filesize' => $recording_data->filesize]
                    );

                    $channel_name = null;
                    if (in_array($recording_data->servicename, array_keys($existing_channels)))
                    {
                        $channel_name = $recording_data->servicename;
                    }
                    elseif (in_array(preg_replace('/[ ]+hd/i','',$recording_data->servicename), array_keys($existing_channels)))
                    {
                        $channel_name = preg_replace('/[ ]+hd/i','',$recording_data->servicename);
                    }

                    if ($channel_name !== null)
                    {
                        $existing_channels[$channel_name]->recordings()->save($recording);
                    }

                    $seen_recordings[] = $recording->service;
                }
                DB::commit();
            }
            catch (\Exception $e)
            {
                print_r($e);
            }
        }
        // Clean up outdated recordings
        $this->recordings()->whereNotIn('service', $seen_recordings)->delete();
    }

    static public function execute($pCommand,$pLogLocation = '',$pWait = false)
    {
		if (($pCommand = trim($pCommand)) == "") return false;
		if ($pLogLocation == '') {
			$pCommand .= ' >/dev/null 1>/dev/null 2>/dev/null';
		} else {
			$pCommand .= ' >' . $pLogLocation . ' 1>' . $pLogLocation . '.1 2>' . $pLogLocation . '.2';
		}
		if (!$pWait) {
			$pCommand .= " & echo $!";
		}
		exec($pCommand,$pid);
		if ($pWait) {
			sleep(1);
			return -1;
		} else {
			return $pid[0]*1;
		}
	}

    public function stream($source)
    {
        $source_url = $this->load_playlist($source);
        if ($source_url === false)
        {
            return false;
        }

        if (!$this->zap_first($source))
        {
            return false;
        }

        $streamer = new Streamer($source_url,$source->name);
        if ($this->audio_language)
        {
            $streamer->language($this->audio_language);
        }
        $streamer->set_profiles($this->transcoding_profiles);
        $streamer->set_dvr($this->dvr_length);
        return $streamer->start();
    }

    public function stop()
    {
        $status = $this->status();
        if ($status['online'])
        {
            $streamer = new Streamer($this->hostname,'');
            $streamer_status = $streamer->stop();
        }
    }

    public function bouquets()
    {
        // TODO: Add filter based on $this->exclude_bouquets
        return $this->hasMany('App\Bouquet')->withCount('channels')->orderBy('position');
    }

    public function channels()
    {
        return $this->hasMany('App\Channel')->withCount('programs')->orderBy('name');
    }

    public function programs()
    {
      return $this->hasManyThrough('App\Program', 'App\Channel')->where('stop','>',Carbon::now())->orderBy('start');
    }

    public function recordings()
    {
        return $this->hasMany('App\Recording')->orderBy('start','desc');
    }
}
