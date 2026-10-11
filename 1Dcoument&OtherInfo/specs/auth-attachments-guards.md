# Spec: Auth Attachments Guards

- **Status**: Approved
- **Created**: 2026-10-11
- **Approved by**: user on 2026-10-11

## 1. Why We Need This

Logins kick staff out mid-rush with no refresh story, some private screens may lean on hidden menus instead of real checks, teacher buttons still risk double-taps, and every upload invents its own file rules. This change hardens all four: sliding sessions plus rotating JSON refresh tokens, server-side permission checks on every page and action, idempotency on every write button, and one attachment rule with JSON sidecars — so expired logins, role misuse, double writes, and bad files stop at the door.

## 2. Who Is Affected

- **IT Admin (primary)** — owns sessions, permissions, files; sees denial and theft events.
- **All staff** — stable logins through rush days; buttons that can't double-fire.
- **Teachers** — grade/attendance buttons explicitly guarded against double-taps and brownouts.
- **Families** — proof uploads with progress and plain errors; JSON-structured reliability.
- **API consumers (first-party)** — short access + rotating refresh JSON tokens.

## 3. Business Flow: Today vs After

- **As-is**: web sessions expire on a fixed clock; Sanctum token API exists with no refresh flow; idempotency keys + press protection cover inquiry, admission, payments, and grade saves; per-person throttles cover inquiry/search; uploads validated per-feature (admission requirements with filename/mime/size + verify); menus hide what roles shouldn't see.
- **To-be**: sign-in → session stays alive through active use (30-min idle timeout), sensitive actions re-check live → API gets short access + rotating JSON refresh → every private page AND action verifies role server-side → every write button sends its idempotency key (double-taps collapse to one write) → uploads pass one rule (allowlist, caps, JSON sidecar per file) → minors' files role-gated, denials audited.
- **Preserved**: existing idempotency keys, mail duplicate markers, throttle behavior, admission verify flow, who-may-see-which-list, audit trails.
- **Exceptions**: theft response (kill family + sign out everywhere + IT event) and role-revocation (instant denial) work the same in rush and quiet hours.

## 4. How It Should Work

1. Staff signs in → works through the day without mid-rush logout; 30+ idle minutes → "Session expired — sign in again."
2. API client signs in → short access token + JSON refresh token → refresh rotates the pair; reuse of an old refresh kills the family and signs all sessions out with the safety message.
3. Any private URL opened by the wrong role → "You don't have access — ask your admin," no data leaked, denial recorded for IT.
4. Any write button tapped twice (or retried after brownout) → one write; the second resolves to "Already saved — showing the result."
5. Uploads (admission docs, grade-edit proof, announcements): pick file → progress → validated (PDF/JPG/PNG, 5MB per doc / 10MB per proof bundle) → JSON sidecar recorded (name, mime, size, owner, context) → attached once, referenced everywhere.
6. Profile shows Active Sessions + Sign Out Everywhere.

## 5. Look & Feel (UX)

- Where it lives: sign-in (unchanged) + Profile (new sessions list + sign-out-everywhere) + one shared upload component + button saving states. No other new screens. One primary action each: `Sign In`, `Sign Out Everywhere`, `Upload`, `Save`.
- What the user sees first: their work, uninterrupted; sessions and sidecars are invisible until needed.
- Key states: saving ("Saving…" disabled button) → saved ("Already saved" on retry); upload progress → named error ("Birth certificate must be PDF/JPG under 5MB"); expired ("Session expired — sign in again"); safety sign-out ("Signed out for safety — sign in again"); denied ("You don't have access — ask your admin").
- Plain labels throughout; same light/dark look; upload errors name the file, the rule, and the fix.

## 6. Business Rules

### Must always be true

- Sessions refresh through active use; 30-minute idle always ends the session.
- Refresh rotation: new pair issued per use; any reuse kills the whole family + signs out everywhere + records an IT event.
- Permission verified at page AND action; menu hiding is decoration only.
- Every write button carries its idempotency key; retries collapse to the first write.
- Uploads: allowlist PDF/JPG/PNG (+ JSON sidecars), 5MB per doc / 10MB per proof bundle / per-request caps; stored once, never executed; partial uploads never attach.
- Minors' files visible only to owning roles; every denial audited without logging file contents or search words.

### Must never happen

- Two live sessions sharing one refresh token after rotation.
- A write succeeding from a denied role, or data leaking through a hidden-menu URL.
- An executable (or disguised) file stored, or a partial file attached to a record.
- Throttles or guards blocking a legitimate payment, grade save, or admission submit at rush.
- File contents or search words written to routine logs.

### Edge cases and what happens then

- Refresh reused (theft or cloned tab race) → family killed, safety sign-out everywhere, IT event with time/IP.
- Shared computer → idle timeout + user-driven Sign Out Everywhere; sessions list names device/time.
- Brownout double-tap → single write; retry shows the first result.
- Upload interrupted → no attachment; resume or re-pick; the record never references a partial file.
- Role revoked mid-session → next action denied at once; the page itself also gates on load.
- Auth/rate-limit incident → IT sees counts (denials, kills, slow paths) without PII in routine records.

## 7. Out of Scope

- Single sign-on, biometrics, partner API tokens (first-party only in v1).
- Third-party malware-scan service (mime/size/extension gating in v1).
- Upload resume-across-devices (noted for later).
- Changing any fee, grade, approval, receipt, or audit logic.

## 8. Success Checks

- [ ] Full workday active use → no mid-rush logout; 30+ idle minutes → sign-in required.
- [ ] API refresh rotates the pair; the old refresh is dead on arrival.
- [ ] Reused refresh → family killed, safety message, IT event recorded.
- [ ] Wrong-role private URL → denied with no data, denial audited.
- [ ] Teacher double-taps Save → exactly one write with "Already saved" on retry.
- [ ] EXE/script upload → blocked naming the rule; 6MB doc → blocked naming 5MB.
- [ ] Interrupted upload → nothing attached to the record.

## 9. Open Questions (if any)

None — sliding sessions (30-min idle), rotating JSON refresh, page+action checks, button idempotency, allowlist + caps, JSON sidecars all confirmed 2026-10-11. Literal `priv` folder: none exists — interpreted as server-side enforcement; correct if you meant otherwise.

## 10. Technical Notes (for developers)

*Plain-language pointer only — the source of truth is the code and this appendix.*
- Affected screens/pages: sign-in, Profile (sessions + sign-out-everywhere), shared upload component (admission apply, grade-edit proof, announcements), all write buttons (teacher grade/attendance explicitly), all private routes.
- Likely areas of the codebase (from read-only inspection):
  - Sessions: session lifetime + sliding refresh on activity with 30-min idle end; sensitive actions re-check permission live; role-revocation takes effect on next action/page load.
  - Tokens: Sanctum (`routes/api.php`, `auth:sanctum` + `throttle:api`) — add short-lived access + rotating JSON refresh pair, reuse-kills-family, safety sign-out, IT event logging.
  - Permissions: every private route/action verifies role server-side (menu hiding stays cosmetic); denials audited without data leak; no new permission model in v1 — enforce the existing one.
  - Buttons: extend `EnsureIdempotentSubmission` (web-global) + `_idempotency_key` + `idempotency_keys` table + mail markers to remaining write buttons (teacher save/post paths explicitly, plus child-spec bulk actions); disable-while-saving + "Already saved" resolution; never block legitimate rush writes.
  - Uploads: converge admission-requirements validation (filename/mime/size + verify) into one shared rule (allowlist PDF/JPG/PNG, 5MB doc / 10MB proof caps, never-execute storage) + JSON sidecar per file (name, mime, size, owner, context); partials never attach; minors' files role-gated.
- Data/records touched: sessions, token families, denial/theft audit events, attachments + JSON sidecars; no fee/grade/enrollment writes beyond the guarded actions themselves.
- Roles/permissions involved: IT admin (owner), all staff (sessions/buttons), families (uploads), teachers (explicit button coverage); enforcement-only, no model change. Relatives: `safe-actions-*.md`, `search-rate-limit-tracking.md` (both Implemented — this spec extends coverage, not behavior).
- Peak-load note: enrollment/fee/grading peaks — refresh/rotation/limit costs kept off the queue-read path; guards collapse duplicates instead of queuing them.

## 11. Approval

> Approved by user on 2026-10-11.
