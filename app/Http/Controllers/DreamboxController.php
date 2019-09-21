<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
Use App\Dreambox;
Use App\Channel;
Use App\Streamer;
Use App\Recording;

class DreamboxController extends Controller
{
    //
    public function index()
    {
        $dreambox = Dreambox::first();
        if ($dreambox)
        {
            //$dreambox->bouquets()->delete();
            //$dreambox->channels()->delete();
            return view('index',['dreambox' => $dreambox->loadCount(['bouquets','channels','programs','recordings'])]);
        }
        else
        {
            return redirect()->route('new_dreambox');
        }
    }

    public function new_dreambox()
    {
        $dreambox = new Dreambox();
        return $this->setup($dreambox);
    }

    public function setup(Dreambox $dreambox)
    {
        return view('setup', ['dreambox' => $dreambox]);
    }

    public function show(Dreambox $dreambox)
    {
        $dreambox->load_bouquets(false);
        return view('dreambox', ['dreambox' => $dreambox->loadCount(['bouquets','channels','programs','recordings'])]);
    }

    public function load(Dreambox $dreambox)
    {
        $dreambox->load_data();
        return $dreambox->loadMissing('bouquets.channels');
    }

    public function epg(Dreambox $dreambox,Channel $channel)
    {
        $dreambox->load_epg($channel);
        return $channel->loadMissing('programs');
    }

    public function recordings(Dreambox $dreambox) {
        $dreambox->load_recordings();
        return $dreambox->recordings->loadMissing('channel');
    }

    public function show_epg(Dreambox $dreambox,Channel $channel)
    {
        $dreambox->load_epg($channel);
        return view('epg', ['channel' => $channel]);

    }

    public function stream(Dreambox $dreambox,Channel $channel)
    {
        $channel['stream'] = $dreambox->stream($channel);
        $channel['type'] = 'channel';
        return $channel;
    }

    public function stream_recording(Dreambox $dreambox,Recording $recording)
    {
        $recording->loadMissing('channel');
        $recording['stream'] = $dreambox->stream($recording);
        $recording['type'] = 'recording';
        return $recording;
    }

    public function status(Dreambox $dreambox)
    {
        $status = ['online' => $dreambox->is_online(),
                   'running' => false];
        $streamer = new Streamer($dreambox->hostname,$dreambox->port, [$dreambox->username, $dreambox->password]);
        $channel = $streamer->status();
        if ($channel)
        {
            // Streamer is running....
            $channel['type'] = (isset($channel->filesize) ? 'recording' : 'channel');
            $channel['online'] = $status['online'];
            $channel['running'] = true;
            return $channel;
        }
        return $status;
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|unique:dreamboxes|max:255',
            'hostname' => 'required|max:255',
            'port' => 'required|integer',
            'enigma' => 'required|integer',
            'dual_tuner' => 'required|integer',
            'buffer_time' => 'required|integer:min:0',
            'epg_limit' => 'integer|min:0|max:72',
            'dvr_length' => 'integer|min:0|max:900',
        ]);

        $dreambox = Dreambox::create($request->all());
        return ['message' => 'Dreambox is created. Will reload now...','url' => route('show_dreambox',['dreambox' => $dreambox->id])];
    }

    public function update(Request $request, Dreambox $dreambox)
    {
        $validatedData = $request->validate([
            'name' => 'required|max:255',
            'hostname' => 'required|max:255',
            'port' => 'required|integer',
            'enigma' => 'required|integer',
            'dual_tuner' => 'required|integer',
            'buffer_time' => 'required|integer:min:0',
            'epg_limit' => 'integer|min:0|max:72',
            'dvr_length' => 'integer|min:0|max:900',
        ]);
        $dreambox->update($request->all());
        return ['message' => 'Dreambox is updated. Will reload now...','url' => route('show_dreambox',['dreambox' => $dreambox->id])];
    }

    public function delete(Dreambox $dreambox)
    {
        $dreambox->delete();

        return response()->json(null, 204);
    }
}
