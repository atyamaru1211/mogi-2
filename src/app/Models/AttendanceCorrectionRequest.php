<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequestRest;


class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'requested_date',
        'requested_clock_in_time',
        'requested_clock_out_time',
        'requested_total_rest_time',
        'requested_note',
        'status',
    ];

    protected $casts = [
        'requested_date' => 'date',
        'requested_clock_in_time' => 'datetime',
        'requested_clock_out_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function requestedRests()
    {
        return $this->hasMany(AttendanceCorrectionRequestRest::class, 'attendance_correction_request_id');
    }
}
