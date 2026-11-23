<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['student_id', 'title', 'path', 'original_name', 'status', 'issued_by_staff'])]
class StudentDocument extends Model
{
    protected function casts(): array
    {
        return [
            'issued_by_staff' => 'boolean',
        ];
    }
}
