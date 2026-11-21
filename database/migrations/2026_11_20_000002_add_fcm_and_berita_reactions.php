<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 512);
            $table->string('token_hash', 64)->unique();
            $table->boolean('notify_skor')->default(true);
            $table->boolean('notify_berita')->default(true);
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::table('beritas', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('published_at')->index();
        });

        Schema::table('berita_likes', function (Blueprint $table) {
            $table->string('reaction', 20)->default('love')->after('visitor_hash')->index();
        });
    }

    public function down(): void
    {
        Schema::table('berita_likes', function (Blueprint $table) {
            $table->dropIndex(['reaction']);
            $table->dropColumn('reaction');
        });

        Schema::table('beritas', function (Blueprint $table) {
            $table->dropIndex(['notified_at']);
            $table->dropColumn('notified_at');
        });

        Schema::dropIfExists('fcm_tokens');
    }
};
