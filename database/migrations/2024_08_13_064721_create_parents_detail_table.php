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
        Schema::create('parents_detail', function (Blueprint $table) {
            $table->id();
            $table->string('srno')->nullable();
            $table->string('f_name')->nullable();
            $table->string('m_name')->nullable();
            $table->string('g_father')->nullable();
            $table->unsignedBigInteger('state_id');
            $table->unsignedBigInteger('district_id');
            $table->string('address')->nullable();
            $table->integer('category_id')->nullable();
            $table->string('email')->nullable();
            $table->string('f_mobile')->nullable();
            $table->string('pin_code')->nullable();
            $table->tinyInteger('f_occupation')->nullable();
            $table->tinyInteger('m_occupation')->nullable();
            $table->string('m_mobile')->nullable();
            $table->unsignedBigInteger('add_user_id')->nullable();
            $table->unsignedBigInteger('edit_user_id')->nullable();
            $table->tinyInteger('active')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parents_detail');
    }
};
