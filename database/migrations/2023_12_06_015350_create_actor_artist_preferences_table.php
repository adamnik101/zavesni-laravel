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
        Schema::create('actor_artist_preferences', function (Blueprint $table) {
            $table->primary(['actor_id', 'artist_id']);
            $table->foreignUuid('actor_id')->references('id')->on('actors');
            $table->foreignUuid('artist_id')->references('id')->on('artists');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actor_artist_preferences');
    }
};
