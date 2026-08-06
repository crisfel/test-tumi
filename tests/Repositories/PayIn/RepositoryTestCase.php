<?php

declare(strict_types=1);

namespace Tests\Repositories\PayIn;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class RepositoryTestCase extends TestCase
{
    use RefreshDatabase;
}
