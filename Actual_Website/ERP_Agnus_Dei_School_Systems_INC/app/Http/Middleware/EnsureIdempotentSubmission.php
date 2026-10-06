<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

// Safe Actions — One Submission (spec: safe-actions-one-submission.md).
//
// A repeated submission of the SAME reference changes nothing and is
// reported as already done; a fresh reference always processes. Requests
// without a reference are treated as new work and never blocked.
class EnsureIdempotentSubmission
{
    /** Route names with a plain-language noun for the repeat notice. */
    private const REPEAT_NOUNS = [
        'cashier.payment.process' => 'payment',
        'cashier.discounts.apply' => 'discount',
        'cashier.refunds.release' => 'refund',
        'cashier.payments.void' => 'payment',
        'cashier.graduation-fees.toggle-paid' => 'fee',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Reads change nothing, so there is nothing to protect.
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        // Auth flows are out of scope (spec §7) and must never be altered.
        // Path-based because several auth POSTs (login, confirm-password) are unnamed.
        if ($request->is('login', 'logout', 'forgot-password*', 'reset-password*', 'confirm-password*', 'verify-email*', 'email/verification-notification', 'password', 'force-change-password')) {
            return $next($request);
        }

        $rawReference = $request->input('_idempotency_key', '');

        // No (or malformed) reference: treated as new work, never blocked.
        // Strict string check first — a crafted array input must pass through,
        // never explode the cast.
        if (! is_string($rawReference) || $rawReference === '' || ! Str::isUuid($rawReference)) {
            return $next($request);
        }

        $reference = $rawReference;

        $route = (string) ($request->route()?->getName() ?? $request->path());

        if (IdempotencyKey::where('reference', $reference)->exists()) {
            return $this->alreadySaved($request, $route);
        }

        try {
            IdempotencyKey::create([
                'reference' => $reference,
                'route' => substr($route, 0, 120),
                'outcome' => 'processed',
            ]);
        } catch (QueryException $e) {
            // Lost the race with a concurrent identical submission: the other
            // request owns this reference, so this one is the repeat.
            if (IdempotencyKey::where('reference', $reference)->exists()) {
                return $this->alreadySaved($request, $route);
            }

            throw $e;
        }

        try {
            return $next($request);
        } catch (\Throwable $e) {
            // Fail = no marker (spec §6 edge): the work did not happen, so a
            // retry must be treated as fresh, never as "already saved".
            IdempotencyKey::where('reference', $reference)->delete();

            throw $e;
        }
    }

    private function alreadySaved(Request $request, string $route): Response
    {
        $noun = self::REPEAT_NOUNS[$route] ?? null;
        $message = $noun !== null
            ? "That {$noun} was already saved — nothing was recorded twice."
            : 'Already saved — nothing was recorded twice.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'duplicate' => true]);
        }

        return redirect()->back()->with('success', $message);
    }
}
