<?php

namespace Tests\Unit\Services;

use App\Models\SupportCall;
use App\Models\User;
use App\Repositories\Contracts\SupportCallRepositoryInterface;
use App\Services\SupportCallService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SupportCallServiceTest extends TestCase
{
    public function test_it_passes_list_filters_to_the_repository(): void
    {
        $supportCall = $this->makeSupportCallWithStatus('open');
        $paginator = new LengthAwarePaginator(collect([$supportCall]), 1, 25);

        $repository = Mockery::mock(SupportCallRepositoryInterface::class, function (MockInterface $mock) use ($paginator): void {
            $mock->shouldReceive('paginateWithResponsibleUser')
                ->once()
                ->with('status', 'asc', 25, 'open', 'high')
                ->andReturn($paginator);
        });

        $service = new SupportCallService($repository);

        $result = $service->listSupportCalls([
            'sort_by' => 'status',
            'sort_direction' => 'asc',
            'per_page' => '25',
            'status' => 'open',
            'priority' => 'high',
        ]);

        $this->assertSame(30, $result['data'][0]['id']);
    }

    public function test_it_uses_the_provided_responsible_user_when_creating_a_support_call(): void
    {
        Carbon::setTestNow('2026-06-17 12:00:00');

        $responsibleUser = (new User([
            'name' => 'Ana Souza',
            'email' => 'ana.souza@example.com',
        ]))->forceFill([
            'id' => 5,
        ]);

        $createdSupportCall = (new SupportCall([
            'title' => 'Rede indisponivel',
            'description' => 'Sem acesso a internet.',
            'priority' => 'high',
            'status' => 'open',
            'responsible_user_id' => 5,
            'opened_at' => Carbon::now(),
        ]))->forceFill([
            'id' => 10,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $createdSupportCall->setRelation('responsibleUser', $responsibleUser);

        $repository = Mockery::mock(SupportCallRepositoryInterface::class, function (MockInterface $mock) use ($createdSupportCall): void {
            $mock->shouldReceive('create')
                ->once()
                ->with([
                    'title' => 'Rede indisponivel',
                    'description' => 'Sem acesso a internet.',
                    'priority' => 'high',
                    'status' => 'open',
                    'responsible_user_id' => 5,
                    'opened_at' => Carbon::now(),
                ])
                ->andReturn($createdSupportCall);

            $mock->shouldReceive('findByIdWithResponsibleUser')
                ->once()
                ->with(10)
                ->andReturn($createdSupportCall);
        });

        $service = new SupportCallService($repository);

        $result = $service->createSupportCall([
            'title' => 'Rede indisponivel',
            'description' => 'Sem acesso a internet.',
            'priority' => 'high',
            'responsible_user_id' => 5,
        ]);

        $this->assertSame(5, $result['responsible_user']['id']);

        Carbon::setTestNow();
    }

    public function test_it_always_creates_a_support_call_with_open_status(): void
    {
        Carbon::setTestNow('2026-06-17 12:00:00');

        $responsibleUser = (new User([
            'name' => 'Ana Souza',
            'email' => 'ana.souza@example.com',
        ]))->forceFill([
            'id' => 5,
        ]);

        $createdSupportCall = (new SupportCall([
            'title' => 'Rede indisponivel',
            'description' => 'Sem acesso a internet.',
            'priority' => 'high',
            'status' => 'open',
            'responsible_user_id' => 5,
            'opened_at' => Carbon::now(),
        ]))->forceFill([
            'id' => 12,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $createdSupportCall->setRelation('responsibleUser', $responsibleUser);

        $repository = Mockery::mock(SupportCallRepositoryInterface::class, function (MockInterface $mock) use ($createdSupportCall): void {
            $mock->shouldReceive('create')
                ->once()
                ->with([
                    'title' => 'Rede indisponivel',
                    'description' => 'Sem acesso a internet.',
                    'priority' => 'high',
                    'status' => 'open',
                    'responsible_user_id' => 5,
                    'opened_at' => Carbon::now(),
                ])
                ->andReturn($createdSupportCall);

            $mock->shouldReceive('findByIdWithResponsibleUser')
                ->once()
                ->with(12)
                ->andReturn($createdSupportCall);
        });

        $service = new SupportCallService($repository);

        $result = $service->createSupportCall([
            'title' => 'Rede indisponivel',
            'description' => 'Sem acesso a internet.',
            'priority' => 'high',
            'status' => 'resolved',
            'responsible_user_id' => 5,
        ]);

        $this->assertSame('open', $result['status']);

        Carbon::setTestNow();
    }

    public function test_it_automatically_assigns_the_user_with_fewer_support_calls(): void
    {
        Carbon::setTestNow('2026-06-17 12:00:00');

        $responsibleUser = (new User([
            'name' => 'Bruno Lima',
            'email' => 'bruno.lima@example.com',
        ]))->forceFill([
            'id' => 3,
        ]);

        $createdSupportCall = (new SupportCall([
            'title' => 'Mouse falhando',
            'description' => 'O mouse desconecta sozinho.',
            'priority' => 'medium',
            'status' => 'open',
            'responsible_user_id' => 3,
            'opened_at' => Carbon::now(),
        ]))->forceFill([
            'id' => 11,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        $createdSupportCall->setRelation('responsibleUser', $responsibleUser);

        $repository = Mockery::mock(SupportCallRepositoryInterface::class, function (MockInterface $mock) use ($responsibleUser, $createdSupportCall): void {
            $mock->shouldReceive('findUserWithFewestSupportCalls')
                ->once()
                ->andReturn($responsibleUser);

            $mock->shouldReceive('create')
                ->once()
                ->with([
                    'title' => 'Mouse falhando',
                    'description' => 'O mouse desconecta sozinho.',
                    'priority' => 'medium',
                    'status' => 'open',
                    'responsible_user_id' => 3,
                    'opened_at' => Carbon::now(),
                ])
                ->andReturn($createdSupportCall);

            $mock->shouldReceive('findByIdWithResponsibleUser')
                ->once()
                ->with(11)
                ->andReturn($createdSupportCall);
        });

        $service = new SupportCallService($repository);

        $result = $service->createSupportCall([
            'title' => 'Mouse falhando',
            'description' => 'O mouse desconecta sozinho.',
            'priority' => 'medium',
        ]);

        $this->assertSame(3, $result['responsible_user']['id']);

        Carbon::setTestNow();
    }

    public function test_it_returns_only_public_fields_for_the_responsible_user(): void
    {
        $responsibleUser = (new User([
            'name' => 'Carlos Lima',
            'email' => 'carlos.lima@example.com',
            'password' => 'Password123!',
        ]))->forceFill([
            'id' => 9,
        ]);

        $supportCall = (new SupportCall([
            'title' => 'Telefone mudo',
            'description' => 'Nao realiza chamadas.',
            'priority' => 'low',
            'status' => 'resolved',
            'opened_at' => Carbon::parse('2026-06-17 08:00:00'),
        ]))->forceFill([
            'id' => 20,
            'responsible_user_id' => 9,
            'created_at' => Carbon::parse('2026-06-17 08:00:00'),
            'updated_at' => Carbon::parse('2026-06-17 09:00:00'),
        ]);
        $supportCall->setRelation('responsibleUser', $responsibleUser);

        $repository = Mockery::mock(SupportCallRepositoryInterface::class, function (MockInterface $mock) use ($supportCall): void {
            $mock->shouldReceive('findByIdWithResponsibleUser')
                ->once()
                ->with(20)
                ->andReturn($supportCall);
        });

        $service = new SupportCallService($repository);

        $result = $service->showSupportCall(20);

        $this->assertSame([
            'id' => 9,
            'name' => 'Carlos Lima',
            'email' => 'carlos.lima@example.com',
        ], $result['responsible_user']);
        $this->assertArrayNotHasKey('password', $result['responsible_user']);
    }

    public function test_it_allows_transition_from_open_to_in_progress(): void
    {
        $supportCall = $this->makeSupportCallWithStatus('open');
        $updatedSupportCall = $this->makeSupportCallWithStatus('in_progress');

        $repository = Mockery::mock(SupportCallRepositoryInterface::class, function (MockInterface $mock) use ($supportCall, $updatedSupportCall): void {
            $mock->shouldReceive('findByIdWithResponsibleUser')
                ->once()
                ->with(30)
                ->andReturn($supportCall);

            $mock->shouldReceive('update')
                ->once()
                ->with($supportCall, ['status' => 'in_progress'])
                ->andReturn($updatedSupportCall);
        });

        $service = new SupportCallService($repository);

        $result = $service->updateSupportCall(30, ['status' => 'in_progress']);

        $this->assertSame('in_progress', $result['status']);
    }

    public function test_it_allows_transition_from_open_to_closed(): void
    {
        $supportCall = $this->makeSupportCallWithStatus('open');
        $updatedSupportCall = $this->makeSupportCallWithStatus('closed');

        $repository = Mockery::mock(SupportCallRepositoryInterface::class, function (MockInterface $mock) use ($supportCall, $updatedSupportCall): void {
            $mock->shouldReceive('findByIdWithResponsibleUser')
                ->once()
                ->with(30)
                ->andReturn($supportCall);

            $mock->shouldReceive('update')
                ->once()
                ->with($supportCall, ['status' => 'closed'])
                ->andReturn($updatedSupportCall);
        });

        $service = new SupportCallService($repository);

        $result = $service->updateSupportCall(30, ['status' => 'closed']);

        $this->assertSame('closed', $result['status']);
    }

    public function test_it_allows_transition_from_in_progress_to_resolved(): void
    {
        $supportCall = $this->makeSupportCallWithStatus('in_progress');
        $updatedSupportCall = $this->makeSupportCallWithStatus('resolved');

        $repository = Mockery::mock(SupportCallRepositoryInterface::class, function (MockInterface $mock) use ($supportCall, $updatedSupportCall): void {
            $mock->shouldReceive('findByIdWithResponsibleUser')
                ->once()
                ->with(30)
                ->andReturn($supportCall);

            $mock->shouldReceive('update')
                ->once()
                ->with($supportCall, ['status' => 'resolved'])
                ->andReturn($updatedSupportCall);
        });

        $service = new SupportCallService($repository);

        $result = $service->updateSupportCall(30, ['status' => 'resolved']);

        $this->assertSame('resolved', $result['status']);
    }

    public function test_it_allows_transition_from_in_progress_to_closed(): void
    {
        $supportCall = $this->makeSupportCallWithStatus('in_progress');
        $updatedSupportCall = $this->makeSupportCallWithStatus('closed');

        $repository = Mockery::mock(SupportCallRepositoryInterface::class, function (MockInterface $mock) use ($supportCall, $updatedSupportCall): void {
            $mock->shouldReceive('findByIdWithResponsibleUser')
                ->once()
                ->with(30)
                ->andReturn($supportCall);

            $mock->shouldReceive('update')
                ->once()
                ->with($supportCall, ['status' => 'closed'])
                ->andReturn($updatedSupportCall);
        });

        $service = new SupportCallService($repository);

        $result = $service->updateSupportCall(30, ['status' => 'closed']);

        $this->assertSame('closed', $result['status']);
    }

    public function test_it_rejects_invalid_status_transitions(): void
    {
        $repository = Mockery::mock(SupportCallRepositoryInterface::class);
        $service = new SupportCallService($repository);

        $invalidTransitions = [
            ['current' => 'open', 'next' => 'resolved'],
            ['current' => 'in_progress', 'next' => 'open'],
            ['current' => 'resolved', 'next' => 'closed'],
            ['current' => 'closed', 'next' => 'open'],
        ];

        foreach ($invalidTransitions as $index => $transition) {
            $supportCall = $this->makeSupportCallWithStatus($transition['current'], 40 + $index);

            $repository->shouldReceive('findByIdWithResponsibleUser')
                ->once()
                ->with(40 + $index)
                ->andReturn($supportCall);

            try {
                $service->updateSupportCall(40 + $index, ['status' => $transition['next']]);
                $this->fail('Expected invalid status transition to throw validation exception.');
            } catch (ValidationException $exception) {
                $this->assertSame(
                    ['A mudanca de status informada nao e permitida.'],
                    $exception->errors()['status'] ?? [],
                );
            }
        }
    }

    private function makeSupportCallWithStatus(string $status, int $id = 30): SupportCall
    {
        $responsibleUser = (new User([
            'name' => 'Carlos Lima',
            'email' => 'carlos.lima@example.com',
        ]))->forceFill([
            'id' => 9,
        ]);

        $supportCall = (new SupportCall([
            'title' => 'Telefone mudo',
            'description' => 'Nao realiza chamadas.',
            'priority' => 'low',
            'status' => $status,
            'opened_at' => Carbon::parse('2026-06-17 08:00:00'),
        ]))->forceFill([
            'id' => $id,
            'responsible_user_id' => 9,
            'created_at' => Carbon::parse('2026-06-17 08:00:00'),
            'updated_at' => Carbon::parse('2026-06-17 09:00:00'),
        ]);
        $supportCall->setRelation('responsibleUser', $responsibleUser);

        return $supportCall;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }
}
