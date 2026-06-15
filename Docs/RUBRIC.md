# Production Readiness Rubric

## What "production-ready" means

A production-ready API can be cloned by a new developer, running in minutes, and shipped to real users with confidence. It fails loudly when misconfigured, protects user data by default, is observable when things go wrong, and does not require tribal knowledge to operate or maintain.

This rubric defines the minimum bar. Each criterion is a checkbox. Anything unchecked is a known gap that must be documented, accepted, or fixed before shipping.

---

## 1. Environment Configuration

A misconfigured environment should fail loudly at boot, not silently at runtime.

- [ ] `.env.example` exists and documents every required variable
- [ ] `.env.example` includes descriptions or example values for non-obvious variables
- [ ] `.env` is listed in `.gitignore` and never committed
- [ ] Required variables without defaults cause the app to fail at startup, not mid-request
- [ ] `APP_ENV` and `APP_DEBUG` are explicitly documented — debug mode must be `false` in production
- [ ] `APP_KEY` generation is documented in setup steps
- [ ] Database connection variables are documented with expected values per environment (local vs production)
- [ ] No secrets, credentials, or tokens are hardcoded anywhere in the codebase

---

## 2. CI Pipeline

Every commit and every pull request must be verified automatically. A green pipeline is the minimum bar for merging.

- [ ] A CI configuration file exists (e.g. `.github/workflows/`)
- [ ] CI runs on every push to `main`/`master` and on every pull request
- [ ] CI installs dependencies (`composer install`)
- [ ] CI runs the full test suite (`php artisan test`)
- [ ] CI fails the build if any test fails
- [ ] CI runs on a clean environment (no leftover state from previous runs)
- [ ] CI uses the correct PHP version (matches `composer.json` requirement)
- [ ] CI sets up a test database (SQLite in-memory or equivalent)
- [ ] Pipeline result is visible without logging in (public status badge or public repo)

---

## 3. Logging

Logs must be useful for debugging production issues without leaking sensitive data.

- [ ] Default log channel is set and appropriate for production (e.g. `stack`, not just `stderr`)
- [ ] Log level is configurable via environment variable (`LOG_LEVEL`)
- [ ] Errors and exceptions are logged with stack traces
- [ ] Request payloads containing passwords or tokens are never logged
- [ ] Personally identifiable information (PII) is not written to logs
- [ ] Log output format is parseable (structured JSON preferred for production)
- [ ] Log files are excluded from version control (`.gitignore` covers `storage/logs/*.log`)

---

## 4. Security Headers

An API response must include headers that protect clients and reduce the attack surface.

- [ ] `X-Content-Type-Options: nosniff` is set on all responses
- [ ] `X-Frame-Options: DENY` or `SAMEORIGIN` is set on all responses
- [ ] `Strict-Transport-Security` (HSTS) is documented for production HTTPS deployments
- [ ] `Content-Security-Policy` is considered and either set or explicitly deferred
- [ ] `X-Powered-By` / `Server` headers that expose implementation details are removed or suppressed
- [ ] CORS policy is explicitly configured — wildcard `*` origins are not used in production
- [ ] Authentication tokens are never returned in response headers unintentionally

---

## 5. README

A developer who has never seen the project must be able to clone and run it using only the README.

- [ ] README exists at the repository root
- [ ] README states what the project is and what it does (one paragraph)
- [ ] README lists all system requirements (PHP version, Composer version, etc.)
- [ ] README has a step-by-step local setup section that works on a fresh clone
- [ ] README documents how to run the test suite
- [ ] README documents the available API endpoints (or links to where they are documented)
- [ ] README documents authentication — how to get a token, how to use it
- [ ] README documents environment variables that must be set before the app will run
- [ ] README does not contain outdated or placeholder content

---

## 6. API Design and Contracts

The API must behave consistently and predictably for clients.

- [ ] All endpoints return consistent JSON response shapes
- [ ] Error responses follow a consistent format (message, errors fields)
- [ ] HTTP status codes are semantically correct (201 for create, 204 for delete, 422 for validation, 401 for auth)
- [ ] Validation errors return field-level detail, not just a generic message
- [ ] Endpoints that do not exist return 404, not 500
- [ ] Unauthenticated requests return 401 JSON, not an HTML redirect page
- [ ] Deleted resources return 404 on subsequent fetch

---

## 7. Database and Migrations

The database layer must be safe to deploy and safe to roll back.

- [ ] All schema changes are managed through migrations (no manual SQL)
- [ ] Migrations run without errors on a fresh database (`php artisan migrate`)
- [ ] Migrations can be rolled back without data loss where possible
- [ ] Production database driver is documented (SQLite is not acceptable for production)
- [ ] Soft deletes are used where data must be preserved after deletion
- [ ] Unique constraints are enforced at the database level, not only in application code

---

## 8. Dependency Management

Dependencies must be pinned and audited.

- [ ] `composer.lock` is committed to version control
- [ ] No known security vulnerabilities in dependencies (`composer audit` passes)
- [ ] Dev-only dependencies are in `require-dev`, not `require`
- [ ] PHP version constraint in `composer.json` matches the version used in CI and production

---

## Scoring

| Section | Criteria | Passed | Failed | Score |
|---|---|---|---|---|
| 1. Environment Configuration | 8 | — | — | —/8 |
| 2. CI Pipeline | 9 | — | — | —/9 |
| 3. Logging | 7 | — | — | —/7 |
| 4. Security Headers | 7 | — | — | —/7 |
| 5. README | 9 | — | — | —/9 |
| 6. API Design and Contracts | 7 | — | — | —/7 |
| 7. Database and Migrations | 6 | — | — | —/6 |
| 8. Dependency Management | 4 | — | — | —/4 |
| **Total** | **57** | — | — | **—/57** |

A score below 45/57 (≈79%) means the project is not production-ready. Every failed criterion in sections 1, 2, and 4 is a blocker regardless of total score.
