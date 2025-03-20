<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    use HasFactory;

    protected $table = 'songs'; // テーブル名を指定（デフォルトではクラス名の複数形が使われるため、必要ないが明示的に指定）
    protected $primaryKey = 'id'; // プライマリキーの指定
    protected $fillable = [
        'song_name',
        'spotify_id',
        'artist_id', // artist_idが追加される
    ];

    // 曲名での検索メソッドを追加
    public static function searchByName($query)
    {
        return self::where('name', 'LIKE', '%' . $query . '%')->get();
    }

    // SetlistSongとのリレーション
    public function setlistSongs()
    {
        return $this->hasMany(SetlistSong::class, 'song_id');
    }

    // 曲が所属するアーティストとのリレーション
    public function artist()
    {
        return $this->belongsTo(Artist::class, 'artist_id');
    }

    public function setlists()
    {
        return $this->belongsToMany(Setlist::class, 'setlist_songs', 'song_id', 'setlist_id');
        //return $this->belongsToMany(Setlist::class);
    }

//=============================================================

    // PlaylistSongとのリレーション
    public function playlistSongs()
    {
        return $this->hasMany(SetlistSong::class, 'song_id');
    }

    //Playlistとのリレーション
    public function playlists()
    {
        return $this->belongsToMany(Setlist::class, 'playlist_songs', 'song_id', 'playlist_id');
    }

}