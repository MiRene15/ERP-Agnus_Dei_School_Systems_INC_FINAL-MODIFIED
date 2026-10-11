<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Student;
use App\Models\User;

/**
 * Institution email provisioning (spec:
 * enrollment-payment-gating-payment-access.md).
 *
 * One active address per student: firstname.lastname@agnusdei.edu.ph,
 * numbered suffix on collision (juan.delacruz2@...), family told
 * plainly in the notice. Runs inside full approval, after the commit
 * the notice email is sent to the student's own email from child 1.
 */
class InstitutionAddressService
{
    public const DOMAIN = 'agnusdei.edu.ph';

    /**
     * @return array{address: ?string, suffixed: bool}
     */
    public static function provision(Student $student, string $actorName): array
    {
        $user = $student->user;

        if ($user === null) {
            log_activity($student, 'Institution Email Skipped', $actorName . ' could not provision an institution address: no login account linked to student #' . $student->id . '.');

            return ['address' => null, 'suffixed' => false];
        }

        $clean = static fn (?string $raw): string => (string) preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $raw)));
        $first = $clean($student->first_name);
        $last = $clean($student->last_name);
        $base = ($first !== '' ? $first : 'student') . '.' . ($last !== '' ? $last : (string) $student->id);

        $candidate = $base . '@' . self::DOMAIN;
        $suffix = 1;
        $suffixed = false;
        while (User::where('email', $candidate)->where('id', '!=', $user->id)->exists()) {
            $suffix++;
            $candidate = $base . $suffix . '@' . self::DOMAIN;
            $suffixed = true;
        }

        if ($user->email !== $candidate) {
            $user->email = $candidate;
            $user->save();
        }

        log_activity($student, 'Institution Email Provisioned', $actorName . ' provisioned ' . $candidate . ' for ' . $student->first_name . ' ' . $student->last_name . ($suffixed ? ' (numbered — name already taken).' : '.'));

        return ['address' => $candidate, 'suffixed' => $suffixed];
    }
}
