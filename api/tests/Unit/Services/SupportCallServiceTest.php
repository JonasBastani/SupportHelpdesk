<?php

namespace Tests\Unit\Services;

use App\Models\SupportCall;
use App\Models\User;
use App\Repositories\Contracts\SupportCallRepositoryInterface;
use App\Services\SupportCallService;
use Illuminate\Support\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SupportCallServiceTest extends TestCase
{
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }
}
