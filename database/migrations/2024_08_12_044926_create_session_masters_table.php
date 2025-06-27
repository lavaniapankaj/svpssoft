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
        Schema::create('session_masters', function (Blueprint $table) {
            $table->id();
            $table->year('start_year');
            $table->year('end_year');
            $table->string('session');
            $table->tinyInteger('current_session')->default(0);
            $table->tinyInteger('admin_current_session')->default(0);
            $table->tinyInteger('fee_current_session')->default(0);
            $table->tinyInteger('marks_current_session')->default(0);
            $table->tinyInteger('student_current_session')->default(0);
            $table->tinyInteger('inventory_current_session')->default(0);
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
        Schema::dropIfExists('session_masters');
    }
};
