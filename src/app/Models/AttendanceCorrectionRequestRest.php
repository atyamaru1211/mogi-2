<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceCorrectionRequest;

class AttendanceCorrectionRequestRest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correction_request_id',
        'requested_rest_start_time',
        'requested_rest_end_time',
    ];

    protected $casts = [
        'requested_rest_start_time' => 'datetime',
        'requested_rest_end_time' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class, 'attendance_correction_request_id');
    }
}
