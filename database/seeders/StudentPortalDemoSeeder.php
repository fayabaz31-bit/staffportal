<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\StudentCourse;
use App\Models\StudentNotice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentPortalDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('Student demo data is available only in the local environment.');
        }
        $password = Str::password(16);
        $created = false;
        DB::transaction(function () use ($password, &$created): void {
            $student = Student::query()->firstOrCreate(['email' => 'student.demo@nflv.test'], [
                'student_number' => 'NFLV/DEMO/001', 'name' => 'Demo Student',
                'password' => $password, 'programme' => 'Language immersion',
                'session' => '2026/2027', 'cohort' => 'Local demonstration', 'institution' => 'Demo partner university',
            ]);
            $created = $student->wasRecentlyCreated;
            foreach ([
                ['code' => 'FRE101', 'title' => 'Oral French', 'timetable' => 'Monday · 09:00–11:00 · Room 1'],
                ['code' => 'FRE102', 'title' => 'French Grammar', 'timetable' => 'Tuesday · 10:00–12:00 · Room 2'],
                ['code' => 'FRE103', 'title' => 'French Culture', 'timetable' => 'Thursday · 11:00–13:00 · Room 1'],
            ] as $course) {
                StudentCourse::query()->firstOrCreate(['code' => $course['code'], 'programme' => $student->programme, 'session' => $student->session], [
                    ...$course, 'credits' => 2, 'registration_open' => true,
                ]);
            }
            StudentNotice::query()->firstOrCreate(['title' => 'Local portal demonstration'], [
                'body' => 'This account and its course schedule are demonstration data. Submit a course registration or support request to try the workflows.',
                'programme' => $student->programme, 'session' => $student->session, 'published_at' => now(),
            ]);
        });
        if ($created) {
            $this->command->info('Local demo student: student.demo@nflv.test');
            $this->command->info('Initial password: '.$password);
        } else {
            $this->command->info('Demo account already exists. Its password has not been changed.');
        }
    }
}
