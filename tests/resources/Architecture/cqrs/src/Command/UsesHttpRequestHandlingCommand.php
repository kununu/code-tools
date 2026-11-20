<?php
declare(strict_types=1);

namespace App\Command;

final class UsesHttpRequestHandlingCommand
{
    public function __construct(
        public \Symfony\Component\HttpFoundation\ArchitectureFixture $a,
        public \Symfony\Component\HttpKernel\ArchitectureFixture $b,
        public \Symfony\Component\Routing\ArchitectureFixture $c,
        public \App\Request\CleanRequest $d,
        public \App\Validator\CleanValidator $e,
        public \App\Security\CleanVoter $f,
    ) {
    }
}
