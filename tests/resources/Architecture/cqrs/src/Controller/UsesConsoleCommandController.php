<?php
declare(strict_types=1);

namespace App\Controller;

final class UsesConsoleCommandController
{
    public function __construct(
        public \App\Command\CleanCommand $a,
    ) {
    }
}
