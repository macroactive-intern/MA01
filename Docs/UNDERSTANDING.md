MA01 — Exercise Library API Understanding

The Task

I need to make a laravel JSON API for a shared exercise library used by MacroActive Coaches

------------------------------------------------------------------------------------------------------------------------

API must allow authenticated users to:

- List exercises
- Search exercises
- View one exercise
- Create an exercise
- Update an exercise
- Deleete an exercise

The project will be starting from an empty directory, so i will need to do the setup as part of the task.

------------------------------------------------------------------------------------------------------------------------

The required implementations:

- An exercises database table
- An Exercise model
- A controller for CRUD actions
- API routes
- A FormRequest class validation
- Feature tests
- Workflow documentation
    - UNDERSTANDING.md
    - ESTIMATE.md
    - APPROACH.md
    - BEFORE-AFTER.md

(make sure to follow the workflow.md)

------------------------------------------------------------------------------------------------------------------------

What inputs does it take?

GET /api/exercises

Inputs:

- Optional query parameter: search

Example:

GET /api/exercises?search=squat

Expected behavior:

- Returns paginated exercise results
- Uses 20 results per page
- Includes standard Laravel pagination metadata
- Filters results when search is provided

The brief does not fully define which fields should be searched, so I need to make a clear assumption before building.

-----------------------------------------------------------

GET /api/exercises/{id}

Inputs:

- Exercise ID in the URL

Example:

GET /api/exercises/1

Expected behavior:

- Returns one exercise if it exists
- Returns 404 if it does not exist or has been deleted

-----------------------------------------------------------------------------------

POST /api/exercises

Inputs:

JSON body with exercise data:

{
  "name": "Barbell Squat",
  "muscle_group": "Legs",
  "equipment_type": "Barbell",
  "video_url": "https://example.com/squat-video",
  "description": "A compound lower-body exercise."
}

Expected behavior:

- Validates the input
- Creates an exercise
- Returns the created resource
- Returns HTTP 201

-----------------------------------------------------------------------------------

PUT /api/exercises/{id}

Inputs:

Exercise ID in the URL
JSON body with updated exercise data

Expected behavior:

- Validates the input
- Updates the exercise
- Returns the updated resource

-----------------------------------------------------------------------------------

DELETE /api/exercises/{id}

Inputs:

Exercise ID in the URL

Expected behavior:

- Deletes the exercise
Returns HTTP 204
- Deleted exercises must not appear in list or search results
- A later GET request to the same exercise ID should return 404

------------------------------------------------------------------------------------------------------------------------

What it shoul display?

This is jut a JSON API, so it wont display any web pages.

It should return JSON responses such as:

- Paginated exercise lists
- Single exercise objects
- Validation error objects
- Empty 204 response after delete
- 401 response for unauthenticated users
- 404 response for missing or deleted exercises
- 422 response for validation errors

The API should use standard Laravel response behavior where possible.

------------------------------------------------------------------------------------------------------------------------

Required exercise fields:

The brief lists the following database columns:

Field           |    	Required?        |   	Notes
id	            |       Yes	             |      Primary key
name	        |       Yes	             |      String, max 120, unique
muscle_group    |	    Yes	             |      String, max 60
equipment_type  |   	No               |      String, max 60
video_url       |	    No               |	    String, max 255 in database, valid URL if provided
description	    |       No	             |      Text
created_at	    |       Yes	             |      Laravel timestamp
updated_at	    |       Yes	             |      Laravel timestamp

------------------------------------------------------------------------------------------------------------------------

Something that looks unclear or contradictory or isn't fleshed out.

The brief mentions a slug. A slug field is a URL-friendly version of a name/title

-------------------------------------------

An example, your exercise name might be:

Barbell Squat

-------------------------------------------

The slug would be:

barbell-squat

-------------------------------------------

So instead of a mobile app link like:

/app/exercises/15

it could use:

/app/exercises/barbell-squat

-------------------------------------------

In your Exercise Library API, the database might store both:

name: Barbell Squat
slug: barbell-squat

------------------------------------------------------------------------------------------------------------------------

Search behavior is under-specified

The brief does not say what can be searched for...

Possible options:

- Search only name
- Search name and muscle_group
- Search name, muscle_group, and equipment_type
- Search description too

Question:

Which fields should be able to search?

Answer:

My assumption they should be able search the fields the will be most helpful browsing the exercise library

------------------------------------------------------------------------------------------------------------------------

Routes use {id}, but if implementing slug support it may suggest slug-based lookup later since it was agreed in the design meeting

endpoints the table uses:

- GET /api/exercises/{id} 
- PUT /api/exercises/{id} 
- DELETE /api/exercises/{id}

Should the API use numeric IDs only, while storing slugs for mobile URLs?
or
Should show/update/delete support lookup by slug?

My assumption for this task is to keep the required routes ID-based because the endpoint table explicitly says {id}. I'll still store and return the slug adding it to the schema.

------------------------------------------------------------------------------------------------------------------------

Slug validation and generation are not specified

If a slug column is added, the brief does not say:

- Whether clients send the slug
- Whether the API generates it from name
- Whether it must be unique
- Whether it changes when the name changes

My assumption would be:

- Generate the slug automatically from name
- Store it uniquely
- Update it when the name changes unless there is a reason not to
- Ensure duplicate names cannot create duplicate slugs because names are already unique

------------------------------------------------------------------------------------------------------------------------

Authentication setup is required, but user registration/login endpoints are not specified

All exercise endpoints require Sanctum token authentication.

The brief is not requiring me to build auth endpoints such as:

- Register
- Login
- Logout
- Token creation endpoint

Since its not required to build out any auth endpoints I can use Laravel Sanctum testing helpers such as Sanctum::actingAs($user)

------------------------------------------------------------------------------------------------------------------------

API resource formatting is not specified

The brief says the API should return the created or updated resource, but it does not say whether to use:

- Raw Eloquent model JSON
- API Resource classes
- Custom response wrappers

Assumption would be to return a standard JSON model/resource data directly

------------------------------------------------------------------------------------------------------------------------

Validation for video_url says valid URL, but max length is only in the database table

The database column says video_url is string(255), and the validation rules say nullable valid URL.

So the request validation should prevent any values that are too long for the database column.

------------------------------------------------------------------------------------------------------------------------

PUT usually requires the full object, but update behavior could allow partial input

The brief says validation rules for POST and PUT:

name: required
muscle_group: required
etc.

Should the update endpoint behave like a full replacement or allow partial updates?

PUT requires full required fields because the validation rules say name and muscle_group are required for POST and PUT.

------------------------------------------------------------------------------------------------------------------------

Assumptions I am making

- The API is JSON-only.
- All exercise endpoints are protected with auth:sanctum.
- The app uses SQLite for local development.
- Tests should use Sanctum authentication helpers rather than building login/register flows.
- GET /api/exercises returns 20 results per page using Laravel pagination.
- Search should likely match name, muscle_group, and equipment_type.
- The slug note should not be ignored. I should include a schema decision about it in APPROACH.md.
- Soft deletes are probably expected because the explain section asks what the deleted database record looks like.
- Validation should be handled through a FormRequest, not inline controller validation.
- Validation errors should use standard Laravel 422 error responses.
- Duplicate exercise names should fail validation before insert because of the unique validation rule.
- The database should also enforce uniqueness on name, not only the validation layer.
- If a slug field is added, it should also be unique.
- The update endpoint should ignore the current record when checking name uniqueness.
- Deleted exercises should not be returned by normal queries.

------------------------------------------------------------------------------------------------------------------------

Questions to resolve before building

- Should the exercises table include a slug column?
- Should slug be generated automatically from name?
- Should slug update when name changes?
- Should exercises use soft deletes?
- Should search check only name, or also muscle_group and equipment_type?
- Should search include description?
- Should the API return raw models or use Laravel API Resources?
- Are auth token creation endpoints required, or only authenticated exercise endpoints?
- Should update be full PUT only, or should partial update behavior also be allowed?

------------------------------------------------------------------------------------------------------------------------