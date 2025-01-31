<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setlist extends Model
{
    use HasFactory;

    protected $table = 'setlists'; // テーブル名の指定（デフォルトではクラス名の複数形が使われるため、必要ないが明示的に指定）
    protected $primaryKey = 'id'; // プライマリキーの指定

    protected $fillable = [
        'user_id',
        'live_name',
        'concert_date',
        'creator_comment',
        'spotify_playlist_id'
        
        //'song_name',
        //'song_spotify_id',
        //'artist_name',
        //'artist_spotify_id',
    ];

//============================================================

    // リレーションシップを定義（例：ユーザーとの関係）
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

//============================================================

    // Songとのリレーション
    public function songs()
    {
        return $this->belongsToMany(Song::class, 'setlist_songs', 'setlist_id', 'song_id')
                    ->with('artist'); // アーティスト情報を同時に取得する
    }

//============================================================

    // Setlistモデルとのポリモーフィックリレーションを追加
    public function bookmarks()
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

//============================================================

    //セットリストに紐づくコメントを取得する
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}