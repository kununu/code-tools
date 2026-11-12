<?php
declare(strict_types=1);

namespace App\Service;

final class UsesController
{
    public function __construct(
        public \App\Controller\CleanController $a,
    ) {
    }
}
