<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liga_id')->nullable()->constrained('ligas')->nullOnDelete();
            $table->string('judul')->nullable();
            $table->string('deskripsi')->nullable();
            $table->string('file_path');
            $table->integer('urutan')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (file_exists(public_path('peraturan4.png'))) {
            $ligaId = DB::table('ligas')->value('id');
            DB::table('posters')->insert([
                'liga_id' => $ligaId,
                'judul' => 'Peraturan & Tata Tertib Pertandingan',
                'deskripsi' => 'Pedoman resmi pelaksanaan pertandingan Liga Dam Batu.',
                'file_path' => 'peraturan4.png',
                'urutan' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('posters');
    }
};
