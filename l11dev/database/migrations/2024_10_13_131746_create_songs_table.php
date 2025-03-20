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
        Schema::create('songs', function (Blueprint $table) {
            $table->id();  // 主キー
            $table->string('song_name'); // 曲名

            // アーティストID。artistsテーブルとのリレーション
            $table->foreignId('artist_id')->constrained('artists')->onDelete('cascade');  
            
            //$table->date('release_date')->nullable();  // リリース日
            $table->string('spotify_id', 22)->unique();  // Spotifyの曲ID
            //$table->string('spotify_url', 255)->default('');  // Spotifyの曲のURL
            //$table->dateTime('last_updated')->nullable();  // 最終更新日時
            //$table->boolean('is_from_spotify')->default(false);  // Spotifyから取得されたかどうか
            //$table->dateTime('spotify_retrieval_time')->nullable();  // データが取得された日時
            //$table->string('artist_name', 50)->constrained('artists', 'artist_name');// アーティスト名
            //$table->string('artist_spotify_id', 22); 
            $table->timestamps();

            // インデックスの追加
            $table->index('song_name', 'idx_song_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
