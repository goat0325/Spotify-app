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
        Schema::create('artists', function (Blueprint $table) {
            $table->id();  // 主キー
            $table->string('artist_name');// アーティスト名
            //$table->date('debut_date')->nullable();  // デビュー日
            $table->string('spotify_id', 22)->unique();  // SpotifyのアーティストID（NULLを許可）
            //$table->string('spotify_url', 255)->unique();  // SpotifyのアーティストのURL（NULLを許可）
            //$table->boolean('is_from_spotify')->default(false);  // Spotifyから取得されたかどうか
            //$table->dateTime('spotify_retrieval_time')->nullable();  // データが取得された日時（NULLを許可）
            $table->timestamps();

            // インデックスの追加
            $table->index('artist_name', 'idx_artist_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
