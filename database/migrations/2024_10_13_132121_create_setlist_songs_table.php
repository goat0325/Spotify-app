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
        Schema::create('setlist_songs', function (Blueprint $table) {
            $table->id();  // 主キー
            $table->foreignId('setlist_id')->constrained('setlists')->onDelete('cascade');
            
            // 外部キーとして `song_id` と `artist_id` を追加
            $table->foreignId('song_id')->nullable()->constrained('songs')->onDelete('cascade');
            $table->foreignId('artist_id')->nullable()->constrained('artists')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setlist_songs');
    }
};
