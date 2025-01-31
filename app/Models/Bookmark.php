<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bookmark extends Model
{
    protected $fillable = ['user_id'];

    //ポリモーフィックリレーションを定義
    public function bookmarkable()
    {
        return $this->morphTo();
    }
}