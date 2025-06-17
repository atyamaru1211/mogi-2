<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;

class Rest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'rest_start_time',
        'rest_end_time',
    ];

    //★データの内容を自動的に変換するよう指示。今回は時間に
    protected $casts = [
        'rest_start_time' => 'datetime',
        'rest_end_time' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
