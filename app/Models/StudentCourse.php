<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'title', 'programme', 'session', 'credits', 'registration_open', 'timetable'])]
class StudentCourse extends Model
{
    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'registration_open' => 'boolean',
        ];
    }
}
