<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->string('google_analytics_id', 40)->nullable()->after('musim');
        });
    }

    public function down(): void
    {
        Schema::table('ligas', function (Blueprint $table) {
            $table->dropColumn('google_analytics_id');
        });
    }
};
