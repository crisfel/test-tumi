<?php

declare(strict_types=1);

namespace Tests\Feature\PayIn;

use PayIn\Application\Exception\ClientAlreadyExistsException;
use PayIn\Domain\Email;
use PayIn\Infrastructure\Persistence\Eloquent\Mappers\ClientMapper;
use PayIn\Infrastructure\Persistence\Eloquent\Repositories\EloquentClientRepository;

final class StoreClientApiTest extends PayInApiTestCase
{
    public function test_registers_a_client_successfully(): void
    {
        $response = $this->postJson('/api/v1/clients', [
            'name' => 'Carlos Rodríguez',
            'email' => 'carlos.rodriguez@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'name', 'email']])
            ->assertJsonPath('data.name', 'Carlos Rodríguez')
            ->assertJsonPath('data.email', 'carlos.rodriguez@example.com');

        $this->assertDatabaseHas('clients', [
            'id' => $response->json('data.id'),
            'email' => 'carlos.rodriguez@example.com',
        ]);
    }

    public function test_returns_409_when_email_is_already_registered(): void
    {
        $this->postJson('/api/v1/clients', [
            'name' => 'Carlos',
            'email' => 'carlos@example.com',
        ]);

        $response = $this->postJson('/api/v1/clients', [
            'name' => 'Carlos Dup',
            'email' => 'carlos@example.com',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('errors.0.code', 'CLIENT_ALREADY_EXISTS');
    }

    public function test_returns_409_on_race_condition_via_database_unique(): void
    {
        // El setUp ya creó ana@example.com; el UNIQUE de BD debe traducirse
        // a ClientAlreadyExistsException aunque el chequeo de aplicación
        // no se ejecute (carrera entre peticiones concurrentes).
        $repository = new EloquentClientRepository(new ClientMapper());

        $this->expectException(ClientAlreadyExistsException::class);

        $duplicate = \PayIn\Domain\Client\Client::register(
            \PayIn\Domain\Client\ClientId::generate(),
            'Duplicado',
            Email::fromString('ana@example.com'),
        );

        $repository->save($duplicate);
    }

    public function test_rejects_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/clients', [
            'name' => 'Carlos',
            'email' => 'no-es-un-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
    }

    public function test_rejects_email_with_crlf_injection(): void
    {
        $response = $this->postJson('/api/v1/clients', [
            'name' => 'Carlos',
            'email' => "carlos@example.com\r\nBcc: victima@example.com",
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.0.code', 'VALIDATION_ERROR');
    }

    public function test_rejects_empty_name(): void
    {
        $response = $this->postJson('/api/v1/clients', [
            'name' => '',
            'email' => 'carlos@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_name_longer_than_100_characters(): void
    {
        $response = $this->postJson('/api/v1/clients', [
            'name' => str_repeat('a', 101),
            'email' => 'carlos@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/clients', []);

        $response->assertStatus(422);
    }
}
