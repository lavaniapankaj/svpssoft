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
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('srno')->nullable();
            $table->integer('subject_id')->nullable();
            $table->integer('exam_id')->nullable();
            $table->integer('marks')->nullable();
            $table->tinyInteger('attendance')->nullable();
            $table->tinyInteger('active')->default(0);
            $table->unsignedBigInteger('add_user_id')->nullable();
            $table->unsignedBigInteger('edit_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marks');
    }
};
