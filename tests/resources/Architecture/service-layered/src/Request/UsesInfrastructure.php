<?php
declare(strict_types=1);

namespace App\Request;

final class UsesInfrastructure
{
    public function __construct(
        public \App\Infrastructure\CleanRepository $a,
    ) {
    }
}
