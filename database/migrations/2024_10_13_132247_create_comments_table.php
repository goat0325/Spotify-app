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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();  // 主キー
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');  // ユーザーID。usersテーブルとのリレーション
            //$table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('commentable_id'); // 多態関連の ID
            $table->string('commentable_type'); // 多態関連のモデル名
            $table->text('content'); // コメント内容

            //$table->foreignId('setlist_id')->nullable()->constrained('setlists', 'setlist_id');  // セットリストID。setlistsテーブルとのリレーション
            //$table->foreignId('playlist_id')->nullable()->constrained('playlists', 'playlist_id');  // プレイリストID。playlistsテーブルとのリレーション
            //$table->string('review_text', 1000);  // レビュー内容
            $table->timestamps();;
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commentss');
    }
};
