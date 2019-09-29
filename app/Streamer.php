<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use GuzzleHttp;

class Streamer
{
    const executable = '/usr/bin/ffmpeg';

    const bitrates = [
                        'FullHD' =>  ['name' => 'Full HD',
                                      'video_bitrate' => 3000,
                                      'width' =>  1920,
                                      'height' => 1280,
                                      'framerate'=> 25,
                                      'audio_bitrate' => 160,
                                      'h264' => '-profile:v high -level 4.1'],

                        'HDReady' => ['name' => 'HD Ready',
                                      'video_bitrate' => 1500,
                                      'width' =>  1280,
                                      'height' => 720,
                                      'framerate'=> 25,
                                      'audio_bitrate' => 128,
                                      'h264' => '-profile:v high -level 4.0'],

                        'SD'      => ['name' => 'SD',
                                      'video_bitrate' => 800,
                                      'width' =>  858,
                                      'height' => 480,
                                      'framerate'=> 25,
                                      'audio_bitrate' => 112,
                                      'h264' => '-profile:v main -level 3.1'],

                        'Mobile'  => ['name' => 'Mobile',
                                      'video_bitrate' => 512,
                                      'width' =>  640,
                                      'height' => 360,
                                      'framerate'=> 20,
                                      'audio_bitrate' => 96,
                                      'h264' => '-profile:v baseline -level 3.1'],

                        'Audio'   => ['name' => 'Audio Only',
                                      'audio_bitrate' => 96]];

    private $auto_killer_temp_file = '.autoKiller';

    private $buffer_time = 3;
    private $chunktime = 2;
    private $dvrlength = 300;
    private $encoder_type = 'software';


    private $source_url = null;
    private $source_name = null;
    private $language = null;

    function __construct($source_url, $source_name)
    {
        $this->set_source($source_url, $source_name);
    }

    static function profiles()
    {
        return Streamer::bitrates;
    }

    public function set_source($source_url,$source_name)
    {
        $this->source_url  = $source_url;
        $this->source_name = $source_name;
    }

    public function language($language)
    {
        $this->language = $language;
    }

    public function set_profiles($profiles)
    {
        $this->enabled_profiles = explode(',',$profiles);
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

    private function auto_killer($streamer_pid)
    {
        $kill_timer_pid = -1;
        // Check for existing process pid
        if (Storage::exists($this->auto_killer_temp_file)) {
            $kill_timer_pid = Storage::get($this->auto_killer_temp_file);
        }
        if (is_numeric($kill_timer_pid) && $kill_timer_pid > 1) {
			// Kill old process
			Dreambox::execute('kill -9 ' . $kill_timer_pid,'',true);
		}
        // Start new kill timer...
		$cmd = '(sleep 120;kill -9 ' . $streamer_pid . '; rm ' . storage_path('app/public/stream') . '/* )';
		$kill_timer_pid = Dreambox::execute($cmd);
        // Store new pid to temp file
        Storage::put($this->auto_killer_temp_file, $kill_timer_pid);
    }

    public function status($autokiller = true)
    {
        $status = ['source' => null, 'service' => null, 'encoder' => null];
        $process_data = trim(shell_exec("ps ax | grep ffmpeg | grep -v grep"));
        $re = '/^(?P<pid>\d+).*ffmpeg (?P<encoder>vaapi)?-i (?P<source>http:\/\/[^ ]+(:\d+)?\/(file\?file=)?(?P<service>[^ ]+))/m';
        preg_match_all($re, $process_data, $matches, PREG_SET_ORDER);
        if ($matches && stripos($this->source_url,$matches[0]['source']) == 0)
        {
            $status['source'] = $matches[0]['source'];
            $status['service'] = $matches[0]['service'];
            $status['pid'] = $matches[0]['pid'];
            // Restart the autokiller....
            if ($autokiller)
            {
                $this->auto_killer($status['pid']);
            }
            if (!empty($status['encoder']))
            {
                $status['encoder'] = $matches[0]['encoder'];
            }
            return $status;
        }
        return false;
    }

    public function stop()
    {
        $pid = $this->status(false);
        $cmd = '(kill -9 ' . $pid['pid'] . '; rm ' . storage_path('app/public/stream') . '/*)';
        Dreambox::execute($cmd,'',true);
    }

    public function start()
    {
        $current_status = $this->status(false);
        if (!isset($current_status) || $current_status['source'] != $this->source_url)
        {
            $this->stop();
            if ($this->source_url == null)
            {
                return false;
            }

            if (config('app.debug'))
            {
                start_measure('start_stream','Starting transcoding');
            }

            // Playlist header
            $main_playlist[] = '#EXTM3U';

            if ('software' == $this->encoder_type)
            {
                $cmd = Streamer::executable . ' -i ' . $this->source_url;
            }
            elseif ('vaapi' == $this->encoder_type)
            {
                // HW VAAPI
                $cmd = Streamer::executable . ' -hwaccel vaapi -hwaccel_device /dev/dri/renderD128 -hwaccel_output_format vaapi -i ' . $this->source_url;
            }
            elseif ('nvidia' == $this->encoder_type)
            {
                // NVIDIA
                $cmd = Streamer::executable . ' -hwaccel cuvid -c:v h264_cuvid -deint 2 -i  ' . $this->source_url;
            }
            elseif ('omx' == $this->encoder_type)
            {
                // OpenMAX (Raspberry PI)
                $cmd = Streamer::executable . ' -c:v h264_mmal -i  ' . $this->source_url;
            }
            //
            //if ($this->language != null)
            //{
            //    $cmd .= ' -map 0:m:language:' . $this->language . '?';
            //}

            foreach (Streamer::bitrates as $bitrate_name => $bitrate)
            {

               // dd()

                if (!in_array($bitrate_name,$this->enabled_profiles))
                {
                    continue;
                }
                if (isset($bitrate['video_bitrate']))
                {
                    // Video
                    $cmd .= ' -map 0:v';

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
                    elseif ('nvidia' == $this->encoder_type)
                    {
                        // NVIDIA
                        $cmd .= ' -vf scale_npp=' . $bitrate['width'] . ':' . $bitrate['height'] . ':interp_algo=super';
                        //$cmd .= ' -vf "format=nv12|vaapi,hwupload,scale_vaapi=w=1280:h=720:format=yuv420p,hwdownload"';
                    }
                    elseif ('omx' == $this->encoder_type)
                    {
                        // OpenMAX (Raspberry PI)
                        $cmd .= ' -vf \'fps=' . $bitrate['framerate'] . ',scale=' . $bitrate['width'] . ':-2,format=yuv420p\' -sws_flags lanczos';
                    }

                    // Encoding
                    if ('software' == $this->encoder_type)
                    {
                        // -x264-params idrint=10,bframes=16,b-adapt=1,ref=3,qpmax=51,qpmin=10,me=hex,merange=16,subq=5,subme=7,qcomp=0.6,aud,keyint=10,nocabac
                        $cmd .= ' -c:v libx264 -x264-params "nal-hrd=cbr" -movflags +faststart -tune film -preset fast ' . $bitrate['h264'] . ' -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -bufsize ' . ($this->buffer_time * $bitrate['video_bitrate']) . 'k -r ' . $bitrate['framerate'] . ' -g ' . ($bitrate['framerate']*2);
                    }
                    elseif ('vaapi' == $this->encoder_type)
                    {
                        // HW VAAPI
                        $cmd .= ' -c:v h264_vaapi -qp 18 -quality 1 -bf 2 -tune film -preset fast -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -bufsize ' . ($this->buffer_time * $bitrate['video_bitrate']) . 'k -r ' . $bitrate['framerate']  . ' -g ' . ($bitrate['framerate']*2);
                    }

                    elseif ('nvidia' == $this->encoder_type)
                    {
                        // NVIDIA
                        $cmd .= ' -c:v h264_nvenc -qp 18 -quality 1 -bf 2 -preset fast -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -bufsize ' . ($this->buffer_time * $bitrate['video_bitrate']) . 'k -r ' . $bitrate['framerate']  . ' -g ' . ($bitrate['framerate']*2);
                    }

                    elseif ('omx' == $this->encoder_type)
                    {
                        // OpenMAX (Raspberry PI)
                        $cmd .= ' -c:v h264_omx -qp 18 -quality 1 -bf 2 -preset fast -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -bufsize ' . ($this->buffer_time * $bitrate['video_bitrate']) . 'k -r ' . $bitrate['framerate']  . ' -g ' . ($bitrate['framerate']*2);
                    }

                    $main_playlist[] = '#EXT-X-STREAM-INF:PROGRAM-ID=1,CODECS="mp4a.40.2, avc1.64001f",BANDWIDTH=' . round( ($bitrate['video_bitrate'] + $bitrate['audio_bitrate']) * 1024) . ',RESOLUTION=' . $bitrate['width'] . 'x' . $bitrate['height'];

                }
                else
                {
                    // Audio only track
                    $main_playlist[] = '#EXT-X-STREAM-INF:PROGRAM-ID=1,CODECS="mp4a.40.2",BANDWIDTH=' . round( $bitrate['audio_bitrate'] * 1024);
                }

                if ($this->language != null)
                {
                    $cmd .= ' -map 0:a:language:' . $this->language . '?';
                }
                else
                {
                    $cmd .= ' -map 0:a:0?';
                }

                // Audio
                $cmd .= ' -c:a aac -ac 51 -b:a ' . $bitrate['audio_bitrate'] . 'k -ar 48000';

                // HLS Output
                $cmd .= ' -f hls -strftime 1 -use_localtime 1 -hls_time ' . $this->chunktime . ' -hls_list_size ' . round($this->dvrlength / $this->chunktime) . ' -hls_segment_type mpegts -hls_flags +delete_segments -hls_segment_filename \'' . storage_path('app/public/stream') . '/' . Str::slug($this->source_name . '_' . $bitrate_name, '_')  . '_%s.ts\' ' . storage_path('app/public/stream/' . Str::slug($this->source_name . '_' . $bitrate_name, '_') . '.m3u8');

                // Main playlist info
                $main_playlist[] = Str::slug($this->source_name . '_' . $bitrate_name, '_') . '.m3u8';
            }

            // Delete old/previous files
            Storage::delete(Storage::allFiles('public/stream/'));
            // Execute on background....
            $process = Dreambox::execute($cmd,storage_path('app/ffmpeg_log'));

            // Create overall playlist
            for ($i = 0; $i < 30; $i++)
            {
                // Check if all bitrates playlist are generated...
                $all_done = array_count_values(array_map(function($bitrate) {
                    return (Storage::exists('public/stream/' . Str::slug($this->source_name . '_' . $bitrate, '_') . '.m3u8') ? 1 : 0);
                },$this->enabled_profiles));

                // If true, alle bitrates are available. Break 30 sec check loop and continue to make the master playlist
                if (isset($all_done[1]) && $all_done[1] == count($this->enabled_profiles)) break;
                sleep(1);
            }
            // Write main playlist
            Storage::put('public/stream/'.Str::slug($this->source_name, '_') . '.m3u8', implode("\n",$main_playlist));
            $current_status = $this->status(false);
            if (config('app.debug'))
            {
                stop_measure('start_stream');
            }
        }
        return asset('storage/stream/'. Str::slug($this->source_name, '_') . '.m3u8');
    }
}
