<?php
declare(strict_types=1);

namespace App\Service;

final class UsesTests
{
    public function __construct(
        public \App\Tests\ArchitectureFixture $a,
    ) {
    }
}
