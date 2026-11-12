<?php
declare(strict_types=1);

namespace App\Domain;

final class UsesConsoleCommand
{
    public function __construct(
        public \App\Command\CleanCommand $a,
    ) {
    }
}
