<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Phase 10 — site setting (key/value).
 *
 * Admin-editable site-wide content like the site name, tagline,
 * and contact details. New settings can be added by inserting a
 * new row; the `Settings` service (`App\Support\Settings`)
 * exposes typed accessors.
 */
class Setting extends Model
{
    use HasFactory;

    // Laravel would tableize `Setting` to `settings` by default; we
    // use a project-specific table name so it doesn't collide with
    // anything else and matches the migration.
    protected $table = 'site_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public $timestamps = true;
}
