# BEFORE-AFTER.md

## What changed

Added the full Exercise Library API implementation:

- `database/migrations/..._create_exercises_table.php` — exercises table with slug, soft deletes
- `app/Models/Exercise.php` — SoftDeletes, slug auto-generation on `creating`, no slug in `$fillable`
- `database/factories/ExerciseFactory.php` — factory for test data
- `app/Http/Requests/ExerciseRequest.php` — FormRequest with case-insensitive unique name closure rule
- `app/Http/Resources/ExerciseResource.php` — explicit field whitelist, excludes `deleted_at`
- `app/Http/Controllers/Api/ExerciseController.php` — thin controller, index/show/store/update/destroy
- `routes/api.php` — 5 manual routes behind `auth:sanctum`, no PATCH
- `tests/Feature/ExerciseApiTest.php` — 25 tests covering all acceptance criteria

---

## Before (failing)

Tests run against bare stubs: no routes, no model events, no slug generation.

```
  PASS  Tests\Unit\ExampleTest
  ✓ that true is true

  PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                               0.15s

  FAIL  Tests\Feature\ExerciseApiTest
  ⨯ unauthenticated users cannot list exercises                                                                 0.09s
  ⨯ unauthenticated users cannot view an exercise                                                               0.03s
  ⨯ unauthenticated users cannot create an exercise                                                             0.01s
  ⨯ unauthenticated users cannot update an exercise                                                             0.01s
  ⨯ unauthenticated users cannot delete an exercise                                                             0.01s
  ⨯ authenticated user can list exercises                                                                       0.03s
  ⨯ list returns 20 results per page                                                                            0.02s
  ⨯ list includes pagination metadata                                                                           0.01s
  ⨯ search filters results by name                                                                              0.01s
  ⨯ search filters by muscle group and equipment type                                                           0.01s
  ⨯ authenticated user can view one exercise                                                                    0.01s
  ⨯ authenticated user can create an exercise                                                                   0.01s
  ⨯ create returns 201                                                                                          0.01s
  ⨯ create response includes generated slug                                                                     0.01s
  ⨯ authenticated user can update an exercise                                                                   0.01s
  ⨯ update ignores current record for unique name validation                                                    0.01s
  ⨯ duplicate name fails with 422                                                                               0.01s
  ⨯ invalid video url fails with 422                                                                            0.01s
  ⨯ missing name fails with 422                                                                                 0.01s
  ⨯ missing muscle group fails with 422                                                                         0.01s
  ⨯ authenticated user can delete an exercise                                                                   0.01s
  ⨯ delete returns 204                                                                                          0.01s
  ⨯ deleted exercise does not appear in list                                                                    0.01s
  ⨯ deleted exercise does not appear in search results                                                          0.01s
  ⨯ fetching deleted exercise returns 404                                                                       0.01s

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────
   FAILED  Tests\Feature\ExerciseApiTest > unauthenticated users cannot list exercises
  Expected response status code [401] but received [404].
  Failed asserting that 404 is identical to 401.

  at tests\Feature\ExerciseApiTest.php:24
     20▕     private function actingAsUser(): void
     21▕     {
     22▕         Sanctum::actingAs(User::factory()->create());
     23▕     }
  ➜  24▕         $this->getJson('/api/exercises')->assertUnauthorized();

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────
   FAILED  Tests\Feature\ExerciseApiTest > authenticated user can update an exercise  QueryException
  SQLSTATE[23000]: Integrity constraint violation: 19 NOT NULL constraint failed: exercises.slug
  (Connection: sqlite, Database: :memory:,
   SQL: insert into "exercises" ("name", "muscle_group", "updated_at", "created_at")
   values (Exercise 60, Chest, 2026-06-15 02:54:55, 2026-06-15 02:54:55))

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────
   FAILED  Tests\Feature\ExerciseApiTest > authenticated user can create an exercise
  Expected response status code [201] but received 404.
  Failed asserting that 404 is identical to 201.

  at tests\Feature\ExerciseApiTest.php:152
    149▕         $this->postJson('/api/exercises', [
    150▕             'name'         => 'Deadlift',
    151▕             'muscle_group' => 'Back',
  ➜ 152▕         ])->assertStatus(201);

  Tests:    25 failed, 2 passed (25 assertions)
  Duration: 0.61s
```

**Root causes of failures:**

| Failure | Reason |
|---|---|
| Auth tests get 404 instead of 401 | Routes not registered — Laravel returns 404 for unknown URIs, not 401 |
| Factory tests throw `NOT NULL constraint failed: exercises.slug` | No `creating` model event — slug never generated, DB rejects the insert |
| Create/update/delete get 404 | Routes not registered |
| Validation tests get 404 | Routes not registered |
| Soft-delete tests throw `QueryException` | Factory can't create records without slug generation |

---

## After (passing)

Full implementation in place: routes registered, model events wired, slug generation active.

```
  PASS  Tests\Unit\ExampleTest
  ✓ that true is true

  PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                               0.15s

  PASS  Tests\Feature\ExerciseApiTest
  ✓ unauthenticated users cannot list exercises                                                                 0.08s
  ✓ unauthenticated users cannot view an exercise                                                               0.03s
  ✓ unauthenticated users cannot create an exercise                                                             0.01s
  ✓ unauthenticated users cannot update an exercise                                                             0.01s
  ✓ unauthenticated users cannot delete an exercise                                                             0.01s
  ✓ authenticated user can list exercises                                                                       0.03s
  ✓ list returns 20 results per page                                                                            0.02s
  ✓ list includes pagination metadata                                                                           0.02s
  ✓ search filters results by name                                                                              0.02s
  ✓ search filters by muscle group and equipment type                                                           0.02s
  ✓ authenticated user can view one exercise                                                                    0.01s
  ✓ authenticated user can create an exercise                                                                   0.02s
  ✓ create returns 201                                                                                          0.01s
  ✓ create response includes generated slug                                                                     0.01s
  ✓ authenticated user can update an exercise                                                                   0.01s
  ✓ update ignores current record for unique name validation                                                    0.01s
  ✓ duplicate name fails with 422                                                                               0.01s
  ✓ invalid video url fails with 422                                                                            0.01s
  ✓ missing name fails with 422                                                                                 0.01s
  ✓ missing muscle group fails with 422                                                                         0.01s
  ✓ authenticated user can delete an exercise                                                                   0.01s
  ✓ delete returns 204                                                                                          0.01s
  ✓ deleted exercise does not appear in list                                                                    0.01s
  ✓ deleted exercise does not appear in search results                                                          0.01s
  ✓ fetching deleted exercise returns 404                                                                       0.01s

  Tests:    27 passed (72 assertions)
  Duration: 0.74s
```
