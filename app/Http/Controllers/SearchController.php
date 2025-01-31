<?php

namespace App\Http\Controllers;

use App\Models\Setlist;
use App\Models\Playlist;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        return view('search.index');
    }

//===============================================================

    public function searchResults(Request $request)
    {
        $query = $request->input('query', ''); // 検索クエリ（デフォルトは空文字）
        $setlists = collect(); // 空のコレクションを初期化
        $playlists = collect(); // 空のコレクションを初期化

        if ($query) {
            // 検索クエリが存在する場合にデータを取得
            $setlists = Setlist::where('live_name', 'LIKE', "%$query%")
                ->orWhere('concert_date', 'LIKE', "%$query%")
                ->orWhere('creator_comment', 'LIKE', "%$query%")
                ->orWhereHas('songs', function ($songQuery) use ($query) {
                    $songQuery->where('song_name', 'LIKE', "%$query%")
                              ->orWhereHas('artist', function ($artistQuery) use ($query) {
                                  $artistQuery->where('artist_name', 'LIKE', "%$query%");
                              });
                })
                ->get();


            $playlists = Playlist::where('playlist_name', 'LIKE', "%$query%")
                ->orWhere('creator_comment', 'LIKE', "%$query%")
                ->orWhereHas('songs', function ($songQuery) use ($query) {
                    $songQuery->where('song_name', 'LIKE', "%$query%")
                            ->orWhereHas('artist', function ($artistQuery) use ($query) {
                                $artistQuery->where('artist_name', 'LIKE', "%$query%");
                            });
                })
                ->get();
        }

        return view('search.results', compact('query', 'setlists', 'playlists'));
    }
}
