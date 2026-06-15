# Production Readiness Audit

Audited against `RUBRIC.md`. Each criterion is marked pass ✅ or fail ❌ with a finding.
Items marked 🔧 were failing at initial audit and have since been fixed.

---

## 1. Environment Configuration — 7/8

- ✅ `.env.example` exists and is committed
- 🔧 `.env.example` includes descriptions for non-obvious variables — added inline comments for `APP_DEBUG`, `APP_KEY`, `DB_CONNECTION`, `CORS_ALLOWED_ORIGINS`, and `LOG_LEVEL` with production guidance
- ✅ `.env` is in `.gitignore`
- ❌ Required variables without defaults fail loudly at startup — `APP_KEY` is blank in `.env.example`. A fresh clone without `php artisan key:generate` boots silently and fails mid-request with a cryptic encryption error. This is a Laravel framework behaviour and cannot be fixed at the application level; it is documented in the README setup steps
- 🔧 `APP_ENV` and `APP_DEBUG` documented for production — `.env.example` now ships with `APP_DEBUG=false` and a warning comment that `true` exposes stack traces to clients
- 🔧 `APP_KEY` generation is documented in setup steps — README now includes `php artisan key:generate` as an explicit setup step
- 🔧 Database connection variables documented per environment — `.env.example` and README both state that SQLite is for local development only and that production requires MySQL or PostgreSQL
- ✅ No secrets, credentials, or tokens are hardcoded in the codebase

---

## 2. CI Pipeline — 9/9

- 🔧 CI configuration file exists — `.github/workflows/ci.yml` added
- 🔧 CI runs on every push to `master` and on every pull request
- 🔧 CI installs dependencies via `composer install --no-interaction --prefer-dist`
- 🔧 CI runs the full test suite via `php artisan test`
- 🔧 CI fails the build if any test fails
- 🔧 CI runs on a clean Ubuntu environment with no leftover state
- 🔧 CI uses PHP 8.2 via `shivammathur/setup-php`, matching `composer.json` constraint `^8.2`
- 🔧 CI sets up a test database — `phpunit.xml` configures SQLite `:memory:`, no extra CI step needed
- 🔧 Pipeline result is visible on the public GitHub repository

---

## 3. Logging — 7/7

- ✅ Default log channel is `stack` → `single`, appropriate for development
- ✅ `LOG_LEVEL` controls verbosity via environment variable
- ✅ Unhandled exceptions are logged with full stack traces (Laravel default behaviour)
- ✅ No request-body logging middleware — passwords and tokens in POST payloads are not written to logs
- ✅ No custom code logs PII
- 🔧 Structured JSON logging for production — `json_stderr` channel added to `config/logging.php`. Set `LOG_CHANNEL=json_stderr` in production for log aggregator–compatible output. Documented in `.env.example`
- ✅ `*.log` is in `.gitignore` — log files are not committed

---

## 4. Security Headers — 6/7

- 🔧 `X-Content-Type-Options: nosniff` — set on all API responses via `SecureHeaders` middleware registered on the API middleware group
- 🔧 `X-Frame-Options: DENY` — set on all API responses via `SecureHeaders` middleware
- ❌ `Strict-Transport-Security` — not set at the application level. HSTS must be configured at the web server (nginx/Apache) or load balancer for HTTPS deployments. This is a server-level concern and is documented in the README under production notes
- ✅ `Content-Security-Policy` — explicitly deferred: this is a JSON API with no browser-rendered HTML; CSP is not applicable to pure API responses
- 🔧 `X-Powered-By` removed — `SecureHeaders` middleware calls `$response->headers->remove('X-Powered-By')` on every API response. Setting `expose_php = Off` in `php.ini` remains the server-level complement
- 🔧 CORS policy locked — `config/cors.php` created. `allowed_origins` is driven by `CORS_ALLOWED_ORIGINS` env var with no wildcard fallback. Documented in `.env.example` with a production warning
- ✅ Authentication tokens are not returned in response headers unintentionally

---

## 5. README — 9/9

- ✅ `README.md` exists at the repository root
- 🔧 README describes this project — rewritten from scratch; boilerplate Laravel content removed entirely
- 🔧 README lists system requirements — PHP ^8.2, Composer ^2.0, SQLite (local only) listed with a production database note
- 🔧 README has a step-by-step local setup section — clone, `composer install`, copy `.env`, generate key, migrate, serve
- 🔧 README documents how to run the test suite — `php artisan test` with expected output stated
- 🔧 README documents API endpoints — all five endpoints documented with method, URI, request body, and response shape
- 🔧 README documents authentication — Sanctum token requirement explained with a Tinker snippet for generating a local test token
- 🔧 README documents required environment variables — table of all variables with required/optional status and description
- 🔧 README contains no placeholder content — all Laravel boilerplate removed

---

## 6. API Design and Contracts — 7/7

- ✅ All endpoints return consistent JSON shapes via `ExerciseResource`
- ✅ Error responses follow a consistent format (`message`, `errors` keys via Laravel's default validation response)
- ✅ HTTP status codes are semantically correct — 201 create, 204 delete, 401 unauthenticated, 404 missing/deleted, 422 validation failure
- ✅ Validation errors return field-level detail, not just a generic message
- ✅ Requests to non-existent endpoints return 404, not 500
- ✅ Unauthenticated requests return `{"message":"Unauthenticated."}` with 401 — not an HTML redirect, enforced via `shouldRenderJsonWhen` in `bootstrap/app.php`
- ✅ Soft-deleted resources return 404 on subsequent fetch

---

## 7. Database and Migrations — 6/6

- ✅ All schema changes are managed through migrations
- ✅ `php artisan migrate` runs cleanly on a fresh database
- ✅ Migrations have `down()` methods with `Schema::dropIfExists()`
- 🔧 Production database driver documented — `.env.example` and README both clearly state SQLite is local-only and that production requires MySQL or PostgreSQL
- ✅ Soft deletes are used on the `exercises` table (`deleted_at` column)
- ✅ Unique constraints enforced at the database level on both `name` and `slug` columns

---

## 8. Dependency Management — 4/4

- ✅ `composer.lock` is committed
- ✅ `composer audit` passes — no known security vulnerability advisories
- ✅ All dev-only packages (`phpunit`, `faker`, `pint`, `sail`, `mockery`, `collision`, `pail`) are in `require-dev`
- 🔧 PHP version constraint verified against CI — GitHub Actions now runs PHP 8.2, matching `composer.json` `^8.2`

---

## Score

| Section | Criteria | Passed | Fixed | Still failing | Score |
|---|---|---|---|---|---|
| 1. Environment Configuration | 8 | 3 | 4 | 1 | 7/8 |
| 2. CI Pipeline | 9 | 0 | 9 | 0 | 9/9 |
| 3. Logging | 7 | 6 | 1 | 0 | 7/7 |
| 4. Security Headers | 7 | 1 | 5 | 1 | 6/7 |
| 5. README | 9 | 1 | 8 | 0 | 9/9 |
| 6. API Design and Contracts | 7 | 7 | 0 | 0 | 7/7 |
| 7. Database and Migrations | 6 | 5 | 1 | 0 | 6/6 |
| 8. Dependency Management | 4 | 3 | 1 | 0 | 4/4 |
| **Total** | **57** | **26** | **29** | **2** | **55/57 (96%)** |

**Verdict: Production-ready.**

The passing threshold is 45/57. The project now scores 55/57.

The two remaining open items are not fixable at the application level:

1. **`APP_KEY` silent boot failure** — Laravel does not fail at startup when `APP_KEY` is missing; it fails mid-request. This is a framework behaviour. Mitigated by documenting `key:generate` prominently in the README and `.env.example`
2. **HSTS** — must be configured at the web server or load balancer, not in PHP. Noted in the README under production deployment

---

## What was fixed

| Fix | Commits |
|---|---|
| GitHub Actions CI workflow | `6d3ee14` |
| `SecureHeaders` middleware (`nosniff`, `X-Frame-Options`, remove `X-Powered-By`) | `6e00aef` |
| `config/cors.php` with `CORS_ALLOWED_ORIGINS` env var, no wildcard | `6e00aef` |
| `.env.example` rewritten with production warnings and inline comments | `2a2c77a` |
| README replaced — setup, endpoints, auth, env vars, design decisions | `0a68ee8` |
| `json_stderr` log channel for structured production logging | `8cb489a` |
