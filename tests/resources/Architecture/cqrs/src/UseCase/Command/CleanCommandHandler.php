<?php
declare(strict_types=1);

namespace App\UseCase\Command;

final class CleanCommandHandler
{
    public function __construct(
        public \App\Domain\CleanEntity $a,
    ) {
    }
}
