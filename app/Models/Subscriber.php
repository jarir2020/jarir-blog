<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    protected $fillable = ['email', 'subscribed_at'];

    protected $casts = [
        'subscribed_at' => 'datetime',
    ];

    /**
     * `subscribed_at` is the only timestamp we care about; the table has
     * no `created_at` / `updated_at` columns.
     */
    public $timestamps = false;
}
