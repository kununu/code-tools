<?php
declare(strict_types=1);

namespace App\Service;

final class CleanService
{
    public function __construct(
        public \App\Domain\CleanEntity $a,
    ) {
    }
}
