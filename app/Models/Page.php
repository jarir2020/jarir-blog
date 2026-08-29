<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 9 — admin-editable page.
 *
 * `slug` is the full URL path ("about", "about/our-mission",
 * "contact"). `parent_slug` is denormalized for fast "list sub-
 * pages of a parent" queries.
 */
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'excerpt',
        'body',
        'order',
        'enabled',
        'parent_slug',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'order' => 'integer',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }

    public function scopeChildrenOf($query, string $parentSlug)
    {
        return $query->where('parent_slug', $parentSlug);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_slug', 'slug');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_slug', 'slug');
    }
}
