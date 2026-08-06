<?php

declare(strict_types=1);

namespace Tests\Unit\PayIn\Domain;

use PayIn\Domain\PayIn\PayInStatus;
use PHPUnit\Framework\TestCase;

final class PayInStatusTest extends TestCase
{
    public function test_created_allows_expected_transitions(): void
    {
        $this->assertSame(
            [PayInStatus::VALIDATED, PayInStatus::PROCESSING, PayInStatus::FAILED],
            PayInStatus::CREATED->transitions(),
        );
    }

    public function test_validated_allows_expected_transitions(): void
    {
        $this->assertSame(
            [PayInStatus::PROCESSING, PayInStatus::FAILED],
            PayInStatus::VALIDATED->transitions(),
        );
    }

    public function test_processing_allows_expected_transitions(): void
    {
        $this->assertSame(
            [PayInStatus::PROCESSED, PayInStatus::FAILED],
            PayInStatus::PROCESSING->transitions(),
        );
    }

    public function test_terminal_states_do_not_allow_transitions(): void
    {
        $this->assertSame([], PayInStatus::PROCESSED->transitions());
        $this->assertSame([], PayInStatus::FAILED->transitions());
        $this->assertTrue(PayInStatus::PROCESSED->isTerminal());
        $this->assertTrue(PayInStatus::FAILED->isTerminal());
        $this->assertFalse(PayInStatus::CREATED->isTerminal());
    }

    public function test_can_transition_to_valid_target(): void
    {
        $this->assertTrue(PayInStatus::CREATED->canTransitionTo(PayInStatus::VALIDATED));
    }

    public function test_cannot_skip_states(): void
    {
        $this->assertFalse(PayInStatus::CREATED->canTransitionTo(PayInStatus::PROCESSED));
        $this->assertFalse(PayInStatus::VALIDATED->canTransitionTo(PayInStatus::PROCESSED));
        $this->assertFalse(PayInStatus::CREATED->canTransitionTo(PayInStatus::CREATED));
    }
}
