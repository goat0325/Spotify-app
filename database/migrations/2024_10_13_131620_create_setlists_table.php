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
        Schema::create('setlists', function (Blueprint $table) {
            $table->id();  // 自動的にインクリメントされる主キーを作成
            $table->integer('user_id');
            $table->string('spotify_playlist_id', 30)->nullable();  // SpotifyプレイリストID
            $table->string('spotify_playlist_link')->nullable();  // Spotify プレイリストのリンク
            //$table->string('spotify_url', 255)->nullable();  // SpotifyプレイリストのURL
            $table->string('live_name', 100);  // ライブ名
            $table->date('concert_date');  // コンサートの日付
            $table->string('creator_comment', 1000)->nullable();  // セットリスト作成者のコメント（任意）
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setlists');
    }
};
