<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

#[Fillable(['actor_id', 'action', 'subject_type', 'subject_id', 'context', 'ip_address', 'created_at'])]
class AuditLog extends Model
{
    /**
     * Audit entries carry only a creation timestamp (append-only).
     */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Append a new audit entry.
     *
     * Callers must never place passwords, tokens, full salary records or other
     * secret values in `$context` (Master Prompt §D/§J).
     *
     * @param  array<string, mixed>  $context
     */
    public static function record(string $action, ?Model $subject = null, array $context = [], ?int $actorId = null): self
    {
        return static::query()->create([
            'actor_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'context' => $context === [] ? null : $context,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Enforce append-only semantics at the application layer.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new RuntimeException('Audit log entries are append-only and cannot be modified.');
        }

        return parent::save($options);
    }

    /**
     * @throws RuntimeException always
     */
    public function delete(): ?bool
    {
        throw new RuntimeException('Audit log entries are append-only and cannot be deleted.');
    }

    public function formattedAction(): string
    {
        return ucfirst(str_replace(['.', '_'], [' ', ' '], $this->action));
    }
}
