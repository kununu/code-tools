<?php
declare(strict_types=1);

namespace App\UseCase\Command;

final class UsesEntrypointsFixturesAndSerializer
{
    public function __construct(
        public \App\Command\CleanCommand $a,
        public \App\DataFixtures\CleanFixture $b,
        public \App\Security\CleanVoter $c,
        public \Symfony\Component\Console\ArchitectureFixture $d,
        public \Symfony\Component\Routing\ArchitectureFixture $e,
        public \Symfony\Component\Serializer\ArchitectureFixture $f,
    ) {
    }
}
