<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Domain;

use PayIn\Domain\Account\AccountId;
use PayIn\Domain\Client\ClientId;
use PayIn\Shared\Kernel\Exceptions\InvalidUuidException;
use PayIn\Shared\Uuid\TypedId;
use PHPUnit\Framework\TestCase;

final class TypedIdTest extends TestCase
{
    public function test_generates_uuid_v7(): void
    {
        $id = ClientId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $id->toString(),
        );
    }

    public function test_builds_from_string(): void
    {
        $value = '018f0000-0000-7000-8000-000000000001';

        $this->assertSame($value, ClientId::fromString($value)->toString());
    }

    public function test_rejects_invalid_uuid(): void
    {
        $this->expectException(InvalidUuidException::class);

        ClientId::fromString('not-a-uuid');
    }

    public function test_equality_requires_same_type(): void
    {
        $value = '018f0000-0000-7000-8000-000000000001';

        $this->assertTrue(ClientId::fromString($value)->equals(ClientId::fromString($value)));
        $this->assertFalse(ClientId::fromString($value)->equals(AccountId::fromString($value)));
    }

    public function test_ids_are_stringable_and_serializable(): void
    {
        $id = ClientId::generate();

        $this->assertSame($id->toString(), (string) $id);
        $this->assertSame($id->toString(), $id->jsonSerialize());
        $this->assertInstanceOf(TypedId::class, $id);
    }
}
