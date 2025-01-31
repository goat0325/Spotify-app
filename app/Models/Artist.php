<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artist extends Model
{
    use HasFactory;

    protected $table = 'artists'; // テーブル名を指定
    protected $primaryKey = 'id'; // プライマリキーの指定
    protected $fillable = [
        'spotify_id',
        'artist_name',
    ];

    // SetlistSongとのリレーション
    public function setlistSongs()
    {
        return $this->hasMany(SetlistSong::class, 'artist_id');
    }

    // PlaylistSongとのリレーション
    public function playlistSongs()
    {
        return $this->hasMany(SetlistSong::class, 'artist_id');
    }

    // アーティストが持つ曲とのリレーション
    public function songs()
    {
        return $this->hasMany(Song::class, 'artist_id');
    }
}