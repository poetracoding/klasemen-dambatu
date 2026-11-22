<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertandingan_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pertandingan_id')->constrained('pertandingans')->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->string('choice', 10); // home | away
            $table->timestamps();

            $table->unique(['pertandingan_id', 'visitor_hash']);
            $table->index(['pertandingan_id', 'choice']);
        });

        Schema::create('pertandingan_cheers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pertandingan_id')->constrained('pertandingans')->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->string('reaction', 20); // fire | clap | target | trophy
            $table->timestamps();

            $table->unique(['pertandingan_id', 'visitor_hash']);
            $table->index(['pertandingan_id', 'reaction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pertandingan_cheers');
        Schema::dropIfExists('pertandingan_votes');
    }
};
