# Production Readiness Audit

Audited against `RUBRIC.md`. Each criterion is marked pass ✅ or fail ❌ with a finding.

---

## 1. Environment Configuration — 3/8

- ✅ `.env.example` exists and is committed
- ❌ `.env.example` includes descriptions for non-obvious variables — variables are listed but none have inline comments explaining what value is expected in production vs local (e.g. `SESSION_DRIVER=database` will fail without a DB table configured; `APP_DEBUG=true` is dangerous in production with no warning)
- ✅ `.env` is in `.gitignore`
- ❌ Required variables without defaults fail loudly at startup — `APP_KEY` is blank in `.env.example`. A fresh clone without `php artisan key:generate` will boot silently and then fail mid-request with a cryptic encryption error, not a clear startup error
- ❌ `APP_ENV` and `APP_DEBUG` documented for production — `.env.example` ships with `APP_DEBUG=true`. No comment warns that this must be `false` in production or that it exposes stack traces to clients
- ❌ `APP_KEY` generation is documented in setup steps — the README contains no project-specific setup steps at all (see Section 5)
- ❌ Database connection variables documented per environment — `.env.example` sets `DB_CONNECTION=sqlite` with no note that SQLite is not suitable for production. A developer copying this to a production server would silently use a file-based database
- ✅ No secrets, credentials, or tokens are hardcoded in the codebase

---

## 2. CI Pipeline — 0/9

- ❌ CI configuration file exists — no `.github/workflows/` directory in the project
- ❌ CI runs on every push to master and on every pull request
- ❌ CI installs dependencies
- ❌ CI runs the full test suite
- ❌ CI fails the build if any test fails
- ❌ CI runs on a clean environment
- ❌ CI uses the correct PHP version (^8.2 per `composer.json`)
- ❌ CI sets up a test database
- ❌ Pipeline result is visible

**This is a hard blocker.** 27 passing tests exist but nothing runs them automatically. A bad commit can be pushed to master right now with zero automated checks.

---

## 3. Logging — 6/7

- ✅ Default log channel is `stack` → `single`, appropriate for development and functional for production
- ✅ `LOG_LEVEL` controls verbosity via environment variable
- ✅ Unhandled exceptions are logged with full stack traces (Laravel default behaviour)
- ✅ No request-body logging middleware added — passwords and tokens in POST payloads are not written to logs
- ✅ No custom code logs PII
- ❌ Log output format is plain text, not structured JSON — in production, log aggregators (Datadog, Papertrail, CloudWatch) parse structured JSON; the current `single` channel writes unformatted lines that are harder to query and alert on
- ✅ `*.log` is in `.gitignore` — log files are not committed

---

## 4. Security Headers — 1/7

- ❌ `X-Content-Type-Options: nosniff` — not set on API responses. Laravel's web middleware stack includes some protections but the API middleware group does not apply them by default
- ❌ `X-Frame-Options` — not set
- ❌ `Strict-Transport-Security` — not documented for production HTTPS deployments
- ❌ `Content-Security-Policy` — not considered or deferred with a reason
- ❌ `X-Powered-By` / `Server` headers — PHP exposes `X-Powered-By: PHP/x.x` by default unless `expose_php = Off` is set in `php.ini`. This is not documented or suppressed at the application level
- ❌ CORS policy — `config/cors.php` does not exist. The `HandleCors` middleware is in the pipeline via the framework but its default configuration allows all origins (`*`). This is not acceptable for a production API
- ✅ Authentication tokens are not returned in response headers unintentionally

**This section is a hard blocker.** No security headers are set at the application level and CORS is wide open.

---

## 5. README — 1/9

- ✅ `README.md` exists at the repository root
- ❌ README describes this project — the file is the default Laravel boilerplate. It describes Laravel the framework, links to Laracasts, and lists Laravel's premium partners. It says nothing about this exercise library API
- ❌ README lists system requirements for this project — no PHP version, no Composer version, no SQLite requirement mentioned
- ❌ README has a step-by-step local setup section — no setup instructions exist
- ❌ README documents how to run the test suite — `php artisan test` is not mentioned
- ❌ README documents API endpoints — no endpoint list or reference
- ❌ README documents authentication — no explanation of Sanctum tokens or how to obtain one for testing
- ❌ README documents environment variables that must be set — `APP_KEY`, `DB_CONNECTION`, and others are not mentioned
- ❌ README does not contain outdated placeholder content — the entire file is placeholder content

**This is a hard blocker.** A developer cloning this repository has no information about what the project is, how to set it up, or how to use it.

---

## 6. API Design and Contracts — 7/7

- ✅ All endpoints return consistent JSON shapes via `ExerciseResource`
- ✅ Error responses follow a consistent format (`message`, `errors` keys via Laravel's default validation response)
- ✅ HTTP status codes are semantically correct — 201 create, 204 delete, 401 unauthenticated, 404 missing/deleted, 422 validation failure
- ✅ Validation errors return field-level detail, not just a generic message
- ✅ Requests to non-existent endpoints return 404, not 500
- ✅ Unauthenticated requests return `{"message":"Unauthenticated."}` with 401 — not an HTML redirect (fixed in this project via `shouldRenderJsonWhen`)
- ✅ Soft-deleted resources return 404 on subsequent fetch

---

## 7. Database and Migrations — 5/6

- ✅ All schema changes are managed through migrations
- ✅ `php artisan migrate` runs cleanly on a fresh database
- ✅ Migrations have `down()` methods with `Schema::dropIfExists()`
- ❌ Production database driver is not documented — `.env.example` defaults to SQLite with no warning. SQLite is a single-file database unsuitable for concurrent production traffic. A production deployment requires MySQL or PostgreSQL, but this is not stated anywhere
- ✅ Soft deletes are used on the `exercises` table (`deleted_at` column)
- ✅ Unique constraints are enforced at the database level on both `name` and `slug` columns, not only in application validation

---

## 8. Dependency Management — 3/4

- ✅ `composer.lock` is committed
- ✅ `composer audit` passes — no known security vulnerability advisories
- ✅ All dev-only packages (`phpunit`, `faker`, `pint`, `sail`, `mockery`, `collision`, `pail`) are correctly in `require-dev`
- ❌ PHP version constraint cannot be verified against CI — `composer.json` requires `^8.2` but no CI pipeline exists to enforce this

---

## Score

| Section | Criteria | Passed | Failed | Score |
|---|---|---|---|---|
| 1. Environment Configuration | 8 | 3 | 5 | 3/8 |
| 2. CI Pipeline | 9 | 0 | 9 | 0/9 |
| 3. Logging | 7 | 6 | 1 | 6/7 |
| 4. Security Headers | 7 | 1 | 6 | 1/7 |
| 5. README | 9 | 1 | 8 | 1/9 |
| 6. API Design and Contracts | 7 | 7 | 0 | 7/7 |
| 7. Database and Migrations | 6 | 5 | 1 | 5/6 |
| 8. Dependency Management | 4 | 3 | 1 | 3/4 |
| **Total** | **57** | **26** | **31** | **26/57 (46%)** |

**Verdict: Not production-ready.**

The passing threshold is 45/57. The project scores 26/57. Three sections are hard blockers regardless of total score (sections 1, 2, and 4 each contain critical failures).

---

## Prioritised fix list

These are ordered by blast radius — what causes the most damage if it ships broken.

| Priority | Fix | Section |
|---|---|---|
| 1 | Add a GitHub Actions CI workflow that installs deps and runs tests on every push/PR | 2 |
| 2 | Replace the boilerplate README with project-specific setup, endpoint, and auth documentation | 5 |
| 3 | Add security headers middleware to the API routes (`nosniff`, `X-Frame-Options`, CORS locked to specific origins) | 4 |
| 4 | Set `APP_DEBUG=false` in `.env.example` and add inline comments warning about production values | 1 |
| 5 | Document that SQLite is for local development only; add guidance on production database setup | 1 + 7 |
| 6 | Add a `LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter` note for production structured logging | 3 |
| 7 | Document `expose_php = Off` in `php.ini` or suppress `X-Powered-By` at the server/middleware level | 4 |
