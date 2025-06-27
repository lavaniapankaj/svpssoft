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
        Schema::create('fee_details', function (Blueprint $table) {
            $table->id();
            $table->string('srno')->nullable();
            $table->integer('type')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->tinyInteger('academic_trans')->nullable();
            $table->integer('fee_of')->nullable();
            $table->float('amount',10,2)->nullable();
            $table->integer('paid_mercy')->nullable();
            $table->date('pay_date')->nullable();
            $table->integer('recp_no')->nullable();
            $table->string('ref_slip_no')->nullable();
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
        Schema::dropIfExists('fee_details');
    }
};
