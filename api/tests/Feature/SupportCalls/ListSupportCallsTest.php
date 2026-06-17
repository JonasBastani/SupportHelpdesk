<?php

namespace Tests\Feature\SupportCalls;

use App\Models\SupportCall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListSupportCallsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_support_calls_with_default_pagination_of_ten_items(): void
    {
        $responsibleUser = User::factory()->create();

        SupportCall::factory()->count(11)->create([
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $response = $this->getJson('/api/support-calls');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('total', 11);
    }

    public function test_it_orders_support_calls_by_creation_date(): void
    {
        $responsibleUser = User::factory()->create();

        $olderSupportCall = SupportCall::factory()->create([
            'title' => 'Primeiro chamado',
            'responsible_user_id' => $responsibleUser->id,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        $newerSupportCall = SupportCall::factory()->create([
            'title' => 'Segundo chamado',
            'responsible_user_id' => $responsibleUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/support-calls?sort_by=created_at&sort_direction=asc');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $olderSupportCall->id)
            ->assertJsonPath('data.1.id', $newerSupportCall->id);
    }

    public function test_it_orders_support_calls_by_status(): void
    {
        $responsibleUser = User::factory()->create();

        $closedSupportCall = SupportCall::factory()->create([
            'status' => 'closed',
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $resolvedSupportCall = SupportCall::factory()->create([
            'status' => 'resolved',
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $response = $this->getJson('/api/support-calls?sort_by=status&sort_direction=asc');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.id', $closedSupportCall->id)
            ->assertJsonPath('data.1.id', $resolvedSupportCall->id);
    }
}
