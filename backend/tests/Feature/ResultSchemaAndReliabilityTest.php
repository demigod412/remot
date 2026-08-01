<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use App\Models\WorkSubmission;
use App\Services\ApplicationException;
use App\Services\ResultSchemaValidator;
use App\Services\TaskApplicationService;
use App\Services\WorkerReliabilityService;

class ResultSchemaAndReliabilityTest extends FeatureTestCase
{
    protected ResultSchemaValidator $schema;
    protected WorkerReliabilityService $reliability;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema      = new ResultSchemaValidator();
        $this->reliability = new WorkerReliabilityService();
    }

    // -------------------------------------------------------------------------
    // Schema validation
    // -------------------------------------------------------------------------

    protected function sampleSchema(): array
    {
        return [
            'type'     => 'object',
            'required' => ['task_id', 'results'],
            'properties' => [
                'task_id' => ['type' => 'string', 'pattern' => '^[A-Z0-9-]{4,32}$'],
                'results' => [
                    'type'      => 'array',
                    'min_items' => 1,
                    'items'     => [
                        'type'     => 'object',
                        'required' => ['label'],
                        'properties' => [
                            'label'      => ['type' => 'string', 'enum' => ['yes', 'no', 'unclear']],
                            'confidence' => ['type' => 'number', 'min' => 0, 'max' => 1],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_a_conforming_payload_passes(): void
    {
        $payload = [
            'task_id' => 'ABC-1234',
            'results' => [
                ['label' => 'yes', 'confidence' => 0.9],
                ['label' => 'no'],
            ],
        ];

        $this->assertSame([], $this->schema->validate($payload, $this->sampleSchema()));
    }

    public function test_missing_required_key_is_reported(): void
    {
        $errors = $this->schema->validate(['task_id' => 'ABC-1234'], $this->sampleSchema());

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('results', implode(' ', $errors));
    }

    public function test_wrong_type_is_reported_with_the_path(): void
    {
        $errors = $this->schema->validate(
            ['task_id' => 12345, 'results' => [['label' => 'yes']]],
            $this->sampleSchema()
        );

        $this->assertStringContainsString('result.task_id', implode(' ', $errors));
        $this->assertStringContainsString('expected string', implode(' ', $errors));
    }

    public function test_value_outside_enum_is_rejected(): void
    {
        $errors = $this->schema->validate(
            ['task_id' => 'ABC-1234', 'results' => [['label' => 'maybe']]],
            $this->sampleSchema()
        );

        $this->assertStringContainsString('result.results[0].label', implode(' ', $errors));
    }

    public function test_numeric_bounds_are_enforced(): void
    {
        $errors = $this->schema->validate(
            ['task_id' => 'ABC-1234', 'results' => [['label' => 'yes', 'confidence' => 1.5]]],
            $this->sampleSchema()
        );

        $this->assertStringContainsString('at most 1', implode(' ', $errors));
    }

    public function test_empty_array_fails_min_items(): void
    {
        $errors = $this->schema->validate(
            ['task_id' => 'ABC-1234', 'results' => []],
            $this->sampleSchema()
        );

        $this->assertStringContainsString('at least 1', implode(' ', $errors));
    }

    public function test_pattern_is_enforced(): void
    {
        $errors = $this->schema->validate(
            ['task_id' => 'lowercase!', 'results' => [['label' => 'yes']]],
            $this->sampleSchema()
        );

        $this->assertStringContainsString('required format', implode(' ', $errors));
    }

    /** Unknown keys pass by default and only fail in strict mode. */
    public function test_strict_mode_rejects_unknown_keys(): void
    {
        $payload = ['task_id' => 'ABC-1234', 'results' => [['label' => 'yes']], 'extra' => 'x'];

        $this->assertSame([], $this->schema->validate($payload, $this->sampleSchema(), false));

        $strict = $this->schema->validate($payload, $this->sampleSchema(), true);
        $this->assertStringContainsString("unexpected key 'extra'", implode(' ', $strict));
    }

    public function test_integers_satisfy_a_number_type(): void
    {
        $errors = $this->schema->validate(
            ['task_id' => 'ABC-1234', 'results' => [['label' => 'yes', 'confidence' => 1]]],
            $this->sampleSchema()
        );

        $this->assertSame([], $errors);
    }

    public function test_booleans_are_not_numbers(): void
    {
        $errors = $this->schema->validate(['v' => true], [
            'type' => 'object', 'properties' => ['v' => ['type' => 'number']],
        ]);

        $this->assertNotEmpty($errors);
    }

    public function test_json_object_and_array_are_distinguished(): void
    {
        $objectSchema = ['type' => 'object'];
        $arraySchema  = ['type' => 'array'];

        $this->assertNotEmpty($this->schema->validate([1, 2, 3], $objectSchema));
        $this->assertNotEmpty($this->schema->validate(['a' => 1], $arraySchema));
        $this->assertSame([], $this->schema->validate(['a' => 1], $objectSchema));
        $this->assertSame([], $this->schema->validate([1, 2, 3], $arraySchema));
    }

    // -------------------------------------------------------------------------
    // Schema self-validation, so a broken schema cannot be saved
    // -------------------------------------------------------------------------

    public function test_a_valid_schema_self_validates(): void
    {
        $this->assertSame([], $this->schema->validateSchema($this->sampleSchema()));
    }

    public function test_unknown_type_in_schema_is_caught(): void
    {
        $errors = $this->schema->validateSchema(['type' => 'strng']);
        $this->assertStringContainsString('not a known type', implode(' ', $errors));
    }

    public function test_bad_regex_in_schema_is_caught(): void
    {
        $errors = $this->schema->validateSchema(['type' => 'string', 'pattern' => '[unclosed']);
        $this->assertStringContainsString('valid regular expression', implode(' ', $errors));
    }

    public function test_required_without_properties_is_caught(): void
    {
        $errors = $this->schema->validateSchema(['type' => 'object', 'required' => ['a']]);
        $this->assertStringContainsString("'properties' is missing", implode(' ', $errors));
    }

    // -------------------------------------------------------------------------
    // Worker reliability
    // -------------------------------------------------------------------------

    protected function makeWorkFor(): Work
    {
        $cat = WorkCategory::create([
            'name'               => 'Cat ' . bin2hex(random_bytes(3)),
            'status'             => 1,
            'application_cost'   => 0,
            'commission_percent' => 0,
            'eligible_user_type' => 0,
        ]);

        $sub = WorkSubcategory::create([
            'category_id' => $cat->id,
            'name'        => 'Sub ' . bin2hex(random_bytes(3)),
            'status'      => 1,
        ]);

        return Work::create([
            'poster_id' => 1, 'poster_type' => 1,
            'category_id' => $cat->id, 'subcategory_id' => $sub->id,
            'slug' => 'task-' . bin2hex(random_bytes(4)), 'title' => 'Test task',
            'description' => 'Do it.', 'worker_slots' => 50,
            'total_coins' => 500, 'coins_per_worker' => 10, 'avg_minutes' => 5,
            'work_status' => 1, 'approval_status' => 1,
        ]);
    }

    protected function giveStrikes(User $user, int $abandoned = 0, int $rejected = 0): void
    {
        $states = array_merge(
            array_fill(0, $abandoned, WorkSubmission::DEL_EXPIRED),
            array_fill(0, $rejected, WorkSubmission::DEL_REJECTED)
        );

        foreach ($states as $state) {
            WorkSubmission::create([
                'work_id'            => $this->makeWorkFor()->id,
                'work_poster_id'     => 1,
                'work_poster_type'   => 1,
                'worker_id'          => $user->id,
                'worker_type'        => 2,
                'application_status' => WorkSubmission::APP_APPROVED,
                'delivery_status'    => $state,
            ]);
        }
    }

    public function test_a_clean_worker_has_no_strikes(): void
    {
        $summary = $this->reliability->summary($this->makeUser());

        $this->assertSame(0, $summary['strikes']);
        $this->assertFalse($summary['blocked']);
    }

    public function test_abandonment_is_weighted_heavier_than_rejection(): void
    {
        $abandoner = $this->makeUser();
        $rejectee  = $this->makeUser();

        $this->giveStrikes($abandoner, abandoned: 1);
        $this->giveStrikes($rejectee, rejected: 1);

        $this->assertGreaterThan(
            $this->reliability->strikes($rejectee),
            $this->reliability->strikes($abandoner)
        );
    }

    public function test_worker_is_blocked_once_over_the_threshold(): void
    {
        config(['jobstation.accountability.max_strikes' => 6]);

        $user = $this->makeUser();
        $this->giveStrikes($user, abandoned: 2); // 2 x weight 3 = 6

        $this->assertTrue($this->reliability->isBlocked($user->fresh()));
    }

    public function test_a_blocked_worker_cannot_apply_and_is_not_charged(): void
    {
        config(['jobstation.accountability.max_strikes' => 3]);

        $user = $this->makeUser([], 100);
        $this->giveStrikes($user, abandoned: 1); // 3 strikes, at the threshold

        $work = $this->makeWorkFor();
        $work->category->update(['application_cost' => 10]);

        $this->expectException(ApplicationException::class);

        try {
            (new TaskApplicationService())->apply($user->fresh(), $work->fresh());
        } finally {
            // Blocked before the fee is taken.
            $this->assertSame(100.0, (float) $user->fresh()->coin_balance);
        }
    }

    public function test_zero_max_strikes_disables_the_block(): void
    {
        config(['jobstation.accountability.max_strikes' => 0]);

        $user = $this->makeUser();
        $this->giveStrikes($user, abandoned: 20);

        $this->assertFalse($this->reliability->isBlocked($user->fresh()));
    }

    public function test_clearing_strikes_forgives_history_without_deleting_it(): void
    {
        config(['jobstation.accountability.max_strikes' => 3]);

        $user = $this->makeUser();
        $this->giveStrikes($user, abandoned: 2);
        $this->assertTrue($this->reliability->isBlocked($user->fresh()));

        $before = WorkSubmission::where('worker_id', $user->id)->count();

        $this->reliability->clearStrikes($user);

        $this->assertFalse($this->reliability->isBlocked($user->fresh()));
        // The submissions themselves are untouched.
        $this->assertSame($before, WorkSubmission::where('worker_id', $user->id)->count());
        $this->assertDatabaseHas('admin_activity_log', ['action' => 'user.strikes_cleared']);
    }

    public function test_strikes_outside_the_window_do_not_count(): void
    {
        config(['jobstation.accountability.window_days' => 30]);

        $user = $this->makeUser();
        $this->giveStrikes($user, abandoned: 3);

        // Age the records past the window.
        WorkSubmission::where('worker_id', $user->id)
            ->update(['updated_at' => now()->subDays(90)]);

        $this->assertSame(0, $this->reliability->strikes($user->fresh()));
    }

    // -------------------------------------------------------------------------
    // Display boost must never touch slots
    // -------------------------------------------------------------------------

    public function test_display_boost_inflates_shown_count_but_not_slots(): void
    {
        $work = $this->makeWorkFor();
        $work->update(['worker_slots' => 5, 'display_application_boost' => 80]);

        $this->assertSame(0, $work->fresh()->real_application_count);
        $this->assertSame(80, $work->fresh()->display_application_count);

        // The number that governs whether anyone can still apply is untouched.
        $this->assertSame(5, $work->fresh()->slots_remaining);
    }

    public function test_boosted_task_still_accepts_a_full_set_of_real_workers(): void
    {
        $work = $this->makeWorkFor();
        $work->update(['worker_slots' => 3, 'display_application_boost' => 500]);

        $service = new TaskApplicationService();

        // All three real slots must remain usable despite the large boost.
        for ($i = 0; $i < 3; $i++) {
            $service->apply($this->makeUser([], 50), $work->fresh());
        }

        $this->assertSame(3, WorkSubmission::where('work_id', $work->id)->count());
        $this->assertSame(0, $work->fresh()->slots_remaining);
        $this->assertSame(503, $work->fresh()->display_application_count);
    }
}
