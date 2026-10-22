<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'correspondence_category_id',
    'name',
    'subject_template',
    'body_template',
    'is_active',
])]
class CorrespondenceTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<CorrespondenceCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CorrespondenceCategory::class, 'correspondence_category_id');
    }

    /**
     * Substitute `{{ placeholders }}` without evaluating PHP or HTML.
     *
     * @param  array<string, string>  $values
     */
    public function render(string $template, array $values): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-z0-9_.]+)\s*\}\}/i',
            fn (array $match): string => (string) ($values[$match[1]] ?? ''),
            $template,
        );
    }
}
