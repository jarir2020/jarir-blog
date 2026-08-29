<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 8 — admin-managed brand social link.
 *
 * The site's top utility bar and footer both render this list. The
 * controller auto-sets `icon` from `platform` so the two can't
 * drift apart.
 */
class SocialLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform',
        'label',
        'url',
        'icon',
        'order',
        'enabled',
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
}
