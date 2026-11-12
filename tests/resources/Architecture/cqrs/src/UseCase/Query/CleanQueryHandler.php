<?php
declare(strict_types=1);

namespace App\UseCase\Query;

final class CleanQueryHandler
{
    public function __construct(
        public \App\Domain\CleanEntity $a,
    ) {
    }
}
