<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\StampCorrectionRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'admin_status' => true,
            'email_verified_at' => now(),
        ]);

        $staff = [
            ['name' => '西 伶奈', 'email' => 'reina.n@coachtech.com'],
            ['name' => '山田 太郎', 'email' => 'taro.y@coachtech.com'],
            ['name' => '増田 一世', 'email' => 'issei.m@coachtech.com'],
            ['name' => '山本 敬吉', 'email' => 'keikichi.y@coachtech.com'],
            ['name' => '秋田 朋美', 'email' => 'tomomi.a@coachtech.com'],
            ['name' => '中西 教夫', 'email' => 'norio.n@coachtech.com'],
        ];

        foreach ($staff as $attributes) {
            $user = User::create($attributes + [
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            $this->seedAttendance($user);
        }
    }

    private function seedAttendance(User $user): void
    {
        $day = Carbon::today()->startOfMonth()->subMonths(5);
        $yesterday = Carbon::yesterday();

        for (; $day->lte($yesterday); $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }

            $clockIn = $day->copy()->setTime(8, 45)->addMinutes(random_int(0, 30));
            $clockOut = $day->copy()->setTime(17, 45)->addMinutes(random_int(0, 150));

            $record = AttendanceRecord::create([
                'user_id' => $user->id,
                'date' => $day->toDateString(),
                'clock_in' => $clockIn->format('H:i:s'),
                'clock_out' => $clockOut->format('H:i:s'),
            ]);
            $record->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);
        }

        $records = $user->attendanceRecords()->latest('date')->take(5)->get();

        foreach ($records->take(2) as $index => $record) {
            $application = StampCorrectionRequest::create([
                'attendance_record_id' => $record->id,
                'user_id' => $user->id,
                'date' => $record->date,
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => $index === 0 ? '電車遅延のため' : '打刻忘れのため',
                'status' => $index === 0 ? StampCorrectionRequest::STATUS_PENDING : StampCorrectionRequest::STATUS_APPROVED,
            ]);
            $application->breaks()->create(['break_in' => '12:00:00', 'break_out' => '13:00:00']);
        }
    }
}
