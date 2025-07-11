<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $users = User::where('email', 'like', 'general%@example.com')->get();
        
        foreach ($users as $user) {
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::today()->subDays($i);

                if ($date->isWeekend()) {
                    continue;
                }

                $clockInTime = $date->copy()->setHour(9)->addMinutes(rand(0, 30));
                $clockOutTime = $date->copy()->setHour(18)->addMinutes(rand(0, 30));

                $restsData = [];
                $totalRestSeconds = 0;

                $restStart1 = $date->copy()->setHour(12)->addMinutes(rand(0, 15));
                $restEnd1 = $restStart1->copy()->addMinutes(rand(45, 60));
                $restsData[] = ['rest_start_time' => $restStart1, 'rest_end_time' => $restEnd1];
                $totalRestSeconds += $restEnd1->diffInSeconds($restStart1);

                if (rand(0, 1) === 1) {
                    $restStart2 = $date->copy()->setHour(15)->addMinutes(rand(0, 15));
                    $restEnd2 = $restStart2->copy()->addMinutes(rand(15, 30));
                    $restsData[] = ['rest_start_time' => $restStart2, 'rest_end_time' => $restEnd2];
                    $totalRestSeconds += $restEnd2->diffInSeconds($restStart2);
                }

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'clock_in_time' => $clockInTime,
                    'clock_out_time' => $clockOutTime,
                    'total_rest_time' => gmdate('H:i:s', $totalRestSeconds),
                ]);

                foreach ($restsData as $restData) {
                    Rest::create([
                        'attendance_id' => $attendance->id,
                        'rest_start_time' => $restData['rest_start_time'],
                        'rest_end_time' => $restData['rest_end_time'],
                    ]);
                }
            }
        }
    }
}
