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
        Schema::create('stu_main_srno', function (Blueprint $table) {
            $table->id();
            $table->string('srno')->nullable();
            $table->tinyInteger('school')->nullable();
            $table->string('class')->nullable();
            $table->integer('section')->nullable();
            $table->integer('rollno')->nullable();
            $table->integer('session_id')->nullable();
            $table->string('image')->nullable();
            $table->integer('transport')->nullable();
            $table->tinyInteger('age_proof')->nullable();
            $table->tinyInteger('gender')->nullable();
            $table->tinyInteger('religion')->nullable();
            $table->date('admission_date')->nullable();
            $table->unsignedBigInteger('relation_code')->nullable();
            $table->string('prev_srno')->nullable();
            $table->date('form_submit_date')->nullable();
            $table->float('trans_1st_inst',10,2)->nullable();
            $table->float('trans_2nd_inst',10,2)->nullable();
            $table->float('trans_total',10,2)->nullable();
            $table->float('trans_discount',10,2)->nullable();
            $table->string('reason')->nullable();
            $table->string('TCRefNo')->nullable();
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
        Schema::dropIfExists('stu_main_srno');
    }
};
