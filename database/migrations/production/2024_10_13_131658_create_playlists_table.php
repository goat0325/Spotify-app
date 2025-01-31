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
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();  // 主キー
            $table->integer('user_id'); 
            
            //$table->foreignId('setlist_id')->nullable()->constrained('setlists', 'setlist_id');
            $table->string('spotify_playlist_id', 30)->nullable();  // SpotifyプレイリストID
            $table->string('spotify_playlist_link')->nullable();  // Spotify プレイリストのリンク
            //$table->string('spotify_url', 255)->nullable();  // SpotifyプレイリストのURL
            $table->string('playlist_name', 100);  // プレイリストの名前
            $table->string('creator_comment', 1000)->nullable();  // プレイリスト作成者のコメント（任意）
            //$table->boolean('is_based_on_setlist')->default(false);  // セットリストに基づくかどうかを管理
            //$table->boolean('is_shared')->default(false);  // シェアされているかどうかを管理
            //$table->boolean('is_deleted')->default(false);  // 削除されているかどうかを管理
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};
