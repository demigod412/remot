<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class SecureFileAccessTest extends FeatureTestCase
{
    public function test_kyc_document_is_only_served_to_owner(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $other = $this->makeUser();

        Storage::disk('local')->put('kyc/secret-front.jpg', 'fake-binary');
        $owner->forceFill(['kyc_data' => ['front_image' => 'secret-front.jpg']])->save();

        $url = route('secure.kyc', ['user' => $owner->id, 'side' => 'front']);

        // Guest — denied.
        $this->get($url)->assertForbidden();

        // A different logged-in user — denied (no IDOR).
        $this->actingAs($other, 'web')->get($url)->assertForbidden();

        // The owner — allowed.
        $this->actingAs($owner, 'web')->get($url)->assertOk();
    }

    public function test_missing_kyc_file_returns_404_for_owner(): void
    {
        Storage::fake('local');

        $owner = $this->makeUser();
        $owner->forceFill(['kyc_data' => ['front_image' => 'does-not-exist.jpg']])->save();

        $this->actingAs($owner, 'web')
            ->get(route('secure.kyc', ['user' => $owner->id, 'side' => 'front']))
            ->assertNotFound();
    }
}
