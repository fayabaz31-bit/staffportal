<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['student_id', 'reference', 'description', 'amount_kobo', 'session', 'due_date', 'rrr', 'paid_at'])]
class StudentInvoice extends Model
{
    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }
}
