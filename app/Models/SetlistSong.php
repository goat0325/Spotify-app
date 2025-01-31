<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SetlistSong extends Model
{
    use HasFactory;

    protected $table = 'setlist_songs'; // テーブル名を指定
    protected $primaryKey = 'id'; // プライマリキーの指定

    protected $fillable = [
        'setlist_id',
        'song_id', // Songテーブルのidを保存
        'artist_id', // Artistテーブルのidを保存
    ];

    // Setlistとのリレーション
    public function setlist()
    {
        return $this->belongsTo(Setlist::class, 'setlist_id');
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
