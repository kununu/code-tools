<?php
declare(strict_types=1);

namespace App\Domain;

final class UsesInputAndReadModelLayers
{
    public function __construct(
        public \App\Request\CleanRequest $a,
        public \App\Validator\CleanValidator $b,
        public \App\Security\CleanVoter $c,
        public \App\Projection\CleanProjection $d,
        public \App\Provider\CleanProvider $e,
        public \App\DataFixtures\CleanFixture $f,
    ) {
    }
}
