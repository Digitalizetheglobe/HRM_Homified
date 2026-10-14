<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('punch_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meters')->default(20);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('employee_punch_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('punch_location_id');
            $table->timestamps();

            $table->unique(['employee_id', 'punch_location_id'], 'employee_punch_location_unique');
            $table->index('punch_location_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_punch_location');
        Schema::dropIfExists('punch_locations');
    }
};
