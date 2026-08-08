<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkCategory;
use App\Models\WorkSubcategory;
use App\Models\WorkSubmission;
use App\Services\TaskApplicationService;

/**
 * Approving and rejecting an application, driven through real HTTP requests.
 *
 * WHY THIS FILE EXISTS
 *
 * Removing the zip upload from the approval screen broke approval twice over, and
 * neither break was visible to any check that existed:
 *
 *   1. $request->file('task_files') returns NULL, not an empty array, when the field
 *      is absent. The controller looped over it directly, so every approval became a
 *      fatal the moment the field went away.
 *   2. approveApplication() typed $instructions as `string` while the form request
 *      had just made it nullable, so the next click would have been a TypeError.
 *
 * Both are valid PHP that lints clean. What catches them is posting to the route the
 * way the form does, which is what these tests do. The unit-level tests in
 * TaskLifecycleTest call the service directly and would pass through both bugs.
 */
class TaskApprovalFlowTest extends FeatureTestCase
{
    private function admin(): Admin
    {
        return Admin::firstOrCreate(
            ['username' => 'approval_admin'],
            [
                'name'     => 'Approval Admin',
                'email'    => 'approval_admin@example.test',
                'password' => bcrypt('password'),
            ]
        );
    }

    private function category(float $fee = 10): WorkCategory
    {
        return WorkCategory::create([
            'name'                    => 'Cat ' . bin2hex(random_bytes(3)),
            'status'                  => 1,
            'application_cost'        => $fee,
            'commission_percent'      => 20,
            'eligible_user_type'      => 0,
            'daily_application_limit' => 0,
        ]);
    }

    private function work(WorkCategory $category, bool $withJson = true): Work
    {
        $sub = WorkSubcategory::create([
            'category_id' => $category->id,
            'name'        => 'Sub ' . bin2hex(random_bytes(3)),
            'status'      => 1,
        ]);

        return Work::create([
            'poster_id'        => 1,
            'poster_type'      => 1,
            'category_id'      => $category->id,
            'subcategory_id'   => $sub->id,
            'slug'             => 'task-' . bin2hex(random_bytes(4)),
            'task_id'          => 'TASK-' . strtoupper(bin2hex(random_bytes(3))),
            'title'            => 'Approval flow task',
            'description'      => 'Do the thing.',
            'worker_slots'     => 5,
            'total_coins'      => 0,
            'coins_per_worker' => 0,
            'payout_usd'       => 20,
            'work_status'      => 1,
            'approval_status'  => 1,
            'question_count'   => $withJson ? 2 : 0,
            'task_json'        => $withJson ? [
                'meta'      => ['title' => 'Approval flow task'],
                'questions' => [
                    ['id' => 'q1', 'type' => 'free_text', 'prompt' => 'Why?', 'required' => true],
                    ['id' => 'q2', 'type' => 'likert', 'prompt' => 'How good?',
                     'scale' => ['min' => 1, 'max' => 5], 'required' => true],
                ],
            ] : null,
        ]);
    }

    private function pendingApplication(WorkCategory $category, ?Work $work = null): WorkSubmission
    {
        $worker = $this->makeUser(['kyc_status' => 1, 'must_change_password' => false], 1000);

        return app(TaskApplicationService::class)->apply($worker, $work ?? $this->work($category));
    }

    // ───────────────────────── approving ─────────────────────────

    /**
     * The bug that broke production: no file field on the form any more, so the
     * request arrives with task_files absent entirely.
     */
    public function test_an_application_can_be_approved_with_no_file_attached(): void
    {
        $submission = $this->pendingApplication($this->category());

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.task-review.application.approve', $submission->id), [
                'task_instructions' => 'Read the brief in the console.',
            ])
            ->assertRedirect();

        $fresh = $submission->fresh();

        $this->assertSame(WorkSubmission::APP_APPROVED, $fresh->application_status);
        $this->assertSame(WorkSubmission::DEL_NOT_STARTED, $fresh->delivery_status);
    }

    /**
     * The second bug, one click behind the first: instructions became optional in
     * the form request while the service still typed the parameter as `string`.
     */
    public function test_an_application_can_be_approved_with_no_instructions(): void
    {
        $submission = $this->pendingApplication($this->category());

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.task-review.application.approve', $submission->id), [])
            ->assertRedirect();

        $this->assertSame(WorkSubmission::APP_APPROVED, $submission->fresh()->application_status);
    }

    public function test_approval_issues_a_usable_annotate_code(): void
    {
        $submission = $this->pendingApplication($this->category());

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.task-review.application.approve', $submission->id), []);

        $code = $submission->fresh()->annotate_code;

        $this->assertNotNull($code, 'Approval must issue a code, or the worker cannot open the task.');
        $this->assertMatchesRegularExpression('/^AN-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{8}$/', $code);
    }

    public function test_the_worker_can_open_the_console_with_that_code(): void
    {
        $submission = $this->pendingApplication($this->category());

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.task-review.application.approve', $submission->id), []);

        $fresh = $submission->fresh();

        $this->actingAs($fresh->worker, 'web')
            ->get(route('user.annotate.console', $fresh->annotate_code))
            ->assertOk();
    }

    /**
     * Somebody else's code must not open somebody else's paid task, whatever they
     * are holding. Codes end up in screenshots and support threads.
     */
    public function test_another_worker_cannot_open_that_code(): void
    {
        $submission = $this->pendingApplication($this->category());

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.task-review.application.approve', $submission->id), []);

        $stranger = $this->makeUser(['kyc_status' => 1, 'must_change_password' => false], 0);

        $this->actingAs($stranger, 'web')
            ->get(route('user.annotate.console', $submission->fresh()->annotate_code))
            ->assertRedirect(route('user.annotate.enter'));
    }

    public function test_approving_twice_is_refused(): void
    {
        $submission = $this->pendingApplication($this->category());
        $admin      = $this->admin();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.task-review.application.approve', $submission->id), []);

        $codeAfterFirst = $submission->fresh()->annotate_code;

        $this->actingAs($admin, 'admin')
            ->post(route('admin.task-review.application.approve', $submission->id), [])
            ->assertSessionHas('error');

        $this->assertSame($codeAfterFirst, $submission->fresh()->annotate_code,
            'A second approval must not reissue the code.');
    }

    // ───────────────────────── rejecting ─────────────────────────

    /**
     * The fee is non-refundable in every case. This was the platform's only refund
     * path until it was removed, so it is worth pinning down.
     */
    public function test_rejecting_an_application_keeps_the_fee(): void
    {
        $category   = $this->category(fee: 25);
        $submission = $this->pendingApplication($category);
        $worker     = $submission->worker;

        $balanceAfterFee = (float) $worker->fresh()->coin_balance;
        $this->assertSame(975.0, $balanceAfterFee);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.task-review.application.reject', $submission->id), [
                'rejection_reason' => 'Not suitable for this task.',
            ])
            ->assertRedirect();

        $this->assertSame(WorkSubmission::APP_REJECTED, $submission->fresh()->application_status);
        $this->assertSame($balanceAfterFee, (float) $worker->fresh()->coin_balance);
        $this->assertSame(0, \App\Models\LedgerEntry::where('category', 'task_apply_refund')->count());
    }

    public function test_a_rejected_application_has_no_annotate_code(): void
    {
        $submission = $this->pendingApplication($this->category());

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.task-review.application.reject', $submission->id), [
                'rejection_reason' => 'Not suitable for this task.',
            ]);

        $this->assertNull($submission->fresh()->annotate_code,
            'A refused worker must not be handed a way in.');
    }

    // ───────────────────── task without questions ─────────────────────

    /**
     * A task predating the JSON format can still be approved — the admin screen
     * warns about it — but the console must refuse rather than render nothing.
     */
    public function test_a_task_with_no_questions_cannot_be_opened(): void
    {
        $category   = $this->category();
        $work       = $this->work($category, withJson: false);
        $submission = $this->pendingApplication($category, $work);

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.task-review.application.approve', $submission->id), []);

        $fresh = $submission->fresh();

        $this->actingAs($fresh->worker, 'web')
            ->get(route('user.annotate.console', $fresh->annotate_code))
            ->assertRedirect(route('user.annotate.enter'))
            ->assertSessionHas('error');
    }
}
