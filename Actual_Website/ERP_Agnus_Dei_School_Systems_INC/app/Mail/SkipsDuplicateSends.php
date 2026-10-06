<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\IdempotencyKey;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

// Safe Actions — One Submission, jobs half (spec: safe-actions-one-submission.md).
//
// A queued mail that is retried must not send twice: the first attempt
// records a once-only marker, a retry with a seen marker sends nothing.
// (Framework job-middleware is not forwarded to mailables, so the guard
// lives in send() itself, which the worker calls on every attempt.)
// The marker chains the triggering submission (fresh submission → fresh
// marker → sends; retry → same marker → skipped) or, where no submission
// exists, a deterministic per-cycle value. Null marker = unprotected but
// never blocked. Route-scoped, so HTTP rows can never suppress a mail.
// Deliberately untyped property: a crafted non-string assignment must pass
// through, never fatal at assignment time (checked below with is_string).
trait SkipsDuplicateSends
{
    /** @var string|null Single-use marker for this send; null means pass straight through. */
    public $idempotencyMarker = null;

    /**
     * @return $this
     */
    public function send($mailer)
    {
        $marker = $this->idempotencyMarker;

        if (! is_string($marker) || $marker === '') {
            return parent::send($mailer);
        }

        $route = 'mail:'.static::class;

        if ($this->markerSeen($marker, $route)) {
            Log::info('Duplicate mail suppressed', ['mail' => static::class]);

            return $this;
        }

        try {
            IdempotencyKey::create([
                'reference' => $marker,
                'route' => substr($route, 0, 120),
                'outcome' => 'processed',
            ]);
        } catch (QueryException $e) {
            // Lost a race with a concurrent identical send: the other attempt
            // owns this marker, so this one is the repeat.
            if ($this->markerSeen($marker, $route)) {
                Log::info('Duplicate mail suppressed', ['mail' => static::class]);

                return $this;
            }

            throw $e;
        }

        try {
            return parent::send($mailer);
        } catch (\Throwable $e) {
            // Fail = no marker (spec §6 edge): nothing was sent, so a retry
            // must be treated as fresh, never as "already sent".
            IdempotencyKey::where('reference', $marker)->where('route', $route)->delete();

            throw $e;
        }
    }

    private function markerSeen(string $marker, string $route): bool
    {
        return IdempotencyKey::where('reference', $marker)->where('route', $route)->exists();
    }
}
