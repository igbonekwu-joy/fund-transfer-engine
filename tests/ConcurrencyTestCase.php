<?php

namespace Tests;

/**
 * Feature tests that must commit so sibling PHP processes can see rows
 * and contend on real database locks (FOR UPDATE).
 */
abstract class ConcurrencyTestCase extends TestCase
{
    /**
     * @var list<string>
     */
    protected $connectionsToTransact = [];
}
