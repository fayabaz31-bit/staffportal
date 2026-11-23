<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['student_id', 'category', 'subject', 'body', 'proposed_changes', 'status', 'decision_note', 'reviewed_by', 'reviewed_at'])]
class StudentRequest extends Model
{
    protected function casts(): array
    {
        return [
            'proposed_changes' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(StudentRequestMessage::class);
    }
}
