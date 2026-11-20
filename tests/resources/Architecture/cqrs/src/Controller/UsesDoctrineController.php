<?php
declare(strict_types=1);

namespace App\Controller;

final class UsesDoctrineController
{
    public function __construct(
        public \Doctrine\DBAL\ArchitectureFixture $a,
    ) {
    }
}
