# APPROACH.md

# MA01 — Exercise Library API Approach

## Goal

Build a Laravel JSON API for managing a shared exercise library.

The API will support:

* Listing exercises
* Searching exercises
* Viewing one exercise
* Creating exercises
* Updating exercises
* Deleting exercises

All endpoints will require Sanctum authentication.

The implementation must also follow the project workflow documents and acceptance criteria before the task is considered complete.

---

## Project setup approach

The project starts from an empty directory.

I will create a new Laravel project and install Sanctum:

```bash
composer create-project laravel/laravel exercise-library-api
cd exercise-library-api
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

For local development and tests, I will use SQLite because the brief says this is simpler for local dev.

I will configure:

* `.env`
* `config/database.php` if needed
* `database/database.sqlite`
* Sanctum middleware/auth setup
* API routes protected by `auth:sanctum`

---

## Schema decisions

The main table will be `exercises`.

The required brief fields are:

| Column           | Type           | Notes              |
| ---------------- | -------------- | ------------------ |
| `id`             | big increments | Primary key        |
| `name`           | string(120)    | Required, unique   |
| `muscle_group`   | string(60)     | Required           |
| `equipment_type` | string(60)     | Nullable           |
| `video_url`      | string(255)    | Nullable           |
| `description`    | text           | Nullable           |
| `created_at`     | timestamps     | Laravel timestamps |
| `updated_at`     | timestamps     | Laravel timestamps |

---

## Slug decision

The brief mentions that a teammate's planning notes said a `slug` field was agreed in a design meeting for mobile deep-linking.

Even though `slug` is missing from the formal schema table, I will include it because the mobile app uses slugs in workout-player URLs.

I will add:

| Column | Type        | Notes                                   |
| ------ | ----------- | --------------------------------------- |
| `slug` | string(140) | Required, unique, generated from `name` |

The slug will be generated from the exercise name.

Example:

```text
name: Barbell Squat
slug: barbell-squat
```

I will not make clients provide the slug manually. This keeps the API easier to use and avoids invalid slug input.

On create:

* Generate slug from `name`

On update:

* If `name` changes, regenerate the slug from the new name

Because `name` is unique, generated slugs should normally be unique too. I will also add a unique database constraint to `slug` as a safety net.

---

## Delete decision

I will use Laravel soft deletes.

Reason:

The acceptance criteria says deleted exercises should:

* Not appear in list results
* Not appear in search results
* Return 404 when fetched by ID

That behavior works with soft deletes.

The explanation section also asks:

> How does Laravel know a particular exercise has been deleted? What does the database record look like?

That question strongly suggests soft deletes, because with a hard delete the database row would no longer exist.

I will add:

```php
$table->softDeletes();
```

This creates a nullable `deleted_at` column.

When an exercise is deleted:

* The row remains in the database
* `deleted_at` is filled with a timestamp
* Normal Eloquent queries automatically exclude it
* Route model binding will not find it by default
* A `GET` request for the deleted exercise returns 404

---

## Model approach

Create:

```text
app/Models/Exercise.php
```

The model will use:

```php
use Illuminate\Database\Eloquent\SoftDeletes;
```

The model will allow mass assignment for safe fields only:

```php
protected $fillable = [
    'name',
    'slug',
    'muscle_group',
    'equipment_type',
    'video_url',
    'description',
];
```

I will not expose ownership fields because this exercise library is shared and the brief does not require per-user ownership.

---

## Validation approach

Validation must be handled by a `FormRequest`, not inline controller validation.

Create:

```text
app/Http/Requests/ExerciseRequest.php
```

The rules will cover both POST and PUT.

Rules:

```php
'name' => [
    'required',
    'string',
    'max:120',
    Rule::unique('exercises', 'name')->ignore($this->route('exercise')),
],
'muscle_group' => [
    'required',
    'string',
    'max:60',
],
'equipment_type' => [
    'nullable',
    'string',
    'max:60',
],
'video_url' => [
    'nullable',
    'url',
    'max:255',
],
'description' => [
    'nullable',
    'string',
],
```

The update rule must ignore the current exercise so that an exercise can be saved without changing its name.

Example:

* Existing exercise: `Barbell Squat`
* Updating description only should pass
* Creating another exercise called `Barbell Squat` should fail with 422

Validation errors will use Laravel's normal 422 JSON error format.

---

## Controller approach

Create:

```text
app/Http/Controllers/Api/ExerciseController.php
```

Controller methods:

| Method    | Responsibility                                              |
| --------- | ----------------------------------------------------------- |
| `index`   | List exercises, paginate 20 per page, apply optional search |
| `show`    | Return one exercise                                         |
| `store`   | Validate and create an exercise, return 201                 |
| `update`  | Validate and update an exercise                             |
| `destroy` | Soft delete an exercise, return 204                         |

The controller should stay thin.

Business rules such as validation should stay in the `FormRequest`.

Slug generation can happen in the controller or model. I will use a clear helper method or model event to keep it consistent.

---

## Route design

Routes will be added to:

```text
routes/api.php
```

I will use API resource routing inside Sanctum middleware:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('exercises', ExerciseController::class);
});
```

This gives the required endpoints:

| Method    | URI                         | Controller method |
| --------- | --------------------------- | ----------------- |
| GET       | `/api/exercises`            | `index`           |
| GET       | `/api/exercises/{exercise}` | `show`            |
| POST      | `/api/exercises`            | `store`           |
| PUT/PATCH | `/api/exercises/{exercise}` | `update`          |
| DELETE    | `/api/exercises/{exercise}` | `destroy`         |

The brief specifically requires `PUT`, but allowing `PATCH` through `apiResource` is acceptable unless the workflow says not to. Tests will focus on the required `PUT`.

---

## Search approach

The list endpoint accepts:

```text
?search=
```

The brief does not specify which fields to search.

I will search these fields:

* `name`
* `muscle_group`
* `equipment_type`

Reason:

These are the most useful fields for browsing an exercise library. I will not search `description` by default because long descriptions can create noisy search results.

Example:

```http
GET /api/exercises?search=squat
```

Expected result:

* Exercises with `squat` in the name should appear
* Exercises with `squat` in the muscle group or equipment type would also appear if matched
* Deleted exercises should not appear

Pagination still applies after filtering.

The query will use a grouped `where` block so the search conditions do not break other filters:

```php
$query->where(function ($query) use ($search) {
    $query->where('name', 'like', "%{$search}%")
        ->orWhere('muscle_group', 'like', "%{$search}%")
        ->orWhere('equipment_type', 'like', "%{$search}%");
});
```

---

## Pagination approach

`GET /api/exercises` will return:

```php
Exercise::query()->paginate(20);
```

This gives standard Laravel pagination metadata, including fields such as:

* `current_page`
* `data`
* `first_page_url`
* `from`
* `last_page`
* `last_page_url`
* `links`
* `next_page_url`
* `path`
* `per_page`
* `prev_page_url`
* `to`
* `total`

The acceptance criteria specifically asks for standard Laravel pagination metadata, so I will not create a custom pagination response.

---

## Response approach

### Create

`POST /api/exercises`

Returns:

* HTTP 201
* Created exercise JSON

### Update

`PUT /api/exercises/{exercise}`

Returns:

* HTTP 200
* Updated exercise JSON

### Delete

`DELETE /api/exercises/{exercise}`

Returns:

* HTTP 204
* Empty response body

### Validation failure

Returns:

* HTTP 422
* Standard Laravel validation errors object

### Unauthenticated request

Returns:

* HTTP 401

### Missing or deleted exercise

Returns:

* HTTP 404

---

## Authentication approach

All exercise endpoints will be protected by:

```php
auth:sanctum
```

The brief does not ask for login, register, or token creation endpoints.

For tests, I will authenticate users using Sanctum testing helpers:

```php
Sanctum::actingAs($user);
```

Unauthenticated request tests will call the endpoints without `Sanctum::actingAs()` and expect 401 responses.

---

## Test approach

Create a feature test file:

```text
tests/Feature/ExerciseApiTest.php
```

Tests should prove each acceptance criterion.

Planned tests:

1. Unauthenticated users cannot list exercises
2. Unauthenticated users cannot view an exercise
3. Unauthenticated users cannot create an exercise
4. Unauthenticated users cannot update an exercise
5. Unauthenticated users cannot delete an exercise
6. Authenticated user can list exercises
7. List endpoint returns 20 results per page
8. List endpoint includes standard Laravel pagination metadata
9. Search filters results by name
10. Search filters results by muscle group or equipment type
11. Authenticated user can view one exercise
12. Authenticated user can create an exercise
13. Create returns 201
14. Create response includes generated slug
15. Authenticated user can update an exercise
16. Update ignores current record for unique name validation
17. Duplicate name fails validation with 422
18. Invalid video URL fails validation with 422
19. Missing required name fails validation with 422
20. Missing required muscle group fails validation with 422
21. Authenticated user can delete an exercise
22. Delete returns 204
23. Deleted exercise does not appear in list
24. Deleted exercise does not appear in search results
25. Fetching a deleted exercise returns 404

The tests should be written before the implementation is fully passing so the workflow can show failing-then-passing progress.

---

## Edge cases

### Duplicate exercise names

The API should reject duplicate names.

This will be enforced in two places:

1. Validation rule: `unique:exercises,name`
2. Database unique index on `name`

Expected response:

* HTTP 422
* Standard Laravel validation error object

---

### Duplicate slugs

Since slugs are generated from names, duplicate slugs should not normally happen if names are unique.

However, the database should still enforce unique slugs.

Example risk:

```text
Name 1: Barbell Squat
Name 2: Barbell-Squat
```

Both could produce:

```text
barbell-squat
```

To avoid this, I should either:

* Validate slug uniqueness indirectly
* Generate a unique slug with a suffix if needed

Preferred approach:

```text
barbell-squat
barbell-squat-2
barbell-squat-3
```

This avoids database errors if two different names generate the same slug.

---

### Empty search value

If the request is:

```http
GET /api/exercises?search=
```

I will treat it the same as no search and return the normal paginated list.

---

### Deleted records

Soft-deleted exercises should be excluded automatically by Eloquent.

I will not use `withTrashed()` in normal API queries.

---

### Updating a deleted record

Because route model binding does not include soft-deleted records by default, updating a deleted exercise should return 404.

---

### Deleting an already deleted record

Because route model binding does not include soft-deleted records by default, deleting an already deleted exercise should return 404.

---

## Files to create or modify

Planned files:

```text
UNDERSTANDING.md
ESTIMATE.md
APPROACH.md
BEFORE-AFTER.md

database/migrations/xxxx_xx_xx_xxxxxx_create_exercises_table.php

app/Models/Exercise.php
app/Http/Controllers/Api/ExerciseController.php
app/Http/Requests/ExerciseRequest.php

routes/api.php

tests/Feature/ExerciseApiTest.php
```

Possible supporting files:

```text
database/factories/ExerciseFactory.php
```

A factory will make the feature tests cleaner.

---

## Acceptance criteria mapping

| Acceptance criterion                                        | Implementation plan                                     |
| ----------------------------------------------------------- | ------------------------------------------------------- |
| `GET /api/exercises` returns paginated results, 20 per page | Use `paginate(20)` in `index`                           |
| `?search=` filters results                                  | Add optional search query to `index`                    |
| `POST /api/exercises` creates and returns 201               | Use `store` with `ExerciseRequest`                      |
| `PUT /api/exercises/{id}` updates and returns resource      | Use `update` with `ExerciseRequest`                     |
| `DELETE /api/exercises/{id}` removes and returns 204        | Use soft delete and return empty 204                    |
| Validation errors return 422                                | Use Laravel `FormRequest`                               |
| All endpoints require authentication                        | Wrap routes in `auth:sanctum`                           |
| Deleted exercises excluded and return 404                   | Use `SoftDeletes` and normal route model binding        |
| FormRequest handles validation                              | Use `ExerciseRequest`, no inline `$request->validate()` |

---

## Explain-it preparation

### 1. What happens on `GET /api/exercises?search=squat`?

Request flow:

1. Request hits `routes/api.php`
2. Laravel matches `GET /api/exercises`
3. `auth:sanctum` middleware checks the token
4. If unauthenticated, Laravel returns 401
5. If authenticated, Laravel calls `ExerciseController@index`
6. The controller starts an `Exercise::query()`
7. If `search` is present, it adds grouped `LIKE` filters
8. Eloquent automatically excludes soft-deleted rows
9. The query is paginated with 20 results per page
10. Laravel returns JSON with exercise data and pagination metadata

---

### 2. How does Laravel know an exercise has been deleted?

The model uses the `SoftDeletes` trait.

The database row has a nullable `deleted_at` column.

Before delete:

```text
deleted_at: null
```

After delete:

```text
deleted_at: 2026-06-15 10:30:00
```

Laravel automatically excludes rows where `deleted_at` is not null from normal Eloquent queries.

---

### 3. FormRequest vs `$request->validate()`

A `FormRequest` is a dedicated validation class.

It keeps validation outside the controller.

Benefits:

* Cleaner controller
* Reusable validation rules
* Easier to test and review
* Matches the acceptance criteria
* Keeps request authorization and validation in one place

Inline `$request->validate()` works, but it mixes validation logic into controller actions. This task specifically requires a `FormRequest`, so inline validation would fail the acceptance criteria.

---

### 4. What happens if two exercises have the same name?

The `ExerciseRequest` checks that `name` is unique.

If a user tries to create a duplicate name, Laravel returns:

* HTTP 422
* Validation error on the `name` field

The database also has a unique index on `name`, so even if validation was bypassed, the database would still protect the data.


---

## Final extra decisions

### Case-insensitive name uniqueness

Name uniqueness is enforced case-insensitively using a custom closure validation rule, not `Rule::unique()`.

`Rule::unique()` adds its own `name = ?` comparison which is case-sensitive in SQLite, undermining the intent. The closure queries directly:

```php
'name' => [
    'required',
    'string',
    'max:120',
    function (string $attribute, mixed $value, Closure $fail): void {
        $query = Exercise::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $value))]);

        $exercise = $this->route('exercise');

        if ($exercise instanceof Exercise) {
            $query->whereKeyNot($exercise->getKey());
        }

        if ($query->exists()) {
            $fail('The name has already been taken.');
        }
    },
],
```

---

### Slug collision suffix logic

Slugs are generated only on `creating` and are immutable after that.

Two different names can produce the same base slug (e.g. `"Barbell Squat"` and `"Barbell-Squat"` both generate `barbell-squat`). The `generateUniqueSlug()` method on the model appends an incrementing suffix to avoid database conflicts:

```
barbell-squat
barbell-squat-2
barbell-squat-3
```

The `slug` column remains out of `$fillable`. Clients cannot set or influence it.

---

### Index response shape

Responses use `ExerciseResource::collection($paginator)`, which produces:

```json
{
  "data": [],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 50
  }
}
```

This is the standard Laravel API resource collection shape. The `deleted_at` field is never exposed.

Tests assert against this shape:

```php
$response->assertJsonPath('meta.per_page', 20);
$response->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total']]);
```

---

### Default index order

The index query orders by `name ASC`, then `id ASC`:

```php
Exercise::query()->orderBy('name')->orderBy('id');
```

Alphabetical order is more useful for browsing an exercise library than insertion order. The `id` tie-breaker makes ordering stable.

---

### Factory is required

`database/factories/ExerciseFactory.php` is required, not optional. Pagination tests need 21+ records. The factory must not set `slug` — the model `creating` event handles it.

---

### Sanctum setup

Use `php artisan install:api` instead of manually requiring Sanctum. This scaffolds `routes/api.php`, publishes the Sanctum migration, and configures the API middleware group in one step.

---

### Authorization

Any authenticated user may create, update, and delete exercises. The brief defines no roles or permissions beyond `auth:sanctum`.

---

### Restore

Soft-deleted exercises cannot be restored through the API. Deletion is permanent from the client perspective. The soft-delete row is retained internally only.

---

### Routes

Manual routes only — no `Route::apiResource()`. This avoids registering `PATCH`:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('exercises', [ExerciseController::class, 'index']);
    Route::get('exercises/{exercise}', [ExerciseController::class, 'show']);
    Route::post('exercises', [ExerciseController::class, 'store']);
    Route::put('exercises/{exercise}', [ExerciseController::class, 'update']);
    Route::delete('exercises/{exercise}', [ExerciseController::class, 'destroy']);
});
```

`PUT` requires all required fields. Partial updates are not supported.
