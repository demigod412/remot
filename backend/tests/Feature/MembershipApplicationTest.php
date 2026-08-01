<?php

namespace Tests\Feature;

use App\Models\MembershipApplication;
use App\Models\User;
use App\Services\ApplicationException;
use App\Services\MembershipService;
use Illuminate\Support\Facades\Hash;

class MembershipApplicationTest extends FeatureTestCase
{
    protected MembershipService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MembershipService();
    }

    protected function makeApplication(array $attributes = []): MembershipApplication
    {
        $uid = bin2hex(random_bytes(4));

        return MembershipApplication::create(array_merge([
            'full_name'      => 'Ada Lovelace',
            'email'          => "ada_{$uid}@example.test",
            'country'        => 'Nigeria',
            'applicant_type' => MembershipApplication::TYPE_INDIVIDUAL,
            'status'         => MembershipApplication::STATUS_PENDING,
            'reference_code' => MembershipApplication::generateReferenceCode(),
            'submitted_at'   => now(),
        ], $attributes));
    }

    public function test_approval_creates_a_user_needing_a_password_change(): void
    {
        $app  = $this->makeApplication();
        $user = $this->service->approve($app);

        $this->assertSame($app->email, $user->email);
        $this->assertTrue((bool) $user->must_change_password);
        $this->assertSame(1, (int) $user->status);
        $this->assertSame(MembershipApplication::STATUS_APPROVED, $app->fresh()->status);
    }

    public function test_business_application_creates_a_business_user(): void
    {
        $app = $this->makeApplication([
            'applicant_type' => MembershipApplication::TYPE_BUSINESS,
            'business_name'  => 'Analytical Engines Ltd',
        ]);

        $user = $this->service->approve($app);

        $this->assertSame(User::TYPE_BUSINESS, (int) $user->user_type);
    }

    public function test_temp_password_is_not_the_stored_plaintext(): void
    {
        $app  = $this->makeApplication();
        $user = $this->service->approve($app);

        // Whatever was mailed, the column must be a hash.
        $this->assertNotSame('', $user->password);
        $this->assertFalse(Hash::check('', $user->password));
        $this->assertStringStartsWith('$', $user->password);
    }

    public function test_cannot_approve_the_same_application_twice(): void
    {
        $app = $this->makeApplication();
        $this->service->approve($app);

        $this->expectException(ApplicationException::class);
        $this->service->approve($app->fresh());
    }

    public function test_usernames_do_not_collide(): void
    {
        $a = $this->service->approve($this->makeApplication(['full_name' => 'Ada Lovelace']));
        $b = $this->service->approve($this->makeApplication(['full_name' => 'Ada Lovelace']));

        $this->assertNotSame($a->username, $b->username);
    }

    public function test_rejection_records_the_reason_and_creates_no_user(): void
    {
        $app = $this->makeApplication();

        $this->service->reject($app, 'Incomplete documents.');

        $this->assertSame(MembershipApplication::STATUS_REJECTED, $app->fresh()->status);
        $this->assertSame('Incomplete documents.', $app->fresh()->rejection_reason);
        $this->assertNull(User::where('email', $app->email)->first());
    }

    public function test_reference_codes_are_unique(): void
    {
        $codes = [];
        for ($i = 0; $i < 25; $i++) {
            $codes[] = MembershipApplication::generateReferenceCode();
        }

        $this->assertSame(count($codes), count(array_unique($codes)));
    }
}
