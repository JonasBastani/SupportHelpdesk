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

    public function test_it_filters_support_calls_by_status(): void
    {
        $responsibleUser = User::factory()->create();

        $openSupportCall = SupportCall::factory()->create([
            'status' => 'open',
            'responsible_user_id' => $responsibleUser->id,
        ]);
        SupportCall::factory()->create([
            'status' => 'closed',
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $response = $this->getJson('/api/support-calls?status=open');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $openSupportCall->id)
            ->assertJsonPath('data.0.status', 'open');
    }

    public function test_it_filters_support_calls_by_priority(): void
    {
        $responsibleUser = User::factory()->create();

        $highPrioritySupportCall = SupportCall::factory()->create([
            'priority' => 'high',
            'responsible_user_id' => $responsibleUser->id,
        ]);
        SupportCall::factory()->create([
            'priority' => 'low',
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $response = $this->getJson('/api/support-calls?priority=high');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $highPrioritySupportCall->id)
            ->assertJsonPath('data.0.priority', 'high');
    }

    public function test_it_filters_support_calls_by_status_and_priority(): void
    {
        $responsibleUser = User::factory()->create();

        $matchingSupportCall = SupportCall::factory()->create([
            'status' => 'in_progress',
            'priority' => 'medium',
            'responsible_user_id' => $responsibleUser->id,
        ]);
        SupportCall::factory()->create([
            'status' => 'in_progress',
            'priority' => 'high',
            'responsible_user_id' => $responsibleUser->id,
        ]);
        SupportCall::factory()->create([
            'status' => 'open',
            'priority' => 'medium',
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $response = $this->getJson('/api/support-calls?status=in_progress&priority=medium');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matchingSupportCall->id)
            ->assertJsonPath('data.0.status', 'in_progress')
            ->assertJsonPath('data.0.priority', 'medium');
    }

    public function test_it_keeps_pagination_and_sorting_when_filtering_support_calls(): void
    {
        $responsibleUser = User::factory()->create();

        $olderSupportCall = SupportCall::factory()->create([
            'status' => 'open',
            'priority' => 'high',
            'responsible_user_id' => $responsibleUser->id,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        $newerSupportCall = SupportCall::factory()->create([
            'status' => 'open',
            'priority' => 'high',
            'responsible_user_id' => $responsibleUser->id,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);
        SupportCall::factory()->create([
            'status' => 'open',
            'priority' => 'low',
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $response = $this->getJson(
            '/api/support-calls?status=open&priority=high&sort_by=created_at&sort_direction=asc&per_page=1',
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $olderSupportCall->id)
            ->assertJsonPath('per_page', 1)
            ->assertJsonPath('total', 2);

        $secondPageResponse = $this->getJson(
            '/api/support-calls?status=open&priority=high&sort_by=created_at&sort_direction=asc&per_page=1&page=2',
        );

        $secondPageResponse
            ->assertOk()
            ->assertJsonPath('data.0.id', $newerSupportCall->id);
    }

    public function test_it_rejects_invalid_status_filter(): void
    {
        $response = $this->getJson('/api/support-calls?status=waiting');

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'O status informado e invalido.');
    }

    public function test_it_rejects_invalid_priority_filter(): void
    {
        $response = $this->getJson('/api/support-calls?priority=urgent');

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.priority.0', 'A prioridade informada e invalida.');
    }
}
