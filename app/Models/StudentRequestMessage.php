<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['student_request_id', 'staff_id', 'from_staff', 'body'])]
class StudentRequestMessage extends Model
{
    protected function casts(): array
    {
        return [
            'from_staff' => 'boolean',
        ];
    }
}
