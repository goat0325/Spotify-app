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
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');  //usersテーブルとのリレーション
            $table->nullableMorphs('bookmarkable'); // セットリストまたはプレイリストを柔軟に対応
            $table->timestamps();
            
            //$table->foreignId('setlist_id')->nullable()->constrained('setlists', 'setlist_id');  // セットリストID。setlistsテーブルとのリレーション
            //$table->foreignId('playlist_id')->nullable()->constrained('playlists', 'playlist_id');  // プレイリストID。playlistsテーブルとのリレーション
            

            // ユニーク制約を追加
            //$table->unique(['user_id', 'setlist_id']);  // ユーザーが同じセットリストを複数回ブックマークすることを防ぐ
            //$table->unique(['user_id', 'playlist_id']);  // ユーザーが同じプレイリストを複数回ブックマークすることを防ぐ
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
    }
};
