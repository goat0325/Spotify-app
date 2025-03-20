<?php

namespace App\Http\Controllers;

use App\Services\SpotifyService; // Spotify APIの認証処理を行うためのサービス
use Illuminate\Http\Request;
use App\Models\Playlist;
use App\Models\PlaylistSong;
use App\Models\Song;
use App\Models\Artist;
use App\Models\Bookmark;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;


class PlaylistController extends Controller
{
    protected $spotify;

    public function __construct(SpotifyService $spotify)
    {
        $this->spotify = $spotify;
    }

//===============================================================

    // Ajaxリクエストで曲をセットリストに追加するメソッド
    public function addSong(Request $request)
    {
        // 必要な情報をバリデーション
        $validated = $request->validate([
            'song_id' => 'required',  // 曲IDは必須
            'artist_spotify_id' => 'required',// アーティストIDも必須
            'song_name' => 'required', // 曲名も必須
            'artist_name' => 'required', // アーティスト名も必須
        ]);

        return response()->json(['message' => '曲がセットリストに追加されました']);
    }

//===============================================================

    //user_idを現在ログインしているユーザーのIDから取得し、フロントエンドに渡す
    public function getUserData()
    {
        return response()->json(['user_id' => auth()->id()]);
    }

//===============================================================

    public function InsertPlaylist(Request $request) //作成したセットリストをDBに保存するメソッド
    {
        Log::info('リクエストデータ', $request->all());

        // 現在のユーザー情報を取得
        $user = Auth::user();

        // ユーザー情報が正しく取得できているか確認
        if (!$user || !$user->spotify_user_id) {
            \Log::error('ユーザー情報が取得できません。Spotify IDが存在しません。');
            return response()->json(['error' => 'User information or Spotify ID is missing'], 400);
        }

        // セットリストに曲を追加するリクエストデータのバリデーション
        $request->validate([
            'user_id' => 'required|integer',
            'playlist_name' => 'required|string|max:255',
            'creator_comment' => 'nullable|string',

            'argInAddedSongs' => 'required|array',
            'argInAddedSongs.*.song_name' => 'required|string|max:100',
            'argInAddedSongs.*.artist_name' => 'required|string|max:50',
            'argInAddedSongs.*.song_spotify_id' => 'required|string|max:50',
            'argInAddedSongs.*.artist_spotify_id' => 'required|string|max:50'
        ]);

        // Playlistsテーブルにセットリストの共通情報を保存
        $playlist = Playlist::create([
            'user_id' => $request->user_id,
            'playlist_name' => $request->input('playlist_name'),
            'creator_comment' => $request->input('creator_comment')
        ]);

        Log::info('作成したプレイリスト:', $playlist->toArray());

        // 作成したセットリストIDが null ではないことを確認
        if (!$playlist || !$playlist->id) {
            throw new \Exception('プレイリストの作成に失敗しました');
        }

        // 各曲情報をPlaylist_songsテーブルに保存
        foreach ($request->argInAddedSongs as $playlistData) {

            //Log::info('Adding song  セットリストデータ', $playlistData); // 各曲データをログに記録

            // アーティストが存在しない場合、アーティストを作成
            $artist = Artist::firstOrCreate(
                ['spotify_id' => $playlistData['artist_spotify_id']],
                ['artist_name' => $playlistData['artist_name']]
            );

            // 曲が存在しない場合、曲を作成
            $song = Song::firstOrCreate(
                ['spotify_id' => $playlistData['song_spotify_id']],
                [
                    'song_name' => $playlistData['song_name'],
                    'artist_id' => $artist->id,  // ここでアーティストの ID を指定
                ]
            );

            PlaylistSong::create([
                'playlist_id' => $playlist->id,  // 作成したプレイリストのIDを参照
                'song_id' => $song->id,      // song_idを使用
                'artist_id' => $artist->id,  // artist_idを使用
            ]);
        }

        // 曲の URI を取得し、Spotify のプレイリストを生成
        $trackUris = $this->getTrackUrisFromPlaylist($playlist->id); // ここで URI を取得、$playlist->idで曲の情報を取得
        
        //Log::info('Spotifyプレイリスト生成開始', ['playlist_id' => $playlist->id]);

        // Spotifyプレイリストを作成
        $this->generateSpotifyPlaylist($playlist, $trackUris);
        //Log::info('Spotifyプレイリスト生成完了', ['playlist_id' => $playlist->id]);

        return response()->json([
            'message' => 'プレイリストが作成されました！',
            'redirect_url' => route('playlists.show', ['playlist' => $playlist->id]),
            Log::info('InsertPlaylist最後まで行ったよ'),
        ]);
    }

//===============================================================

    // セットリスト入力画面を表示
    public function create()
    {
        $playlist = new Playlist(); // 新しいセットリストオブジェクトを作成
        $songs = Song::all();  // 曲のリストを取得
        $artists = Artist::all();  // アーティストのリストを取得

        // ログを追加
        \Log::info('Playlist name before saving:', ['playlist_name' => $playlist->playlist_name]);


         // セットリスト、曲、アーティスト変数（オブジェクト）をビューに渡す
        return view('playlists.create', compact('playlist', 'songs', 'artists'));
    }

//===============================================================

    // セットリストを保存
    public function store(Request $request)
    {
        // バリデーション
        $validatedData = $request->validate([
            'user_id' => 'required|string|max:50',  // ユーザーIDのバリデーションを追加（必要に応じて)
            'playlist_name' => 'required|string|max:100', // プレイリスト名は必須
            'creator_comment' => 'nullable|string|max:1000',
            //'artistName' => 'required|string|max:50', // アーティスト名は必須

            'argInAddedSongs' => 'required|array', // 曲のデータが含まれる配列
            'argInAddedSongs.*.song_name' => 'required|string|max:100', // 各曲名も必須
            'argInAddedSongs.*.artist_name' => 'required|string|max:50', // 各アーティスト名も必須
            'argInAddedSongs.*.song_spotify_id' => 'nullable|string',
            'argInAddedSongs.*.artist_spotify_id' => 'nullable|string',
        ]);
    }    

//===============================================================

    // Playlist に基づき Spotify プレイリストを生成
    public function generateSpotifyPlaylist($playlist, $trackUris)
    {
        // 既にインジェクションされたSpotifyServiceインスタンスを使用
        $spotifyApi = $this->spotify; 

        // ユーザー情報を取得　（すでにauth()->user()でログインユーザーが取得されているので、引数の$userは不要）
        $user = auth()->user();
        $accessToken = $user->access_token;
        $spotifyUserId = $user->spotify_user_id;  // SpotifyのユーザーIDを取得

        // プレイリスト名をセットリストの情報に基づいて作成
        $playlistName = $playlist->playlist_name;

        // Spotifyプレイリストを作成
        $playlistData = $spotifyApi->createPlaylist(
            $spotifyUserId,       // SpotifyのユーザーID
            $playlistName,        // プレイリスト名
            'Created from playlist data', // プレイリストの説明
            false,                // 公開設定（デフォルトで非公開）
            $trackUris            // 曲のURI（必要に応じて追加）
        );        

        // $playlistDataが文字列（プレイリストID）である場合
        if ($playlistData !== null) {
            $playlist->spotify_playlist_id = $playlistData; // プレイリストIDを直接セット
            $playlist->spotify_playlist_link = 'https://open.spotify.com/playlist/' . $playlistData; // プレイリストリンクを作成
            $playlist->save();
        } else {
            \Log::error('Error: Playlist data is null.');
        }

        // 曲情報（Spotify URI）を取得
        $trackUris = $playlist->songs->map(function ($song) {
            return $song->spotify_id ? 'spotify:track:' . $song->spotify_id : null;
        })->filter()->values()->toArray();

        // trackUrisが空でないか確認
        if (empty($trackUris)) {
            \Log::error("プレイリストに曲ないよ", ['playlist_id' => $playlistId]);
            return redirect()->back()->withErrors('プレイリストに曲がありません。');
        }
        
        $this->spotify->addTracksToPlaylist($accessToken, $playlistData, $trackUris);

        // 作成した Spotify プレイリスト ID を Playlist テーブルに保存        
        $playlist->spotify_playlist_id = $playlistData; // プレイリストIDを保存
        $playlist->spotify_playlist_link = 'https://open.spotify.com/playlist/' . $playlistData; // プレイリストリンクも保存
        $playlist->save();

        return redirect()->route('playlists.show', ['playlist' => $playlist->id])->with('success', 'Spotifyプレイリストが作成されました。');
    }

//===============================================================

    // Playlist から Spotify URI リストを取得するメソッド
    private function getTrackUrisFromPlaylist($playlistId)
    {
        // セットリスト内のすべての曲を取得し、そのSpotifyのURIを取得
        $spotifyIds = PlaylistSong::where('playlist_id', $playlistId)
            ->join('songs', 'playlist_songs.song_id', '=', 'songs.id')
            ->pluck('songs.spotify_id');

        $trackUris = [];

        // 各曲のSpotify URIを配列に追加
        foreach ($spotifyIds as $spotifyId) {
            if ($spotifyId) {

                // SpotifyのトラックURIを作成して配列に追加
                $trackUris[] = 'spotify:track:' . $spotifyId;

                // 各曲のSpotify URIをログ出力
                \Log::info('Adding track URI', ['spotify_uri' => 'spotify:track:' . $spotifyId]);
            }
        }

        return $trackUris;
    }

//===============================================================

    // プレイリスト表示
    public function show($id)
    {
        // プレイリスト情報を取得
        $playlist = Playlist::with(['songs', 'songs.artist', 'comments.user'])->findOrFail($id);

        // Spotify プレイリストリンクの生成
        $spotifyPlaylistLink = 'https://open.spotify.com/playlist/' . $playlist->spotify_playlist_id;

        // 1ページあたり最大5件のコメントを取得
        $comments = $playlist->comments()->with('user')->latest()->paginate(5);

        // playlists.showビューにセットリストデータを渡す
        return view('playlists.show', [
            'playlist' => $playlist,
            'spotifyPlaylistLink' => $spotifyPlaylistLink,  // リンクを渡す
            'comments' => $comments,
        ]);
    }

//===============================================================

    public function storeComment(Request $request, $id)
    {
        $playlist = Playlist::findOrFail($id);

        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $playlist->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        return redirect()->back()->with('success', 'コメントを投稿しました。');
    }

//===============================================================

    public function toggleBookmark(Request $request, $id)
    {
        $user = auth()->user();
        $playlist = Playlist::findOrFail($id);

        // 既にブックマークされているかチェック
        $bookmark = $playlist->bookmarks()->where('user_id', $user->user_id)->first();

        if ($bookmark) {
	    $bookmark->delete();
            return response()->json(['bookmarked' => false]);
        } else {
            $playlist->bookmarks()->create(['user_id' => $user->user_id]); // ブックマークする
            return response()->json(['bookmarked' => true]);
        }
    }

//===============================================================

    //ブックマークのカスケード削除を設定
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($playlist) {
            // プレイリストが削除されるとき、そのブックマークも削除
            $playlist->bookmarks()->delete();
        });
    }

//===============================================================

    // playlists/createにてセットリストに追加した曲を、１曲ずつ削除できるメソッド
    public function removeSong($id)
    {
        // 対象の曲を取得
        $song = Song::findOrFail($id);

        // 曲を削除
        $song->delete();

        // レスポンスを返す（非同期通信のため）
        return response()->json(['message' => '曲を削除しました。'], 200);
    }

    //===============================================================

    public function edit($id)
    {
        $playlist = Playlist::with(['songs', 'songs.artist'])->findOrFail($id);

        return view('playlists.create', [
            'playlist' => $playlist,
            'songs' => $playlist->songs,
            
        ]);
    }

//===============================================================

    public function update(Request $request, $id)
    {
        $request->validate([
            'live_name' => 'required|string|max:255',
            'concert_date' => 'required|date',
            'creator_comment' => 'nullable|string',

            'argInAddedSongs' => 'required|array',
            'argInAddedSongs.*.song_name' => 'required|string|max:100',
            'argInAddedSongs.*.artist_name' => 'required|string|max:50',
            'argInAddedSongs.*.song_spotify_id' => 'required|string|max:50',
            'argInAddedSongs.*.artist_spotify_id' => 'required|string|max:50'
        ]);

        $playlist = Playlist::findOrFail($id);
        $playlist->update([
            'live_name' => $request->input('live_name'),
            'concert_date' => $request->input('concert_date'),
            'creator_comment' => $request->input('creator_comment'),
        ]);
    
        // 既存の曲データを削除して新しいデータを保存
        PlaylistSong::where('playlist_id', $playlist->id)->delete();

        foreach ($request->argInAddedSongs as $playlistData) {
            $artist = Artist::firstOrCreate(
                ['spotify_id' => $playlistData['artist_spotify_id']],
                ['artist_name' => $playlistData['artist_name']]
            );

            $song = Song::firstOrCreate(
                ['spotify_id' => $playlistData['song_spotify_id']],
                [
                    'song_name' => $playlistData['song_name'],
                    'artist_id' => $artist->id,
                ]
            );

            PlaylistSong::create([
                'playlist_id' => $playlist->id,
                'song_id' => $song->id,
                'artist_id' => $artist->id,
            ]);
        }
    
        return redirect()->route('playlists.show', $playlist->id)->with('success', 'セットリストを更新しました！');
    }

//===============================================================

    public function destroy($playlistId)
    {
        \Log::info('削除リクエストを受け取りました: ' . $playlistId);
        
        // 現在のユーザーを取得
        $user = auth()->user();
        
        // セットリストをIDで取得
        $playlist = $user->playlists()->findOrFail($playlistId);

        // セットリストを削除
        $playlist->delete();
        
        return redirect()->route('my.lists')->with('success', 'セットリストが削除されました。');
    }
    
}
