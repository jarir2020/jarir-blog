<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 6 — admin-managed sidebar widget.
 *
 * The widget's `type` decides how the public sidebar renders it. The
 * SidebarResolver service reads the type + the JSON `settings` column
 * and produces the public response payload.
 *
 * Allowed types (kept as a free-form string so admins can add their
 * own widget types in the future without a migration):
 *   - popular_recent_comments: tabbed widget (popular / recent / comments)
 *   - category:               5 most recent posts in a chosen category
 *   - video:                  video gallery (placeholder in v1)
 *   - html:                   arbitrary HTML
 *   - social:                 social link list
 *   - archives:               archive dropdown (year/month)
 *   - newsletter:             subscribe form (rendered by the Sidebar component)
 */
class Widget extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'position',
        'order',
        'enabled',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'enabled' => 'boolean',
        'order' => 'integer',
    ];

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeForPosition($query, string $position)
    {
        return $query->where('position', $position)->orderBy('order')->orderBy('id');
    }
}
