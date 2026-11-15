# BEACON OSAS Portal — Security Assessment & Remediation Log

Date: 2026-11-15 · Scope: pre-deployment security review of the Laravel 11
portal on Render + Neon · Plan approved as "Fixes 1–5 + tests".

## 1. Fixes applied

| # | Finding | Fix | Commit | Verification |
|---|---------|-----|--------|--------------|
| 1 | **Privilege escalation**: a Coordinator could promote any Student (or fellow Coordinator) to a full Administrator account via `PUT /admin/users/{publicId}` — `assignableRoles(Coordinator)` wrongly included `Administrator` while `canCreateRole` did not, and the assignability gate ran on every update. | `UserPolicy::assignableRoles()` now returns `['Student']` for Coordinator; `allows('change_role')` blocks coordinators on any non-Student target; `UserController::update` gates only when the role actually changes; the locked role field round-trips the target's current role instead of `roles[0]` (which silently demoted on save). | `df73566` | `scripts/policy-probe.php` — 32/32 PASS |
| 2 | **Missing security headers** on every response; `X-Powered-By` version disclosure. | `app/Http/Middleware/SecurityHeaders.php` appended globally: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`, CSP `frame-ancestors 'self'`, HSTS (`max-age=31536000; includeSubDomains`) only when `isSecure()`, `X-Powered-By` stripped. | `6d3fbc0` | HTTP-kernel smoke test — 24/24 PASS (http/https × 200/404) |
| 3 | **No flood protection on sign-in**; failed-attempt counting keyed on email+IP, so rotating the source address (or spoofing `X-Forwarded-For`, which `trustProxies('*')` trusts) reset the counter while guessing one password. | `POST /login` carries `throttle:20,600` (20 req/10 min per IP as a flood net); `LoginThrottle` now counts failures **per account** (email only) with 5 failures / 10 min lockout; `ThrottleRequests` keys include the HTTP method so GET views never consume the POST budget. | `1950877` | `scripts/service-write-probe.php` throttle section — 11/11 PASS incl. net-zero and cross-account isolation |
| 4 | **8-character password minimum** on all password-setting forms. | `min:12` server-side in `RegisterStudentRequest`, `ResetPasswordRequest`, `StoreUserRequest`, `UpdateUserRequest`, `ProfileController` (×2); views updated (help text, placeholders, `minlength="12"`). | `4a45b3f` | `scripts/password-policy-probe.php` — 12/12 PASS (8/11 rejected, 12 accepted × 4 forms) |
| 5 | This document. | Findings, fixes, deferred items and probe inventory recorded here. | (this commit) | — |

### Bugs found and fixed along the way

- **`User::create` public_id collision** (`bf8f896`): seeded accounts keep
  legacy pinned ids from `seed.json` (the administrator holds `USR-1009`), so
  creating the 6th user (row id 9 → `USR-1009`) collided on
  `users_public_id_unique` and 500'd — for admin-created accounts and student
  self-registration alike. The candidate now walks upward until free, matching
  `BaseModel::nextPublicId()`.
- **`scripts/service-write-probe.php` rot** (`13342e8`): the probe still
  assumed the demo-era seed (admin at `User::find(9)`, hardcoded `USR-1002`,
  a seeded orientation module) and had not run to completion since the demo
  data was removed. Fixtures are now resolved by role and the quiz lifecycle
  creates a disposable module. Full run: **ALL SERVICE WRITE CHECKS PASSED**,
  net-zero on every table.

## 2. Verification probes (re-runnable)

```
php scripts/policy-probe.php            # 32 authorization checks (no DB)
php scripts/password-policy-probe.php   # 12 password-rule checks (no writes)
php scripts/service-write-probe.php     # full write-path probe; needs a SEEDED DB
php scripts/login-flow-probe.php        # 14 sign-in / lockout / throttle checks; SEEDED scratch DB
```

`service-write-probe` must run against a scratch database
(`$env:DB_DATABASE='osas_beacon'` in the same shell), never the live `neondb`.
`login-flow-probe` has the same requirement and refuses to start without the
guard (it records failed logins and exhausts one throttle key by design).

Post-deploy live checks: `curl -I https://osas-beacon.onrender.com/login` —
verified 2026-11-15: CSP, Referrer-Policy, HSTS, nosniff and X-Frame-Options
all present, and no `x-powered-by` once `expose_php=Off` (`ea91145`) was
deployed — plus a sign-in per role. The sign-in, lockout and throttle flows
are covered in-kernel by `login-flow-probe`; an optional live 11-POST
throttle probe remains (run it last: it locks the caller's IP on
`POST /login` for 10 minutes — expect 5× "Incorrect" 302,
2× "Too many failed" 302, then 429).

## 3. Accepted risks & deferred items

- **Laravel 11 EOL + 5 advisories** — triaged as mostly non-exploitable in
  this deployment (no user-controlled deserialization, mailer is `log` so the
  CRLF email-rule injection is moot). Framework upgrade to Laravel 12/13
  deferred by decision; revisit before handling real personal data.
- **`trustProxies('*')`** — Render's TLS terminator is trusted, which means
  `X-Forwarded-For` is client-controllable. IP-based throttling is therefore
  only a flood net (finding 3); the real brute-force gate is per-account and
  unaffected. Not changed: Render does not publish a stable proxy IP range to
  pin.
- **No full Content-Security-Policy** — the portal uses inline scripts and
  styles (rich-text editor, carousels); `frame-ancestors` provides the
  clickjacking protection without breaking execution. A full CSP needs the
  inline code extracted first.
- **`Beacon@2026` (11 chars) is below the new UI policy** — seeded accounts
  keep logging in (the rule applies only when *setting* a password); the seed
  was deliberately left unchanged. Rotate to a 12+ character seed password
  when the demo accounts are next reset.
- **Render free-plan cold starts** — a keep-alive ping was offered and is
  still undecided; ~15 min idle sleeps the service.
- Cosmetic, known: mojibake ` Â · ` in some page subtitles; `SESSION_SECURE`
  in `render.yaml` is a no-op (Render terminates TLS at the proxy and
  `trustProxies('*')` makes `isSecure()` true anyway).
