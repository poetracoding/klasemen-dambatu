<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertandingans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->integer('ronde')->default(1);
            $table->integer('nomor_pertandingan')->default(1);
            $table->date('tanggal')->nullable();
            $table->time('waktu')->nullable();
            $table->string('lokasi')->nullable();
            $table->foreignId('tim_home')->constrained('tims')->cascadeOnDelete();
            $table->foreignId('tim_away')->constrained('tims')->cascadeOnDelete();
            $table->integer('skor_home')->nullable()->default(0);
            $table->integer('skor_away')->nullable()->default(0);
            $table->foreignId('pemenang_id')->nullable()->constrained('tims')->nullOnDelete();
            $table->string('status')->default('belum_main'); // belum_main, berlangsung, selesai
            $table->string('catatan')->nullable();
            $table->timestamps();

            $table->unique(['liga_id', 'tim_home', 'tim_away']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pertandingans');
    }
};
