<?php
declare(strict_types=1);

namespace App\UseCase\Query;

final class UsesCommand
{
    public function __construct(
        public \App\UseCase\Command\CleanCommandHandler $a,
    ) {
    }
}
