<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'spotify_user_id',
        'account_name',
        'access_token',
        'refresh_token',
        'profile_image',
    ];

    // 主キーの指定
    protected $primaryKey = 'user_id'; // user_idを主キーとして指定

    // タイムスタンプの使用を指定
    public $timestamps = true;

//===============================================================

    //setlistsテーブルとのリレーションを定義
    public function setlists()
    {
        return $this->hasMany(Setlist::class, 'user_id');
    }

//===============================================================

    //playlistsテーブルとのリレーションを定義
    public function playlists()
    {
        return $this->hasMany(Playlist::class, 'user_id');
    }

//===============================================================

    //bookmarksテーブルとのリレーションを定義
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'user_id');
    }

//===============================================================

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }


    
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
