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
        Schema::create('transport_fee_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->float('admission_fee', 10, 2)->nullable();
            $table->float('inst_1', 10, 2)->nullable();
            $table->float('inst_2', 10, 2)->nullable();
            $table->float('inst_total', 10, 2)->nullable();
            $table->float('ins_discount', 10, 2)->nullable();
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
        Schema::dropIfExists('transport_fee_masters');
    }
};
