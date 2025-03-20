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
        Schema::create('spotify_songs_data', function (Blueprint $table) {
            //$table->id('spotify_songs_data_id');  // 主キー
            //$table->string('spotify_id', 22)->unique();  // SpotifyのID
            //$table->foreignId('song_id')->constrained('songs', 'song_id');  // songsテーブルとのリレーション
            //$table->text('data_json');  // Spotifyの元データ（APIレスポンス全体）
            //$table->dateTime('retrieved_at')->nullable();  // データ取得日時
            //$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spotify_songs_data');
    }
};
