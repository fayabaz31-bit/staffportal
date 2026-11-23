<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'student_course_id', 'status', 'score', 'grade', 'result_published_at'])]
class StudentEnrollment extends Model
{
    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'result_published_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(StudentCourse::class, 'student_course_id');
    }
}
