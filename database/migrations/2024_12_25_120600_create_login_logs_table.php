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
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('browser')->nullable();
            $table->string('id_type');
            $table->string('panel')->nullable();
            $table->string('user_name')->nullable();
            $table->dateTime('date')->nullable();
            $table->integer('user_id')->nullable();
            $table->tinyInteger('success')->default(0);
            $table->string('password_attempt')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
