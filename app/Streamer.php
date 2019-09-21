<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use GuzzleHttp;

class Streamer
{
    private $executable = '/usr/bin/ffmpeg';

    private $auto_killer_temp_file = '.autoKiller';

    private $bitrates = [
                         //'FullHD' => ['video_bitrate' => 3000,
                         //              'width' =>  1920,
                         //              'height' => 1280,
                         //              'framerate'=> 25,
                         //              'audio_bitrate' => 160,
                         //              'h264' => '-profile:v high -level 3.2'],

                         'HDReady' => ['video_bitrate' => 1500,
                                       'width' =>  1280,
                                       'height' => 720,
                                       'framerate'=> 25,
                                       'audio_bitrate' => 112,
                                       'h264' => '-profile:v main -level 3.2'],

                         'SD'      => ['video_bitrate' => 800,
                                       'width' =>  858,
                                       'height' => 480,
                                       'framerate'=> 25,
                                       'audio_bitrate' => 96,
                                       'h264' => '-profile:v main -level 3.1'],

                         'Mobile'  => ['video_bitrate' => 512,
                                       'width' =>  640,
                                       'height' => 360,
                                       'framerate'=> 20,
                                       'audio_bitrate' => 96,
                                       'h264' => '-profile:v baseline -level 3.1']];

    private $hostname = null;
    private $port = null;
    private $authentication = null;

    private $source = null;
    private $language = null;

    private $chunktime = 2;
    private $dvrlength = 300;

    private $encoder_type = 'software';

    function __construct($hostname, $port = 80, $authentication = null) {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->authentication = $authentication;
    }

    public function channel(Channel $channel)
    {
        $this->source = $channel;
    }

    public function recording(Recording $recording)
    {
        $this->source = $recording;
    }

    public function language($language)
    {
        $this->language = $language;
    }

    public function set_dvr($length)
    {
        $this->dvrlength = $length;
    }

    private function hardware_detection()
    {
        if (file_exists('/dev/dri/renderD128'))
        {
            $this->encoder_type = 'vaapi';
        }
    }

    private function auto_killer()
    {
        $kill_timer_pid = -1;
        // Check for existing process pid
        if (Storage::exists($this->auto_killer_temp_file)) {
            $kill_timer_pid = Storage::get($this->auto_killer_temp_file);
        }
        if (is_numeric($kill_timer_pid) && $kill_timer_pid > 1) {
			// Kill old process
			$cmd = 'kill -9 ' . $kill_timer_pid;
			Dreambox::execute($cmd,'',true);
		}
        // Start new kill timer...
		$cmd = '(sleep 120;killall -9 ffmpeg; rm ' . storage_path('app/public/stream') . '/* )';
		$kill_timer_pid = Dreambox::execute($cmd);

        Storage::put($this->auto_killer_temp_file, $kill_timer_pid);
    }

    private function load_playlist()
    {
        $client = new GuzzleHttp\Client([
                            'base_uri' => 'http://' . $this->hostname ,
                            'timeout'  => 5.0,
                        ]);

        $response = null;
        if ($this->source instanceof Channel)
        {
            $response = $client->request('GET', '/web/stream.m3u?ref=' . $this->source->service, ['auth' => $this->authentication]);
        }
        elseif ($this->source instanceof Recording)
        {
            // http://hd51.theyosh.lan/web/ts.m3u?file=/recordings/20180216%202057%20-%20HISTORY%20HD%20-%20American%20Pickers.ts
            $response = $client->request('GET', '/web/ts.m3u?file=' . str_replace(' ','%20',$this->source->service), ['auth' => $this->authentication]);
        }

        if ($response != null && 200 == $response->getStatusCode())
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

    public function status($autokiller = true)
    {
        // Restart the autokiller....
        if ($autokiller)
        {
            $this->auto_killer();
        }

        $process_data = trim(shell_exec("ps ax | grep ffmpeg | grep -v grep"));
        $re = '/(?P<encoder>vaapi)? -i http:\/\/' . $this->hostname . '(:\d+)?\/(file\?file=)?(?P<service>[^ ]+)/m';
        preg_match_all($re, $process_data, $matches, PREG_SET_ORDER);
        if ($matches)
        {
            $status = Channel::where('service',$matches[0]['service'])->first();
            if (!$status) {
                $status = Recording::where('service',str_replace('%20',' ',$matches[0]['service']))->first()->loadMissing('channel');
            }
            $status['encoder'] = $matches[0]['encoder'];
            return $status;
        }

        return null;
    }

    public function stop()
    {
        $cmd = '(killall -9 ffmpeg; rm ' . storage_path('app/public/stream') . '/*)';
        Dreambox::execute($cmd,'',true);
    }


    public function start()
    {
        global $basename;
        $current_status = $this->status(false);

        if (!isset($current_status) || $current_status->service != $this->source->service)
        {
            $this->stop();

            $basename = $this->source->name;
            $main_playlist[] = '#EXTM3U';

            $source = $this->load_playlist();
            if (!$source)
            {
                return false;
            }

            if ('software' == $this->encoder_type)
            {
                $cmd = $this->executable . ' -i ' . $source;
            }
            elseif ('vaapi' == $this->encoder_type)
            {
                // HW VAAPI
                $cmd = $this->executable . ' -hwaccel vaapi -hwaccel_device /dev/dri/renderD128 -hwaccel_output_format vaapi -i ' . $source;
            }

            foreach ($this->bitrates as $bitrate_name => $bitrate)
            {
                // Scale resolution
                if ('software' == $this->encoder_type)
                {
                    $cmd .= ' -vf \'fps=' . $bitrate['framerate'] . ',scale=' . $bitrate['width'] . ':-2,format=yuv420p\' -sws_flags lanczos';
                }
                elseif ('vaapi' == $this->encoder_type)
                {
                    // HW VAAPI
                    $cmd .= ' -vf \'deinterlace_vaapi=rate=field:auto=1,fps=' . $bitrate['framerate'] . ',scale_vaapi=w=' . $bitrate['width'] . ':h=-2:format=nv12\'';
                    //$cmd .= ' -vf "format=nv12|vaapi,hwupload,scale_vaapi=w=1280:h=720:format=yuv420p,hwdownload"';
                }

                $cmd .= ' -map 0:v';

                if ($this->language != null)
                {
                    $cmd .= ' -map 0:m:language:' . $this->language . '?';
                }

                $cmd .= ' -map 0:a';

                // Audio
                $cmd .= ' -c:a aac -strict experimental -ac 3 -b:a ' . $bitrate['audio_bitrate'] . 'k -ar 48000';

                // Video
                if ('software' == $this->encoder_type)
                {
                    // -x264-params idrint=10,bframes=16,b-adapt=1,ref=3,qpmax=51,qpmin=10,me=hex,merange=16,subq=5,subme=7,qcomp=0.6,aud,keyint=10,nocabac
                    $cmd .= ' -c:v libx264 -pix_fmt yuv420p -tune film ' . $bitrate['h264'] . ' -bufsize 1M -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -bufsize 2M -r ' . $bitrate['framerate'] . ' -g ' . ($bitrate['framerate']*2);
                }
                elseif ('vaapi' == $this->encoder_type)
                {
                    // HW VAAPI
                    $cmd .= ' -c:v h264_vaapi -qp 18 -quality 1 -bf 2 -bufsize 2M -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -r ' . $bitrate['framerate']  . ' -g ' . ($bitrate['framerate']*2);
                }

                // HLS Output
                $cmd .= ' -f hls -strftime 1 -use_localtime 1 -hls_time ' . $this->chunktime . ' -hls_list_size ' . round($this->dvrlength / $this->chunktime) . ' -hls_segment_type mpegts -hls_flags +delete_segments -hls_segment_filename \'' . storage_path('app/public/stream') . '/' . Str::slug($basename . '_' . $bitrate_name, '_')  . '_%s.ts\' ' . storage_path('app/public/stream/' . Str::slug($basename . '_' . $bitrate_name, '_') . '.m3u8');

                // Main playlist info
                $main_playlist[] = '#EXT-X-STREAM-INF:PROGRAM-ID=1,CODECS="avc1.64001f,mp4a.40.2",BANDWIDTH=' . round( ($bitrate['video_bitrate'] + $bitrate['audio_bitrate']) * 1024) . ',RESOLUTION=' . $bitrate['width'] . 'x' . $bitrate['height'];
                $main_playlist[] = Str::slug($basename . '_' . $bitrate_name, '_') . '.m3u8';
            }



            // Delete old/previous files
            Storage::delete(Storage::allFiles('public/stream/'));
            // Execute on background....
            $process = Dreambox::execute($cmd,storage_path('ffmpeg_log'));

            // Create overall playlist
            for ($i = 0; $i < 30; $i++)
            {
                // Check if all bitrates playlist are generated...
                $all_done = array_count_values(array_map(function($bitrate) {
                    global $basename;
                    return (Storage::exists('public/stream/' . Str::slug($basename . '_' . $bitrate, '_') . '.m3u8') ? 1 : 0);
                },array_keys($this->bitrates)));

                // If true, alle bitrates are available. Break 30 sec check loop and continue to make the master playlist
                if (isset($all_done[1]) && $all_done[1] == count($this->bitrates)) break;
                sleep(1);
            }
            // Write main playlist
            Storage::put('public/stream/'.Str::slug($basename, '_') . '.m3u8', implode("\n",$main_playlist));
            $current_status = $this->status(false);
        }
        return asset('storage/stream/'. Str::slug($current_status->name, '_') . '.m3u8');
    }
}
