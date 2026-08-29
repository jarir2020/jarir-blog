<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Phase 5 — admin-editable post status.
 *
 * Replaces the hardcoded `enum('draft','published','archived')` on the
 * `posts` table. The `slug` column is the contract the rest of the
 * codebase keys off — `Post::scopePublished` and the public sidebar
 * filter both look up by `slug='published'`, not by id, so renaming
 * the row never breaks a query.
 */
class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'label',
        'color',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
