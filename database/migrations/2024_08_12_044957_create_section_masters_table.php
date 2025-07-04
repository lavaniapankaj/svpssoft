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
        Schema::create('section_masters', function (Blueprint $table) {
            $table->id();
            $table->string('section');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('add_user_id');
            $table->unsignedBigInteger('edit_user_id');
            $table->tinyInteger('active')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_masters');
    }
};
