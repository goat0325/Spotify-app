<?php

namespace App\Http\Controllers;


use App\Services\SpotifyService; // Spotify APIの認証処理を行うためのサービス
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Setlist;
use App\Models\SetlistSong;
use App\Models\Playlist;
use App\Models\PlaylistSong;
use App\Models\Song;
use App\Models\Artist;
use App\Models\Bookmark;



class SetlistController extends Controller
{
    protected $spotify;

    public function __construct(SpotifyService $spotify)
    {
        $this->spotify = $spotify;
        
    }

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

    public function InsertSetlist(Request $request) //作成したセットリストをDBに保存するメソッド
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
            'live_name' => 'required|string|max:255',
            'concert_date' => 'required|date',
            'creator_comment' => 'nullable|string',

            'argInAddedSongs' => 'required|array',
            'argInAddedSongs.*.song_name' => 'required|string|max:100',
            'argInAddedSongs.*.artist_name' => 'required|string|max:50',
            'argInAddedSongs.*.song_spotify_id' => 'required|string|max:50',
            'argInAddedSongs.*.artist_spotify_id' => 'required|string|max:50'
        ]);

        // Setlistsテーブルにセットリストの共通情報を保存
        $setlist = Setlist::create([
            'user_id' => $request->user_id,
            'live_name' => $request->input('live_name'),
            'concert_date' => $request->input('concert_date'),
            'creator_comment' => $request->input('creator_comment')
        ]);

        Log::info('作成したセットリスト:', $setlist->toArray());

        // 作成したセットリストIDが null ではないことを確認
        if (!$setlist || !$setlist->id) {
            throw new \Exception('セットリストの作成に失敗しました');
        }

        // 各曲情報をSetlist_songsテーブルに保存
        foreach ($request->argInAddedSongs as $setlistData) {

            Log::info('Adding song  セットリストデータ', $setlistData); // 各曲データをログに記録

            // アーティストが存在しない場合、アーティストを作成
            $artist = Artist::firstOrCreate(
                ['spotify_id' => $setlistData['artist_spotify_id']],
                ['artist_name' => $setlistData['artist_name']]
            );

            // 曲が存在しない場合、曲を作成
            $song = Song::firstOrCreate(
                ['spotify_id' => $setlistData['song_spotify_id']],
                [
                    'song_name' => $setlistData['song_name'],
                    'artist_id' => $artist->id,  // ここでアーティストの ID を指定
                ]
            );

            SetlistSong::create([
                'setlist_id' => $setlist->id,  // 作成したセットリストのIDを参照
                'song_id' => $song->id,      // song_idを使用
                'artist_id' => $artist->id,  // artist_idを使用
            ]);
        }

        // 曲の URI を取得し、Spotify のプレイリストを生成
        $trackUris = $this->getTrackUrisFromSetlist($setlist->id); // ここで URI を取得、$setlist->idで曲の情報を取得
        
        Log::info('Spotifyプレイリスト生成開始', ['setlist_id' => $setlist->id]);
        // Spotifyプレイリストを作成
        $this->generateSpotifyPlaylist($setlist, $trackUris);  //変更前の引数($setlist->id);
        Log::info('Spotifyプレイリスト生成完了', ['setlist_id' => $setlist->id]);

        return response()->json([
            'message' => 'セットリストが作成されました！',
            'redirect_url' => route('setlists.show', ['setlist' => $setlist->id]),
            Log::info('InsertSetlist最後まで行ったよ'),
        ]);
    }    

//===============================================================

    // セットリスト入力画面を表示
    public function create()
    {
        $setlist = new Setlist(); // 新しいセットリストオブジェクトを作成
        $songs = Song::all();  // 曲のリストを取得
        $artists = Artist::all();  // アーティストのリストを取得

         // セットリスト、曲、アーティスト変数（オブジェクト）をビューに渡す
        return view('setlists.create', compact('setlist', 'songs', 'artists'));    }

//===============================================================

    // セットリストを保存
    public function store(Request $request)
    {
        // バリデーション
        $validatedData = $request->validate([
            'user_id' => 'required|string|max:50',  // ユーザーIDのバリデーションを追加（必要に応じて)
            'live_name' => 'required|string|max:100', // ライブ名は必須
            'concert_date' => 'required|date', // 日付は必須
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

    // Setlist に基づき Spotify プレイリストを生成
    public function generateSpotifyPlaylist($setlist, $trackUris)  //変更前の引数($setlistId)
    {
        // 既にインジェクションされたSpotifyServiceインスタンスを使用
        $spotifyApi = $this->spotify; 

        // ユーザー情報を取得　（すでにauth()->user()でログインユーザーが取得されているので、引数の$userは不要）
        $user = auth()->user();
        $accessToken = $user->access_token;
        $spotifyUserId = $user->spotify_user_id;  // SpotifyのユーザーIDを取得

        // プレイリスト名をセットリストの情報に基づいて作成
        $playlistName = $setlist->live_name . ' / ' . $setlist->concert_date;


        \Log::info('プレイリストの名前', ['playlistName' => $playlistName]);


        // Spotifyプレイリストを作成
        $playlistData = $spotifyApi->createSetlist(
            $spotifyUserId,       // SpotifyのユーザーID
            $playlistName,        // プレイリスト名
            'Created from setlist data', // プレイリストの説明
            false,                // 公開設定（デフォルトで非公開）
            $trackUris            // 曲のURI（必要に応じて追加）
        );        

        \Log::info('Playlist data:', ['playlistData' => $playlistData]);

        // $playlistDataが文字列（プレイリストID）である場合
        if ($playlistData !== null) {
            $setlist->spotify_playlist_id = $playlistData; // プレイリストIDを直接セット
            $setlist->spotify_playlist_link = 'https://open.spotify.com/playlist/' . $playlistData; // プレイリストリンクを作成
            $setlist->save();
        } else {
            \Log::error('Error: Playlist data is null.');
        }

        // 曲情報（Spotify URI）を取得
        $trackUris = $setlist->songs->map(function ($song) {
            return $song->spotify_id ? 'spotify:track:' . $song->spotify_id : null;
        })->filter()->values()->toArray();

        \Log::info('Final track URIs', ['track_uris' => $trackUris]);

        // trackUrisが空でないか確認
        if (empty($trackUris)) {
            \Log::error("セットリストに曲ないよ", ['setlist_id' => $setlistId]);
            return redirect()->back()->withErrors('セットリストに曲がありません。');
        }

        \Log::info('Spotifyプレイリスト生成開始', ['user_id' => $user->user_id, 'setlist_id' => $setlist->id]);
        
        $this->spotify->addTracksToSetlist($accessToken, $playlistData /*Id*/, $trackUris);
        \Log::info('プレイリストにトラックを追加しました', ['playlist_id' => $playlistData/*Id*/, 'track_count' => count($trackUris)]);

        // 作成した Spotify プレイリスト ID を Setlist テーブルに保存        
        $setlist->spotify_playlist_id = $playlistData; // プレイリストIDを保存
        $setlist->spotify_playlist_link = 'https://open.spotify.com/playlist/' . $playlistData; // プレイリストリンクも保存
        $setlist->save();
        \Log::info('SetlistテーブルにSpotifyプレイリストIDを保存しました', ['setlist_id' => $setlist->id, 'spotify_playlist_id' => $playlistData]);

        return redirect()->route('setlists.show', ['setlist' => $setlist->id])->with('success', 'Spotifyプレイリストが作成されました。');
    }

//===============================================================

    // Setlist から Spotify URI リストを取得するメソッド
    private function getTrackUrisFromSetlist($setlistId)
    {
        // セットリスト内のすべての曲を取得し、そのSpotifyのURIを取得
        $spotifyIds = SetlistSong::where('setlist_id', $setlistId)
            ->join('songs', 'setlist_songs.song_id', '=', 'songs.id')
            ->pluck('songs.spotify_id');

        // ログで確認
        \Log::info('Retrieved Spotify IDs', ['spotify_ids' => $spotifyIds]);

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

        // 最終的な trackUris をログで確認
        \Log::info('Generated track URIs', ['track_uris' => $trackUris]);

        return $trackUris;
    }

//===============================================================

    // セットリスト表示
    public function show($id)
//ブックマーク、レビューを書いて他ユーザーとも交流ができるようにするページとして設ける。
    {
        // セットリスト情報を取得
        $setlist = Setlist::with(['songs', 'songs.artist', 'comments.user'])->findOrFail($id);

        // Spotify プレイリストリンクの生成
        $spotifyPlaylistLink = 'https://open.spotify.com/playlist/' . $setlist->spotify_playlist_id;

        // 1ページあたり最大5件のコメントを取得
        $comments = $setlist->comments()->with('user')->latest()->paginate(5);

        // setlists.showビューにセットリストデータを渡す
        return view('setlists.show', [
            'setlist' => $setlist,
            'spotifyPlaylistLink' => $spotifyPlaylistLink,  // リンクを渡す
            'comments' => $comments,
        ]);
    }

//===============================================================

    public function storeComment(Request $request, $id)
    {
        $setlist = Setlist::findOrFail($id);

        $request->validate([
            'content' => 'required|string|max:500',
        ]);

        $setlist->comments()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        return redirect()->back()->with('success', 'コメントを投稿しました。');
    }

//===============================================================

    public function toggleBookmark(Request $request, $id)
    {
        $user = auth()->user();
        $setlist = Setlist::findOrFail($id);

        // 既にブックマークされているかチェック
        $bookmark = $setlist->bookmarks()->where('user_id', $user->user_id)->first();

        if ($bookmark) {
	    $bookmark->delete(); // ブックマークを外す
            return response()->json(['bookmarked' => false]);
        } else {
            $setlist->bookmarks()->create(['user_id' => $user->user_id]); // ブックマークする
            return response()->json(['bookmarked' => true]);
        }
    }

//===============================================================

    //ブックマークのカスケード削除を設定 
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($setlist) {
            // セットリストが削除されるとき、そのブックマークも削除
            $setlist->bookmarks()->delete();
        });
    }
//===============================================================

    public function showBookmarks()
    {
        $user = auth()->user();

        // 自分がブックマークしたセットリストを取得
        $bookmarkedSetlists = $user->bookmarks()
            ->where('bookmarkable_type', Setlist::class)
            ->with('bookmarkable') // セットリスト情報を取得
            ->get();

        // 自分がブックマークしたプレイリストを取得
        $bookmarkedPlaylists = $user->bookmarks()
            ->where('bookmarkable_type', Playlist::class)
            ->with('bookmarkable') // プレイリスト情報を取得
            ->get();

        return view('bookmarks', [
            'bookmarkedSetlists' => $bookmarkedSetlists,
            'bookmarkedPlaylists' => $bookmarkedPlaylists,
	]);
    }

//===============================================================

    public function myLists()
//myListsビューに、今まで作成したセットリストとプレイリストを選択し、setlists/showとplaylists/showの
//それぞれのリストの詳細ページに遷移することができるページとして設ける。
    {
        $user = auth()->user(); // 現在ログインしているユーザーを取得

        // ユーザーが作成したセットリストを取得
        $setlists = $user->setlists()->orderBy('created_at', 'desc')->get();
        // PlaylistController の myPlaylists メソッドを呼び出し、プレイリストを取得  
        $playlists = $user->playlists()->orderBy('created_at', 'desc')->get(); // ユーザーが作成したプレイリストを取得
        
        return view('myLists', compact('setlists', 'playlists'));
    }

//===============================================================

    // setlists/createにてセットリストに追加した曲を、１曲ずつ削除できるメソッド
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
	$setlist = Setlist::findOrFail($id); 
	//$setlist = Setlist::with(['songs.artist'])->findOrFail($id);

    	$setlistSongs = SetlistSong::where('setlist_id', $id)
            ->with(['song.artist']) 
	    ->get();


	// 最初の曲のアーティスト名を取得（曲がない場合は Unknown Artist）
	$artistName = optional($setlistSongs->first()->song->artist)->artist_name ?? 'Unknown Artist';

	// 取得したデータをログに出力
	\Log::info("ログここだよ！！！！！！！！！！！！！！！！！！！！");
    \Log::info("Editing setlist ID: {$id}, Artist Name: " . ($setlist->artist->artist_name ?? 'Unknown Artist'));

    	return view('setlists.create', [
            'setlist' => $setlist,
            'setlistSongs' => $setlistSongs,
	    'isEdit' => true // 編集画面かどうかを識別するフラグ
  	]);
    }

//===============================================================

    public function update(Request $request, $id) //Setlist $setlist)
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

        $setlist = Setlist::findOrFail($id);
        $setlist->update([
            'live_name' => $request->input('live_name'),
            'concert_date' => $request->input('concert_date'),
            'creator_comment' => $request->input('creator_comment'),
	]);



        // 既存の曲データを削除して新しいデータを保存
        SetlistSong::where('setlist_id', $setlist->id)->delete();

        foreach ($request->argInAddedSongs as $setlistData) {
            $artist = Artist::firstOrCreate(
                ['spotify_id' => $setlistData['artist_spotify_id']],
                ['artist_name' => $setlistData['artist_name']]
	    );

	    if (!empty($setlistData['artist_name'])) {
    		$artist->update(['artist_name' => $setlistData['artist_name']]);
	    }


            $song = Song::firstOrCreate(
                ['spotify_id' => $setlistData['song_spotify_id']],
                [
                    'song_name' => $setlistData['song_name'],
                    'artist_id' => $artist->id,
                ]
	    );

            SetlistSong::create([
                'setlist_id' => $setlist->id,
                'song_id' => $song->id,
                'artist_id' => $artist->id,
            ]);
        }
    
        return redirect()->route('setlists.show', $setlist->id)->with('success', 'セットリストを更新しました！');
    }

//===============================================================

    public function destroy($setlistId)
    {
        \Log::info('削除リクエストを受け取りました: ' . $setlistId);
        
        // 現在のユーザーを取得
        $user = auth()->user();
        
        // セットリストをIDで取得
        $setlist = $user->setlists()->findOrFail($setlistId);

        // セットリストを削除
        $setlist->delete();
        
        return redirect()->route('my.lists')->with('success', 'セットリストが削除されました。');
    }

}
