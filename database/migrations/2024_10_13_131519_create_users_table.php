<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');  //主キー。user_idとして
            $table->string('spotify_user_id')->unique();  // SpotifyのユーザーIDを保存。usersテーブルでは重複はダメ。
            $table->string('account_name');  // Spotifyのアカウント名を保存
            // Spotifyアカウントのプロフィールのデフォルト画像。（l11dev/public/images/default-profile.jpg　に設定）
            $table->string('profile_image')->default('images/default-profile.jpg');
            $table->string('access_token', 2500);  // Spotify APIのアクセストークン（有効期間が短いので再発行が必要）
            $table->string('refresh_token', 1000)->nullable();  // Spotify APIのリフレッシュトークン（有効期間が長いため、長めに設定）
            $table->timestamps();  // 'created_at' と 'updated_at' が自動的に作成される
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
