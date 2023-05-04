<?php

namespace App;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Streamer
{
    const executable = '/usr/bin/ffmpeg';
    const ffprobe = '/usr/bin/ffprobe';

    const bitrates = [
        'FullHD' => [
            'name' => 'Full HD',
            'video_bitrate' => 4000,
            'width' => 1920,
            'height' => 1080,
            'framerate' => 25,
            'audio_bitrate' => 160,
            'h264' => '-profile:v high -level 4.1 -pixel_format nv12'
        ],

        'HDReady' => [
            'name' => 'HD Ready',
            'video_bitrate' => 2000,
            'width' => 1280,
            'height' => 720,
            'framerate' => 25,
            'audio_bitrate' => 128,
            'h264' => '-profile:v high -level 4.0 -pixel_format nv12'
        ],

        'SD' => [
            'name' => 'SD',
            'video_bitrate' => 1000,
            'width' => 854,
            'height' => 480,
            'framerate' => 25,
            'audio_bitrate' => 112,
            'h264' => '-profile:v main -level 3.1 -pixel_format nv12'
        ],

        'Mobile' => [
            'name' => 'Mobile',
            'video_bitrate' => 512,
            'width' => 640,
            'height' => 360,
            'framerate' => 20,
            'audio_bitrate' => 96,
            'h264' => '-profile:v baseline -level 3.1 -pixel_format nv12'
        ],

        'Audio' => [
            'name' => 'Audio Only',
            'audio_bitrate' => 96
        ]
    ];

    private $auto_killer_temp_file = '.autoKiller';

    private $buffer_time = 10;
    private $chunktime = 2;
    private $dvrlength = 300;
    private $encoder_type = 'software';

    private $source_url = null;
    private $source_name = null;
    private $language = null;

    private $executable = Streamer::executable;

    function __construct($source_url, $source_name)
    {
        $this->hardware_detection();
        $this->set_source($source_url, $source_name);
    }

    static function profiles()
    {
        return Streamer::bitrates;
    }

    public function set_source($source_url, $source_name)
    {
        $this->source_url = $source_url;
        $this->source_name = $source_name;
    }

    public function language($language)
    {
        if (strpos('ac3', $language) === -1) {
            $language .= ',ac3';
        }
        $this->language = trim($language);
    }

    public function set_profiles($profiles)
    {
        $this->enabled_profiles = explode(',', $profiles);
    }

    public function set_dvr($length)
    {
        $this->dvrlength = $length;
    }

    private function hardware_detection()
    {
        if (file_exists(base_path() . '/nvidia/ffmpeg')) {
            $this->encoder_type = 'nvidia';
            $this->executable = base_path() . '/nvidia/ffmpeg';
        }
        //        elseif (file_exists('/dev/dri/renderD128'))
//        {
//            $this->encoder_type = 'vaapi';
//        }
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
            Dreambox::execute('kill -9 ' . $kill_timer_pid, '', true);
        }
        // Start new kill timer...
        $cmd = '(sleep 120;kill -9 ' . $streamer_pid . '; rm ' . storage_path('app/public/stream') . '/* )';
        $kill_timer_pid = Dreambox::execute($cmd);
        // Store new pid to temp file
        Storage::put($this->auto_killer_temp_file, $kill_timer_pid);
    }

    // This will give back the most optimal video and audio codecs....
    private function probe_stream()
    {
        if (config('app.debug')) {
            start_measure('probe_stream', 'Probing stream: ' . $this->source_url);
        }

        Log::debug(Streamer::ffprobe . ' -hide_banner -v quiet -print_format json -show_format -show_streams ' . $this->source_url);

        $probe = shell_exec(Streamer::ffprobe . ' -hide_banner -v quiet -print_format json -show_format -show_streams ' . $this->source_url);
        Storage::put('probe.json', $probe);
        $probe = json_decode($probe);

        $data = ['video' => null, 'audio' => null, 'subtitle' => null];

        $streams = [];
        foreach ($probe->streams as $stream) {
            if (! isset($stream->codec_type) || ! array_key_exists($stream->codec_type, $data)) {
                continue;
            }

            if (! isset($streams[$stream->codec_type])) {
                $streams[$stream->codec_type] = [];
            }

            $streams[$stream->codec_type][] = $stream;
        }

        foreach ($streams as $key => $stream_data) {
            if (is_array($stream_data) && count($stream_data) > 0) {
                // Find the best stream data
                if (count($stream_data) === 1) {
                    $data[$key] = $stream_data[0];
                } else {
                    $languages = array_map('trim', explode(',', $this->language));

                    $best_score = -1;
                    foreach ($stream_data as $stream) {
                        $score = count(array_intersect($languages, [$stream->codec_name ?? null, $stream->tags->language ?? null]));
                        if ($score > $best_score) {
                            // New best match
                            $data[$key] = $stream;
                            $best_score = $score;
                        }
                    }
                }
            }
        }

        if (config('app.debug')) {
            stop_measure('probe_stream');
        }

        return $data;

    }

    public function status($autokiller = true)
    {
        $status = ['source' => null, 'service' => null, 'encoder' => null];
        $process_data = shell_exec("ps ax | grep ffmpeg | grep -v grep");

        if ($process_data) {
            $process_data = trim($process_data);
            $re = '/^(?P<pid>\d+).*ffmpeg(-nvidia)? (?P<encoder>vaapi|cuvid)?.*-i (?P<source>http:\/\/[^ ]+(:\d+)?\/(file\?file=)?(?P<service>[^ ]+))/m';
            preg_match_all($re, $process_data, $matches, PREG_SET_ORDER);
            if ($matches && stripos($this->source_url, $matches[0]['source']) == 0) {
                $status['source'] = $matches[0]['source'];
                $status['service'] = $matches[0]['service'];
                $status['pid'] = $matches[0]['pid'];
                // Restart the autokiller....
                if ($autokiller) {
                    $this->auto_killer($status['pid']);
                }
                if (! empty($status['encoder'])) {
                    $status['encoder'] = $matches[0]['encoder'];
                }
                return $status;
            }
        }

        return false;
    }

    public function stop()
    {
        $pid = $this->status(false);
        $cmd = '(kill -9 ' . $pid['pid'] . '; rm ' . storage_path('app/public/stream') . '/*)';
        Dreambox::execute($cmd, '', true);
    }

    public function start()
    {
        $current_status = $this->status(false);
        if (! isset($current_status) || $current_status == false || $current_status['source'] != $this->source_url) {
            if ($current_status) {
                $this->stop();
            }

            if ($this->source_url == null) {
                return false;
            }

            $stream_map = ['video' => 0, 'audio' => 0, 'subtitle' => 0];
            if ($this->language != null) {
                $stream_map = $this->probe_stream();
            }

            if (config('app.debug')) {
                start_measure('start_stream', 'Starting transcoding');
            }

            // Playlist header + main audio
            $main_playlist = [
                '#EXTM3U',
                '#EXT-X-VERSION:3',
                '#EXT-X-MEDIA:TYPE=AUDIO,GROUP-ID="aac",LANGUAGE="en",NAME="audio",URI="' . Str::slug($this->source_name . '_audio', '_') . '.m3u8"'
            ];

            if ('software' == $this->encoder_type) {
                $cmd = $this->executable . ' -hide_banner -i ' . $this->source_url;
            } elseif ('vaapi' == $this->encoder_type) {
                // HW VAAPI
                $cmd = $this->executable . ' -hide_banner -hwaccel vaapi -hwaccel_device /dev/dri/renderD128 -hwaccel_output_format vaapi -i ' . $this->source_url;
                //$cmd = Streamer::executable . ' -hwaccel vaapi -hwaccel_device /dev/dri/renderD128 -i ' . $this->source_url;
            } elseif ('nvidia' == $this->encoder_type) {
                // NVIDIA
                $cmd = $this->executable . ' -hide_banner -re -hwaccel cuvid -hwaccel_output_format cuda -i ' . $this->source_url;
            } elseif ('omx' == $this->encoder_type) {
                // OpenMAX (Raspberry PI)
                $cmd = $this->executable . ' -c:v h264_mmal -i  ' . $this->source_url;
            }
            //
            //if ($this->language != null)
            //{
            //    $cmd .= ' -map 0:m:language:' . $this->language . '?';
            //}

            $bitrate_counter = 0;
            foreach (Streamer::bitrates as $bitrate_name => $bitrate) {
                if (! in_array($bitrate_name, $this->enabled_profiles)) {
                    continue;
                }
                if (isset($bitrate['video_bitrate'])) {
                    // Video only!
                    $cmd .= ' -map 0:' . $stream_map['video']->index;

                    // Scale resolution
                    if ('software' == $this->encoder_type) {
                        // Add yadif for deinterlacing
                        $cmd .= ' -vf \'fps=' . $bitrate['framerate'] . ',scale=' . $bitrate['width'] . ':-2,format=yuv420p\' -sws_flags lanczos';
                    } elseif ('vaapi' == $this->encoder_type) {
                        // HW VAAPI
                        $cmd .= ' -vf \'deinterlace_vaapi=rate=field:auto=1,fps=' . $bitrate['framerate'] . ',scale_vaapi=w=' . $bitrate['width'] . ':h=-2:format=nv12\'';
                        //$cmd .= ' -vf "format=nv12|vaapi,hwupload,scale_vaapi=w=1280:h=720:format=yuv420p,hwdownload"';
                    } elseif ('nvidia' == $this->encoder_type) {
                        // NVIDIA
                        $cmd .= ' -vf yadif_cuda,fps=' . $bitrate['framerate'] . ',scale_cuda=' . $bitrate['width'] . ':' . $bitrate['height'] . ':format=yuv420p';
                    } elseif ('omx' == $this->encoder_type) {
                        // OpenMAX (Raspberry PI)
                        $cmd .= ' -vf \'fps=' . $bitrate['framerate'] . ',scale=' . $bitrate['width'] . ':-2,format=yuv420p\'';
                    }

                    // Encoding
                    if ('software' == $this->encoder_type) {
                        // -x264-params idrint=10,bframes=16,b-adapt=1,ref=3,qpmax=51,qpmin=10,me=hex,merange=16,subq=5,subme=7,qcomp=0.6,aud,keyint=10,nocabac
                        $cmd .= ' -c:v libx264 -x264-params nal-hrd=cbr,min-keyint=' . (2 * $bitrate['framerate']) . ',keyint=' . (2 * $bitrate['framerate']) . ',no-scenecut -movflags +faststart -tune film -preset medium ' . $bitrate['h264'] . ' -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -bufsize ' . ($this->buffer_time * $bitrate['video_bitrate']) . 'k -r ' . $bitrate['framerate'] . ' -g ' . ($bitrate['framerate'] * 2);
                    } elseif ('vaapi' == $this->encoder_type) {
                        // HW VAAPI
                        $cmd .= ' -c:v h264_vaapi -qp 18 -quality 1 -bf 2 -tune film -preset medium -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -bufsize ' . ($this->buffer_time * $bitrate['video_bitrate']) . 'k -r ' . $bitrate['framerate'] . ' -g ' . ($bitrate['framerate'] * 2);
                    } elseif ('nvidia' == $this->encoder_type) {
                        // NVIDIA
                        $cmd .= ' -c:v h264_nvenc -qp 18 -bf 2 -preset medium ' . $bitrate['h264'] . ' -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -bufsize ' . ($this->buffer_time * $bitrate['video_bitrate']) . 'k -g ' . ($bitrate['framerate'] * 2) . ' -rc cbr -rc-lookahead 32'; // . ' -r ' . $bitrate['framerate'];
                    } elseif ('omx' == $this->encoder_type) {
                        // OpenMAX (Raspberry PI)
                        $cmd .= ' -c:v h264_omx -bf 2 -b:v ' . $bitrate['video_bitrate'] . 'k -minrate ' . $bitrate['video_bitrate'] . 'k -maxrate ' . $bitrate['video_bitrate'] . 'k -bufsize ' . ($this->buffer_time * $bitrate['video_bitrate']) . 'k -r ' . $bitrate['framerate'] . ' -g ' . ($bitrate['framerate'] * 2);
                    }

                    // if ( $stream_map['subtitle']) {
                    //   $cmd .= ' -map 0:' . $stream_map['subtitle']->index;
                    // }

                    $main_playlist[] = '#EXT-X-STREAM-INF:PROGRAM-ID=1,CODECS="mp4a.40.2, avc1.64001f",BANDWIDTH=' . round(($bitrate['video_bitrate'] + $bitrate['audio_bitrate']) * 1024) . ',RESOLUTION=' . $bitrate['width'] . 'x' . $bitrate['height'] . ',AUDIO="aac"';
                }
                // HLS Output
                $cmd .= ' -f hls -strftime 1 -hls_time ' . $this->chunktime . ' -hls_list_size ' . round($this->dvrlength / $this->chunktime) . ' -hls_segment_type mpegts -hls_flags +delete_segments -hls_segment_filename \'' . storage_path('app/public/stream') . '/' . Str::slug($this->source_name . '_' . $bitrate_name, '_') . '_%s.ts\' ' . storage_path('app/public/stream/' . Str::slug($this->source_name . '_' . $bitrate_name, '_') . '.m3u8');

                // Bitrate playlist name
                $main_playlist[] = Str::slug($this->source_name . '_' . $bitrate_name, '_') . '.m3u8';

                $bitrate_counter++;
            }

            // Add single bitrate main audio
            $cmd .= '  -map 0:' . $stream_map['audio']->index . ' -c:a aac -ac 2 -b:a 128k -ar 48000';

            // HLS Output
            $cmd .= ' -f hls -strftime 1 -hls_time ' . $this->chunktime . ' -hls_list_size ' . round($this->dvrlength / $this->chunktime) . ' -hls_segment_type mpegts -hls_flags +delete_segments -hls_segment_filename \'' . storage_path('app/public/stream') . '/' . Str::slug($this->source_name . '_audio', '_') . '_%s.ts\' ' . storage_path('app/public/stream/' . Str::slug($this->source_name . '_audio', '_') . '.m3u8');

            // Delete old/previous files
            Storage::makeDirectory('public/stream/');
            Storage::delete(Storage::allFiles('public/stream/'));
            // Execute on background....
            Storage::put('cmd.txt', $cmd);

            // $process = Dreambox::execute('echo "$cmd"',storage_path('app/cmd.txt'));
            $process = Dreambox::execute($cmd, storage_path('app/ffmpeg_log'));

            // Create overall playlist
            for ($i = 0; $i < 30; $i++) {
                // Check if all bitrates playlist are generated...
                $all_done = array_count_values(array_map(function ($bitrate) {
                    return (Storage::exists('public/stream/' . Str::slug($this->source_name . '_' . $bitrate, '_') . '.m3u8') ? 1 : 0);
                }, $this->enabled_profiles));

                // If true, alle bitrates are available. Break 30 sec check loop and continue to make the master playlist
                if (isset($all_done[1]) && $all_done[1] == count($this->enabled_profiles))
                    break;
                sleep(1);
            }
            // Write main playlist
            if (count($this->enabled_profiles) == 1) {
                symlink(
                    Str::slug($this->source_name . '_' . $this->enabled_profiles[0], '_') . '.m3u8',
                    storage_path('app/public/stream/' . Str::slug($this->source_name, '_') . '.m3u8')
                );
            } else {
                Storage::put('public/stream/' . Str::slug($this->source_name, '_') . '.m3u8', implode("\n", $main_playlist));
            }
            $current_status = $this->status(true);
            if (config('app.debug')) {
                stop_measure('start_stream');
            }
        }
        return asset('storage/stream/' . Str::slug($this->source_name, '_') . '.m3u8');
    }
}