<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_liga')->default('LIGA DAM BATU');
            $table->string('musim')->nullable()->default('2026');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status')->default('draft'); // draft, berjalan, selesai
            $table->integer('jumlah_meja')->default(2);
            $table->string('hari_pertandingan')->default('1,2,3,4,5,6,7');
            $table->text('tanggal_libur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligas');
    }
};
