<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beritas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liga_id')->nullable()->constrained('ligas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('judul', 180);
            $table->string('slug', 200)->unique();
            $table->string('kategori', 50)->default('Berita');
            $table->string('ringkasan', 300)->nullable();
            $table->text('isi');
            $table->string('gambar', 255)->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();
        });

        Schema::create('berita_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berita_id')->constrained('beritas')->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->timestamp('created_at')->nullable();

            $table->unique(['berita_id', 'visitor_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_likes');
        Schema::dropIfExists('beritas');
    }
};
