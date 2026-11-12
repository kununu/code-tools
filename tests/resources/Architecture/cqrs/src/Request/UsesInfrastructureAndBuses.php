<?php
declare(strict_types=1);

namespace App\Request;

final class UsesInfrastructureAndBuses
{
    public function __construct(
        public \App\Infrastructure\CleanRepository $a,
        public \App\Messaging\CleanProducer $b,
        public \App\Command\CleanCommand $c,
        public \Doctrine\DBAL\ArchitectureFixture $d,
        public \Kununu\CQRS\ArchitectureFixture $e,
    ) {
    }
}
