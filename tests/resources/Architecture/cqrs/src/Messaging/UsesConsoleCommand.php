<?php
declare(strict_types=1);

namespace App\Messaging;

final class UsesConsoleCommand
{
    public function __construct(
        public \App\Command\CleanCommand $a,
    ) {
    }
}
