<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaylistSong extends Model
{
    use HasFactory;

    protected $table = 'playlist_songs'; // テーブル名を指定
    protected $primaryKey = 'id'; // プライマリキーの指定

    protected $fillable = [
        'playlist_id',
        'song_id', // Songテーブルのidを保存
        'artist_id', // Artistテーブルのidを保存
    ];

    // Playlistとのリレーション
    public function playlist()
    {
        return $this->belongsTo(Setlist::class, 'playlist_id');
    }

//==============================================================

    // Songとのリレーション
    public function song()
    {
        return $this->belongsTo(Song::class, 'song_id');
    }

//==============================================================

    // Artistとのリレーション
    public function artist()
    {
        return $this->belongsTo(Artist::class, 'artist_id');
    }
    
}
