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
        Schema::create('employee_payable_days', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('total_payable_days', 8, 2)->default(0);
            $table->decimal('final_payable_salary', 8, 2)->default(0);
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_payable_days');
    }
};
