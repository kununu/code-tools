<?php
declare(strict_types=1);

namespace App\Command;

final class CleanCommand
{
    public function __construct(
        public \App\UseCase\Command\CleanCommandHandler $a,
        public \Symfony\Component\Console\ArchitectureFixture $b,
    ) {
    }
}
