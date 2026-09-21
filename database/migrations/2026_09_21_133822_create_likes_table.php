<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liker_artist_id')->constrained('artists')->cascadeOnDelete();
            $table->foreignId('liked_artist_id')->constrained('artists')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['liker_artist_id', 'liked_artist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};