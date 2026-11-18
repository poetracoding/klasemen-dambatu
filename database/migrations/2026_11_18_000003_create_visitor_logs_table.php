<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_logs', function (Blueprint $table) {
            $table->id();
            $table->date('visit_date')->index();
            $table->string('session_id', 100)->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('path', 255)->index();
            $table->string('page_title', 120)->nullable();
            $table->string('device_type', 20)->default('desktop');
            $table->string('user_agent', 255)->nullable();
            $table->string('referer', 255)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['visit_date', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_logs');
    }
};
