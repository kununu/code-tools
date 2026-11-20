<?php
declare(strict_types=1);

namespace App\Controller;

final class CleanController extends AbstractBaseController
{
    public function __construct(
        public \App\UseCase\Query\CleanQueryHandler $a,
        public \Symfony\Component\HttpFoundation\ArchitectureFixture $b,
    ) {
    }
}
