<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Application;

use PayIn\Application\Result\ChargeOutcome;
use PayIn\Application\Result\ChargeResult;
use PHPUnit\Framework\TestCase;

final class ChargeResultTest extends TestCase
{
    public function test_success_factory(): void
    {
        $result = ChargeResult::success('FP-1', 'approved', ['auth' => 'X']);

        $this->assertSame(ChargeOutcome::SUCCESS, $result->outcome);
        $this->assertSame('FP-1', $result->providerTransactionId);
        $this->assertTrue($result->isSuccess());
        $this->assertNull($result->errorCode);
    }

    public function test_rejected_factory(): void
    {
        $result = ChargeResult::rejected('PROVIDER_REJECTED', 'Fondos insuficientes');

        $this->assertSame(ChargeOutcome::REJECTED, $result->outcome);
        $this->assertSame('PROVIDER_REJECTED', $result->errorCode);
        $this->assertFalse($result->isSuccess());
        $this->assertNull($result->providerTransactionId);
    }

    public function test_timeout_factory(): void
    {
        $result = ChargeResult::timeout('Sin respuesta');

        $this->assertSame(ChargeOutcome::TIMEOUT, $result->outcome);
        $this->assertNull($result->errorCode);
        $this->assertSame('Sin respuesta', $result->message);
    }

    public function test_error_factory(): void
    {
        $result = ChargeResult::error('PROVIDER_ERROR', 'Fallo interno');

        $this->assertSame(ChargeOutcome::ERROR, $result->outcome);
        $this->assertSame('PROVIDER_ERROR', $result->errorCode);
    }

    public function test_is_immutable(): void
    {
        $result = ChargeResult::success('FP-1');

        $this->assertSame(['payload' => []], ['payload' => $result->payload]);
    }
}
