<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\User;

class ContractEscrowTest extends FeatureTestCase
{
    public function test_sending_an_offer_holds_escrow_up_front(): void
    {
        $employer = $this->makeUser([], 1000);
        $worker   = $this->makeUser();

        $this->actingAs($employer, 'web')
            ->post(route('user.contracts.store'), [
                'worker_identifier' => $worker->username,
                'title'             => 'Build a landing page',
                'description'       => 'Please build a responsive marketing landing page for our campaign.',
                'amount'            => 200,
            ])
            ->assertRedirect();

        $contract = Contract::where('employer_id', $employer->id)->firstOrFail();

        $this->assertSame(0, (int) $contract->status, 'Contract should be in the offered state.');
        $this->assertSame(800.0, (float) $employer->fresh()->coin_balance, 'Escrow should be held at offer time.');
        $this->assertDatabaseHas('ledger_entries', [
            'user_id'   => $employer->id,
            'category'  => 'work_spend',
            'reference' => $contract->reference,
        ]);
    }

    public function test_declining_an_offer_refunds_the_employer(): void
    {
        $employer = $this->makeUser([], 1000);
        $worker   = $this->makeUser();

        $this->actingAs($employer, 'web')->post(route('user.contracts.store'), [
            'worker_identifier' => $worker->username,
            'title'             => 'Logo design',
            'description'       => 'Design a clean modern logo for the new product line please.',
            'amount'            => 300,
        ])->assertRedirect();

        $contract = Contract::where('employer_id', $employer->id)->firstOrFail();
        $this->assertSame(700.0, (float) $employer->fresh()->coin_balance);

        // Worker declines → escrow released back to employer.
        $this->actingAs($worker, 'web')
            ->post(route('user.contracts.decline', $contract->id), ['declined_reason' => 'Not available'])
            ->assertRedirect();

        $this->assertSame(4, (int) $contract->fresh()->status, 'Contract should be declined.');
        $this->assertSame(1000.0, (float) $employer->fresh()->coin_balance, 'Employer should be fully refunded.');
    }

    public function test_offer_is_rejected_when_employer_cannot_afford_it(): void
    {
        $employer = $this->makeUser([], 50);
        $worker   = $this->makeUser();

        $this->actingAs($employer, 'web')->post(route('user.contracts.store'), [
            'worker_identifier' => $worker->username,
            'title'             => 'Expensive job',
            'description'       => 'This contract costs far more than the employer currently has available.',
            'amount'            => 500,
        ]);

        $this->assertSame(50.0, (float) $employer->fresh()->coin_balance, 'Balance must be untouched.');
        $this->assertDatabaseMissing('contracts', ['employer_id' => $employer->id, 'amount' => 500]);
    }
}
