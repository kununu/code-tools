<?php
declare(strict_types=1);

namespace App\Infrastructure;

final class CleanRepository
{
    public function __construct(
        public \App\Domain\CleanEntity $a,
        public \Doctrine\ORM\ArchitectureFixture $b,
    ) {
    }
}
