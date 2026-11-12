<?php
declare(strict_types=1);

namespace App\Infrastructure;

final class UsesEntrypointAndReadModelLayers
{
    public function __construct(
        public \App\Request\CleanRequest $a,
        public \App\Validator\CleanValidator $b,
        public \App\Security\CleanVoter $c,
        public \App\Provider\CleanProvider $d,
        public \App\Projection\CleanProjection $e,
        public \Symfony\Component\Console\ArchitectureFixture $f,
        public \Symfony\Component\HttpKernel\ArchitectureFixture $g,
        public \Symfony\Component\Routing\ArchitectureFixture $h,
    ) {
    }
}
