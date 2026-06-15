<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExerciseApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    // -------------------------------------------------------------------------
    // 1–5: Unauthenticated access
    // -------------------------------------------------------------------------

    public function test_unauthenticated_users_cannot_list_exercises(): void
    {
        $this->getJson('/api/exercises')->assertUnauthorized();
    }

    public function test_unauthenticated_users_cannot_view_an_exercise(): void
    {
        $exercise = Exercise::factory()->create();

        $this->getJson("/api/exercises/{$exercise->id}")->assertUnauthorized();
    }

    public function test_unauthenticated_users_cannot_create_an_exercise(): void
    {
        $this->postJson('/api/exercises', [])->assertUnauthorized();
    }

    public function test_unauthenticated_users_cannot_update_an_exercise(): void
    {
        $exercise = Exercise::factory()->create();

        $this->putJson("/api/exercises/{$exercise->id}", [])->assertUnauthorized();
    }

    public function test_unauthenticated_users_cannot_delete_an_exercise(): void
    {
        $exercise = Exercise::factory()->create();

        $this->deleteJson("/api/exercises/{$exercise->id}")->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // 6–10: List and search
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_list_exercises(): void
    {
        $this->actingAsUser();
        Exercise::factory()->count(3)->create();

        $this->getJson('/api/exercises')->assertOk();
    }

    public function test_list_returns_20_results_per_page(): void
    {
        $this->actingAsUser();
        Exercise::factory()->count(25)->create();

        $response = $this->getJson('/api/exercises')->assertOk();

        $this->assertCount(20, $response->json('data'));
    }

    public function test_list_includes_pagination_metadata(): void
    {
        $this->actingAsUser();
        Exercise::factory()->count(25)->create();

        $this->getJson('/api/exercises')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.total', 25);
    }

    public function test_search_filters_results_by_name(): void
    {
        $this->actingAsUser();

        Exercise::factory()->create(['name' => 'Barbell Squat', 'muscle_group' => 'Quadriceps']);
        Exercise::factory()->create(['name' => 'Bench Press',   'muscle_group' => 'Chest']);

        $response = $this->getJson('/api/exercises?search=squat')->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Barbell Squat', $response->json('data.0.name'));
    }

    public function test_search_filters_by_muscle_group_and_equipment_type(): void
    {
        $this->actingAsUser();

        Exercise::factory()->create(['name' => 'Leg Press', 'muscle_group' => 'Quadriceps', 'equipment_type' => 'Machine']);
        Exercise::factory()->create(['name' => 'Curl',      'muscle_group' => 'Biceps',     'equipment_type' => 'Dumbbell']);

        $this->getJson('/api/exercises?search=Quadriceps')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Leg Press');

        $this->getJson('/api/exercises?search=Dumbbell')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Curl');
    }

    // -------------------------------------------------------------------------
    // 11: Show
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_view_one_exercise(): void
    {
        $this->actingAsUser();
        $exercise = Exercise::factory()->create();

        $this->getJson("/api/exercises/{$exercise->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $exercise->id)
            ->assertJsonPath('data.name', $exercise->name)
            ->assertJsonPath('data.slug', $exercise->slug);
    }

    // -------------------------------------------------------------------------
    // 12–14: Create
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_create_an_exercise(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/exercises', [
            'name'         => 'Deadlift',
            'muscle_group' => 'Back',
        ])->assertStatus(201);

        $this->assertDatabaseHas('exercises', ['name' => 'Deadlift']);
    }

    public function test_create_returns_201(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/exercises', [
            'name'         => 'Pull Up',
            'muscle_group' => 'Back',
        ])->assertStatus(201);
    }

    public function test_create_response_includes_generated_slug(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/exercises', [
            'name'         => 'Barbell Squat',
            'muscle_group' => 'Quadriceps',
        ])->assertStatus(201)
            ->assertJsonPath('data.slug', 'barbell-squat');
    }

    // -------------------------------------------------------------------------
    // 15–16: Update
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_update_an_exercise(): void
    {
        $this->actingAsUser();
        $exercise = Exercise::factory()->create(['muscle_group' => 'Chest']);

        $this->putJson("/api/exercises/{$exercise->id}", [
            'name'         => $exercise->name,
            'muscle_group' => 'Back',
        ])->assertOk()
            ->assertJsonPath('data.muscle_group', 'Back');

        $this->assertDatabaseHas('exercises', ['id' => $exercise->id, 'muscle_group' => 'Back']);
    }

    public function test_update_ignores_current_record_for_unique_name_validation(): void
    {
        $this->actingAsUser();
        $exercise = Exercise::factory()->create(['name' => 'Squat', 'muscle_group' => 'Quadriceps']);

        $this->putJson("/api/exercises/{$exercise->id}", [
            'name'         => 'Squat',
            'muscle_group' => 'Quadriceps',
            'description'  => 'Updated description',
        ])->assertOk();
    }

    // -------------------------------------------------------------------------
    // 17–20: Validation failures
    // -------------------------------------------------------------------------

    public function test_duplicate_name_fails_with_422(): void
    {
        $this->actingAsUser();
        Exercise::factory()->create(['name' => 'Barbell Squat', 'muscle_group' => 'Quadriceps']);

        $this->postJson('/api/exercises', [
            'name'         => 'Barbell Squat',
            'muscle_group' => 'Legs',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->postJson('/api/exercises', [
            'name'         => 'barbell squat',
            'muscle_group' => 'Legs',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_invalid_video_url_fails_with_422(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/exercises', [
            'name'         => 'Squat',
            'muscle_group' => 'Legs',
            'video_url'    => 'not-a-url',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['video_url']);
    }

    public function test_missing_name_fails_with_422(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/exercises', [
            'muscle_group' => 'Legs',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_missing_muscle_group_fails_with_422(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/exercises', [
            'name' => 'Squat',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['muscle_group']);
    }

    // -------------------------------------------------------------------------
    // 21–25: Delete and soft-delete behaviour
    // -------------------------------------------------------------------------

    public function test_authenticated_user_can_delete_an_exercise(): void
    {
        $this->actingAsUser();
        $exercise = Exercise::factory()->create();

        $this->deleteJson("/api/exercises/{$exercise->id}")->assertStatus(204);

        $this->assertSoftDeleted('exercises', ['id' => $exercise->id]);
    }

    public function test_delete_returns_204(): void
    {
        $this->actingAsUser();
        $exercise = Exercise::factory()->create();

        $this->deleteJson("/api/exercises/{$exercise->id}")->assertStatus(204);
    }

    public function test_deleted_exercise_does_not_appear_in_list(): void
    {
        $this->actingAsUser();
        $exercise = Exercise::factory()->create();
        $exercise->delete();

        $response = $this->getJson('/api/exercises')->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains($exercise->id, $ids);
    }

    public function test_deleted_exercise_does_not_appear_in_search_results(): void
    {
        $this->actingAsUser();
        $exercise = Exercise::factory()->create(['name' => 'Deleted Squat', 'muscle_group' => 'Legs']);
        $exercise->delete();

        $this->getJson('/api/exercises?search=Deleted')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_fetching_deleted_exercise_returns_404(): void
    {
        $this->actingAsUser();
        $exercise = Exercise::factory()->create();
        $exercise->delete();

        $this->getJson("/api/exercises/{$exercise->id}")->assertNotFound();
    }
}
