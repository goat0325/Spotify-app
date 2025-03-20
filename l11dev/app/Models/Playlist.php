<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Playlist extends Model
{
    use HasFactory;

    protected $table = 'playlists'; // テーブル名の指定（デフォルトではクラス名の複数形が使われるため、必要ないが明示的に指定）
    protected $primaryKey = 'id'; // プライマリキーの指定

    protected $fillable = [
        'user_id',
        'playlist_name',
        'creator_comment',
        'spotify_playlist_id'
    ];

//============================================================

    // リレーションシップを定義（例：ユーザーとの関係）
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

//============================================================

    // SetlistSongとのリレーション
    public function songs()
    {
        return $this->belongsToMany(Song::class, 'playlist_songs', 'playlist_id', 'song_id')
                    ->with('artist'); // アーティスト情報を同時に取得する
    }

//============================================================

    // Playlistモデルとのポリモーフィックリレーションを追加
    public function bookmarks()
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

//============================================================

    //プレイリストに紐づくコメントを取得する
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}