<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
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
            App::setLocale($dreambox->interface_language); // Stupid Laraval is not capable if handling language sessions... so every request tell again what you want... rubbish!
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
        App::setLocale($dreambox->interface_language); // Stupid Laraval is not capable if handling language sessions... so every request tell again what you want... rubbish!
        return view('setup', ['dreambox' => $dreambox, 'profiles' => Streamer::profiles()]);
    }

    public function show(Dreambox $dreambox)
    {
        $dreambox->is_online();
        $dreambox->load_bouquets(false);
        App::setLocale($dreambox->interface_language); // Stupid Laraval is not capable if handling language sessions... so every request tell again what you want... rubbish!
        return view('dreambox', ['dreambox' => $dreambox->loadCount(['bouquets','channels','programs','recordings'])]);
    }

    public function load(Dreambox $dreambox)
    {
        $dreambox->is_online();
        $dreambox->load_bouquets();
        return $dreambox->loadMissing('bouquets.channels');
    }

    public function epg(Dreambox $dreambox,Channel $channel)
    {
        $dreambox->is_online();
        $dreambox->load_epg($channel);
        return $channel->loadMissing('programs');
    }

    public function recordings(Dreambox $dreambox)
    {
        $dreambox->is_online();
        $dreambox->load_recordings();
        return $dreambox->recordings->loadMissing('channel');
    }

    public function show_epg(Dreambox $dreambox,Channel $channel)
    {
        $dreambox->is_online();
        $dreambox->load_epg($channel);
        App::setLocale($dreambox->interface_language); // Stupid Laraval is not capable if handling language sessions... so every request tell again what you want... rubbish!
        return view('epg', ['channel' => $channel]);
    }

    public function stop(Dreambox $dreambox)
    {
        $dreambox->stop();
    }

    public function stream(Dreambox $dreambox,Channel $channel)
    {
        $dreambox->is_online();
        $channel['stream'] = $dreambox->stream($channel);
        $channel['type'] = 'channel';
        return $channel;
    }

    public function stream_recording(Dreambox $dreambox,Recording $recording)
    {
        $dreambox->is_online();
        $recording->loadMissing('channel');
        $recording['stream'] = $dreambox->stream($recording);
        $recording['type'] = 'recording';
        return $recording;
    }

    public function status(Dreambox $dreambox)
    {
        $dreambox->is_online();
        return $dreambox->status();
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|unique:dreamboxes|max:255',
            'hostname' => 'required|max:255',
            'port' => 'required|integer|min:0',
            'multiple_tuners' => 'required|boolean',
            'buffer_time' => 'required|integer|min:0',
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
            'port' => 'required|integer|min:0',
            'multiple_tuners' => 'required|boolean',
            'buffer_time' => 'required|integer|min:0',
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
