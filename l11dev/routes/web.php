<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SetlistController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\SongController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;


Route::get('/test-db', function () {
    try {
        \DB::connection()->getPdo();
        return "Database connection is successful!";
    } catch (\Exception $e) {
        return "Could not connect to the database. Error: " . $e->getMessage();
    }
});

//ログインからホーム画面===================================
//ユーザーログインと認証
//ログイン画面へのルート

Route::get('/', function () {
    return redirect()->route('login'); // ログイン画面へリダイレクト
});


Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');

//Spotifyへのリダイレクト
Route::get('login/redirect', [LoginController::class, 'redirectToSpotify'])->name('spotify.redirect');
Route::get('/spotify/auth', [LoginController::class, 'redirectToSpotify'])->name('spotify.auth');

//ログアウト処理を行うルート
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

//Spotifyアカウント認証後、ホーム画面へのコールバック
Route::get('/callback', [LoginController::class, 'handleSpotifyCallback'])->name('spotify.callback');

// ホーム画面
Route::get('/home', [HomeController::class, 'index'])->name('home');

//=======================================================

//ログインしたユーザーのみ使える機能
//Route::middleware('auth')->group(function () {

//===========================================================================
//セットリスト用のルート
//セットリスト関連
Route::resource('setlists', SetlistController::class);

//setlists/createページ Spotifyから曲を検索・追加し、セットリストをDBに保存するためのルート群
// 曲の検索
Route::get('/songs/search', [SongController::class, 'search'])->name('songs.search');
//セットリストに曲を追加する際に、まずDBにその曲が存在するかを確認し、存在しなければ新たに保存
Route::post('/setlists/create/check-existence', [SongController::class, 'checkExistence']);
//セットリストに曲を追加する際、ajaxを使いページをリロードすることなく追加するためのルート
Route::post('/setlists/add-song', [SetlistController::class, 'addSong'])->name('setlists.addSong');
// ユーザーIDを取得するルート
Route::get('/get-user-data', [SetlistController::class, 'getUserData']);
// 曲をセットリストに追加
Route::post('/setlists/insert-setlist', [SetlistController::class, 'InsertSetlist'])->name('setlists.InsertSetlist');

//　setlists/showページ　createで作成したセットリストを表示、Spotifyに飛んで曲を聴く
Route::get('/setlists/{setlist}', [SetlistController::class, 'show'])->name('setlists.show');
Route::get('/setlists/{setlistId}/generate-playlist', [SetlistController::class, 'generateSpotifyPlaylist'])->name('setlists.generatePlaylist');

//　setlists/createにて、セットリストに追加した曲を１曲ずつ削除する
Route::delete('/setlists/songs/{id}', [SetlistController::class, 'removeSong'])->name('setlists.songs.remove');

//showページから内容を編集する
Route::get('/setlists/{id}/edit', [SetlistController::class, 'edit'])->name('setlists.edit');
Route::put('/setlists/{id}', [SetlistController::class, 'update'])->name('setlists.update');

//セットリストの削除
Route::delete('/setlist/{setlist}/delete', [SetlistController::class, 'destroy'])->name('setlists.destroy');

//==========================================================================
//Myセットリスト／プレイリスト用のルート　時作したセットリストを一覧で見る
Route::get('/my-lists', [SetlistController::class, 'myLists'])->name('my.lists');
//すでにセットリスト用のルートで記載しているが、自作したセットリスト一覧から詳細ページに遷移するためのルート
//Route::get('/setlists/{setlist}', [SetlistController::class, 'show'])->name('setlists.show');

//ブックマーク機能、ブックマークをしたリスト一覧ページ
//Route::get('/bookmark', [SetlistController::class, 'bookmarked'])->name('bookmarks');
Route::get('/bookmarks', [SetlistController::class, 'showBookmarks'])->name('bookmarks.show');


// セットリスト・プレイリストそれぞれのブックマークのルート
Route::post('setlists/{id}/bookmark', [SetlistController::class, 'toggleBookmark'])->name('setlists.toggleBookmark');
Route::post('playlists/{id}/bookmark', [PlaylistController::class, 'toggleBookmark'])->name('playlists.toggleBookmark');

// セットリスト・プレイリストそれぞれのコメントのルート
Route::post('setlists/{id}/comments', [SetlistController::class, 'storeComment'])->middleware('auth')->name('setlists.comments.store');
Route::post('playlists/{id}/comments', [PlaylistController::class, 'storeComment'])->middleware('auth')->name('playlists.comments.store');

//=======================================================

//プレイリスト関連
Route::get('/playlist/create', [PlaylistController::class, 'create'])->name('playlist.create');


//プレイリスト関連
Route::resource('playlists', PlaylistController::class);

//playlists/createページ Spotifyから曲を検索・追加し、プレイリストをDBに保存するためのルート群
// 曲の検索
Route::get('/songs/search', [SongController::class, 'search'])->name('songs.search');
//プレイリストに曲を追加する際に、まずDBにその曲が存在するかを確認し、存在しなければ新たに保存
Route::post('/playlists/create/check-existence', [SongController::class, 'checkExistence']);
//プレイリストに曲を追加する際、ajaxを使いページをリロードすることなく追加するためのルート
Route::post('/playlists/add-song', [PlaylistController::class, 'addSong'])->name('playlists.addSong');
// ユーザーIDを取得するルート
Route::get('/get-user-data', [PlaylistController::class, 'getUserData']);
// 曲をプレイリストに追加
Route::post('/playlists/insert-playlist', [PlaylistController::class, 'InsertPlaylist'])->name('playlists.InsertPlaylist');

//playlists/showページ　createで作成したセットリストを表示、Spotifyに飛んで曲を聴く
Route::get('/playlists/{playlist}', [PlaylistController::class, 'show'])->name('playlists.show');
Route::get('/playlists/{playlistId}/generate-playlist', [PlaylistController::class, 'generateSpotifyPlaylist'])->name('playlists.generatePlaylist');

//　playlists/createにて、プレイリストに追加した曲を１曲ずつ削除する
Route::delete('/playlists/songs/{id}', [PlaylistController::class, 'removeSong'])->name('playlists.songs.remove');

//showページから内容を編集する
Route::get('/playlists/{id}/edit', [PlaylistController::class, 'edit'])->name('playlists.edit');
Route::put('/playlists/{id}', [PlaylistController::class, 'update'])->name('playlists.update');

//プレイリストの削除
Route::delete('/playlist/{playlist}/delete', [PlaylistController::class, 'destroy'])->name('playlists.destroy');


//=======================================================

//検索機能
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/search/results', [SearchController::class, 'searchResults'])->name('search.results');


//ゲスト用
//Route::get('/guest/home', [HomeController::class, 'guestHome'])->name('guest.home');
