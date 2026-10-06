<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Processed single-use submission references (spec: safe-actions-one-submission.md).
// Reference, route, outcome and timestamp only — never contents, names, or amounts.
class IdempotencyKey extends Model
{
    public $timestamps = false;

    /** Retention window in days (spec §§3, 6: expiry restores old behavior, never blocks). */
    public const RETENTION_DAYS = 3;

    /**
     * Deterministic UUID marker from opaque parts (spec §10: markers keep
     * reference/route/outcome only — never contents, names, or amounts).
     * Stable across retries, distinct across intents. Valid for uuid columns.
     */
    public static function referenceFor(string ...$parts): string
    {
        $hash = sha1(implode('|', $parts));

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20, 12);
    }

    protected $fillable = [
        'reference',
        'route',
        'outcome',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
