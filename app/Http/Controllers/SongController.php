<?php

namespace App\Http\Controllers;

use App\Services\SpotifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Song;
use App\Models\Artist;


class SongController extends Controller
{
    protected $spotifyService;

    public function __construct(SpotifyService $spotifyService)
    {
        $this->spotifyService = $spotifyService;
    }

//=================================================================

    public function search(Request $request)
    {
        // 必要に応じて入力を検証
        $request->validate(['query' => 'required|string|min:1']);

        $query = $request->get('query');
        $perPage = $request->get('per_page', 10); // 1ページに表示する件数（デフォルトは10件）
        $page = $request->get('page', 1); // 現在のページ（デフォルトは1）


        // Spotifyからの曲データを取得
        $spotifyResults = $this->spotifyService->searchTracks($query, [ // Spotify APIを呼び出す
            'Accept-Language' => 'ja' // 日本語設定を付与
        ]);

        // Spotify APIからのレスポンスを確認
        $newSongData = [];
        if (isset($spotifyResults->tracks->items)) {
            foreach ($spotifyResults->tracks->items as $spotifyTrack) {
                $newSongData[] = [
                    'song_name' => $spotifyTrack->name,
                    'artist_name' => $spotifyTrack->artists[0]->name ?? 'Unknown Artist',
                    'album_image_url' => $spotifyTrack->album->images[0]->url ?? null,
                    'song_spotify_id' => $spotifyTrack->id, // Spotify IDを追加
                    'artist_spotify_id' => $spotifyTrack->artists[0]->id
                ];
            }
        } else {
            Log::info('Spotify APIからのレスポンスが無効です。');
        }

        // データベースの曲とSpotifyからの曲を統合
        $response = [
            'spotify_results' => $newSongData, // Spotifyからの結果
        ];

        return response()->json($response);
    }

//=================================================================

    public function checkExistence(Request $request)
    {
        $request->validate([
            'song_name' => 'required|string',
            'song_spotify_id' => 'required|string',
            'artist_name' => 'required|string',
            'artist_spotify_id' => 'required|string',
        ]);

        $exists = Song::where('spotify_id', $request->spotify_id)->exists();

        Log::info('Received POST data:', request()->all());

        return response()->json(['exists' => $exists]);
    }

}

