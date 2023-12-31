<?php

namespace App\Repositories\Implementations;

use App\Models\Track;
use App\Models\TrackPlay;
use App\Repositories\Interfaces\TrackRepositoryInterface;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EloquentTrackRepository implements TrackRepositoryInterface
{

    function getAll()
    {
        return response()->json(
            Track::with('owner')
            ->with('features')
            ->with('album')
            ->withCount('likes')->paginate(10));
    }

    function show(string $id)
    {
        $path = storage_path('app/audio/9A6324BF-012E-4AAD-A978-2A544BBCD420.mp3');

        if(file_exists($path)){
            $size = filesize($path);
            $track = Track::with('owner')
                ->with('features')
                ->with('album.tracks')
                ->with('genre')
                ->withCount('likes')->findOrFail($id);

            $headers = [
                'Content-Type' => 'audio/mpeg',
                'Content-Length' => $size / 2,
                'Range' => "",
                'Accept-Ranges' => 'bytes',
            ];

            return response()->stream(function () use ($path,$track, $size) {
                $stream = fopen($path, 'r');
                fseek($stream, 0);
                $length =  $size / 2;
                echo fread($stream, $length);
                fclose($stream);
            }, 206, $headers);
        } else {
            abort(404);
        }
        try {
            $track = Track::with('owner')
                ->with('features')
                ->with('album.tracks')
                ->with('genre')
                ->withCount('likes')->findOrFail($id);

            return response()->json(['track' => $track]);
        }
        catch (ModelNotFoundException $exception){
            $user = "Anonymous";
            if(Auth::hasUser()){
                $user = Auth::user()->email;
            }
            Bugsnag::notifyException(new ModelNotFoundException("User: $user, Tried searching for a track that does not exists."));
            return response()->json(['message' => 'Track not found.']);
        }
    }

    function store(FormRequest $request)
    {
        // TODO: Implement store() method.
    }

    function update(FormRequest $request, string $id)
    {
        // TODO: Implement update() method.
    }

    function delete(string $id)
    {
        // TODO: Implement delete() method.
    }

    function getTrack(string $id, Request $request)
    {
        $play = new TrackPlay();
        $actor = \auth('sanctum')->user()->getAuthIdentifier();
        if($actor) {
            $play->actor_id = $actor;
        }

        $play->track_id = $id;

        $play->save();

        return response()->json('https://commondatastorage.googleapis.com/codeskulptor-demos/DDR_assets/Kangaroo_MusiQue_-_The_Neverwritten_Role_Playing_Game.mp3');

        $section = $request->get('section');
        if($section) {
            $path = storage_path('app/audio/'.$id.'.mp3');

            if(file_exists($path)){
                $size = filesize($path);
                $track = Track::with('owner')
                    ->with('features')
                    ->with('album.tracks')
                    ->with('genre')
                    ->withCount('likes')->findOrFail($id);

                $headers = [
                    'Content-Type' => 'audio/mpeg',
                    'Content-Length' => 500000,
                    'Range' => "",
                    'Accept-Ranges' => 'bytes',
                ];

              /*  return response()->stream(function () use ($section, $path, $size) {
                    $stream = fopen($path, 'r');
                    fseek($stream, $section);
                    $length =  500000;
                    echo fread($stream, $length);
                    fclose($stream);
                }, 206, $headers);*/
            } else {
                abort(404);
            }
        }

    }

    function popular()
    {
        $now = Carbon::now();
        $sevenDays = $now->copy()->subDays(7);

        $popularLastSevenDays = TrackPlay::select('track_id')
            ->whereBetween('created_at', [$sevenDays, $now])
            ->groupBy('track_id')
            ->havingRaw('COUNT(track_id) > 5') // pustana vise od n puta ukupno
            ->havingRaw('COUNT(DISTINCT actor_id) > 4') // vise od n korisnika
            ->orderByDesc(\DB::raw('COUNT(track_id)'))
            ->take(20);

        $tracks = Track::with(['owner', 'features', 'album'])
            ->whereIn('id', $popularLastSevenDays)
            ->get();

        return response()->json($tracks);
    }
}
