<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->string('app_icon', 255)->nullable()->after('google_analytics_id');
            $table->string('app_tagline', 160)->nullable()->after('app_icon');
            $table->string('footer_text', 160)->nullable()->after('app_tagline');
            $table->string('kontak_panitia', 120)->nullable()->after('footer_text');
            $table->string('theme_color', 20)->nullable()->after('kontak_panitia');
            $table->text('tentang_aplikasi')->nullable()->after('theme_color');
        });
    }

    public function down(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->dropColumn([
                'app_icon',
                'app_tagline',
                'footer_text',
                'kontak_panitia',
                'theme_color',
                'tentang_aplikasi',
            ]);
        });
    }
};
