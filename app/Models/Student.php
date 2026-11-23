<?php

namespace App\Models;

use App\Notifications\StudentResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'student_number', 'password', 'status', 'programme', 'session', 'cohort', 'institution', 'phone', 'address', 'date_of_birth', 'placement_result', 'email_notices'])]
#[Hidden(['password', 'remember_token'])]
class Student extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return ['password' => 'hashed', 'date_of_birth' => 'date', 'email_notices' => 'boolean'];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(StudentInvoice::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(StudentRequest::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new StudentResetPassword($token));
    }
}
