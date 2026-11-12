<?php
declare(strict_types=1);

namespace App\Domain;

final class UsesHttpConsoleAndBundles
{
    public function __construct(
        public \Symfony\Component\HttpFoundation\ArchitectureFixture $a,
        public \Symfony\Component\HttpKernel\ArchitectureFixture $b,
        public \Symfony\Bundle\FrameworkBundle\ArchitectureFixture $c,
        public \Symfony\Component\Console\ArchitectureFixture $d,
    ) {
    }
}
