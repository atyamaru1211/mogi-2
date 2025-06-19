<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionRequestRestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_correction_request_rests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_correction_request_id');
            $table->foreign('attendance_correction_request_id', 'acr_request_id')->references('id')->on('attendance_correction_requests')->cascadeOnDelete();
            $table->timestamp('requested_rest_start_time');
            $table->timestamp('requested_rest_end_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_correction_request_rests');
    }
}
