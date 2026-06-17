<?php

namespace Tests\Feature\SupportCalls;

use App\Models\SupportCall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManageSupportCallsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_support_call_with_an_explicit_responsible_user(): void
    {
        $responsibleUser = User::factory()->create();

        $response = $this->postJson('/api/support-calls', [
            'title' => 'Impressora parada',
            'description' => 'A impressora do financeiro nao imprime.',
            'priority' => 'high',
            'status' => 'open',
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Chamado criado com sucesso.')
            ->assertJsonPath('data.title', 'Impressora parada')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.responsible_user.id', $responsibleUser->id);

        $this->assertDatabaseHas('support_calls', [
            'title' => 'Impressora parada',
            'responsible_user_id' => $responsibleUser->id,
            'status' => 'open',
        ]);
    }

    public function test_it_always_creates_a_support_call_with_open_status(): void
    {
        $responsibleUser = User::factory()->create();

        $response = $this->postJson('/api/support-calls', [
            'title' => 'Servidor indisponivel',
            'description' => 'O servidor de arquivos nao responde.',
            'priority' => 'high',
            'status' => 'resolved',
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('support_calls', [
            'title' => 'Servidor indisponivel',
            'status' => 'open',
        ]);
    }

    public function test_it_automatically_assigns_the_support_call_to_the_user_with_fewer_calls(): void
    {
        $busiestUser = User::factory()->create();
        $availableUser = User::factory()->create();

        SupportCall::factory()->count(2)->create([
            'responsible_user_id' => $busiestUser->id,
        ]);
        SupportCall::factory()->create([
            'responsible_user_id' => $availableUser->id,
        ]);

        $response = $this->postJson('/api/support-calls', [
            'title' => 'Notebook sem internet',
            'description' => 'O notebook do suporte nao acessa a rede.',
            'priority' => 'medium',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.responsible_user.id', $availableUser->id)
            ->assertJsonPath('data.status', 'open');
    }

    public function test_it_breaks_assignment_ties_by_the_lowest_user_id(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        SupportCall::factory()->create(['responsible_user_id' => $firstUser->id]);
        SupportCall::factory()->create(['responsible_user_id' => $secondUser->id]);

        $response = $this->postJson('/api/support-calls', [
            'title' => 'Email fora do ar',
            'description' => 'O email da recepcao nao envia mensagens.',
            'priority' => 'low',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.responsible_user.id', min($firstUser->id, $secondUser->id));
    }

    public function test_it_returns_a_validation_error_when_there_are_no_users_for_automatic_assignment(): void
    {
        $response = $this->postJson('/api/support-calls', [
            'title' => 'Monitor apagado',
            'description' => 'O monitor do estoque nao liga.',
            'priority' => 'low',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.responsible_user_id.0', 'Nao ha responsaveis disponiveis para atribuicao automatica.');
    }

    public function test_it_validates_priority_and_responsible_user_id_on_creation(): void
    {
        $response = $this->postJson('/api/support-calls', [
            'title' => 'VPN instavel',
            'description' => 'A VPN cai a cada 10 minutos.',
            'priority' => 'urgent',
            'responsible_user_id' => 999,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.priority.0', 'A prioridade informada e invalida.')
            ->assertJsonPath('errors.responsible_user_id.0', 'O responsavel informado nao existe.');
    }

    public function test_it_shows_a_support_call_with_the_responsible_user(): void
    {
        $responsibleUser = User::factory()->create([
            'name' => 'Ana Souza',
            'email' => 'ana.souza@example.com',
        ]);

        $supportCall = SupportCall::factory()->create([
            'title' => 'Acesso ao sistema',
            'responsible_user_id' => $responsibleUser->id,
        ]);

        $response = $this->getJson("/api/support-calls/{$supportCall->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $supportCall->id)
            ->assertJsonPath('data.responsible_user.name', 'Ana Souza')
            ->assertJsonMissingPath('data.responsible_user.password');
    }

    public function test_it_requires_authentication_to_update_a_support_call(): void
    {
        $supportCall = SupportCall::factory()->create();

        $response = $this->putJson("/api/support-calls/{$supportCall->id}", [
            'status' => 'resolved',
        ]);

        $response->assertUnauthorized();
    }

    public function test_it_updates_a_support_call_when_authenticated(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $supportCall = SupportCall::factory()->create([
            'status' => 'open',
        ]);

        $response = $this->putJson("/api/support-calls/{$supportCall->id}", [
            'status' => 'in_progress',
            'priority' => 'low',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Chamado atualizado com sucesso.')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.priority', 'low');

        $this->assertDatabaseHas('support_calls', [
            'id' => $supportCall->id,
            'status' => 'in_progress',
            'priority' => 'low',
        ]);
    }

    public function test_it_rejects_an_invalid_status_transition_when_authenticated(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $supportCall = SupportCall::factory()->create([
            'status' => 'open',
        ]);

        $response = $this->putJson("/api/support-calls/{$supportCall->id}", [
            'status' => 'resolved',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.status.0', 'A mudanca de status informada nao e permitida.');

        $this->assertDatabaseHas('support_calls', [
            'id' => $supportCall->id,
            'status' => 'open',
        ]);
    }

    public function test_it_requires_authentication_to_delete_a_support_call(): void
    {
        $supportCall = SupportCall::factory()->create();

        $response = $this->deleteJson("/api/support-calls/{$supportCall->id}");

        $response->assertUnauthorized();
    }

    public function test_it_deletes_a_support_call_when_authenticated(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $supportCall = SupportCall::factory()->create();

        $response = $this->deleteJson("/api/support-calls/{$supportCall->id}");

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Chamado removido com sucesso.');

        $this->assertDatabaseMissing('support_calls', [
            'id' => $supportCall->id,
        ]);
    }
}
