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
        Schema::create('sms_group', function (Blueprint $table) {
            $table->id();
            $table->string('group_name');
            $table->tinyInteger('active')->default(1);
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
        Schema::dropIfExists('sms_group');
    }
};
