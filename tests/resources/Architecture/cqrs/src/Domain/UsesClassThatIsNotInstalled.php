<?php
declare(strict_types=1);

namespace App\Domain;

final class UsesClassThatIsNotInstalled
{
    public function __construct(
        public \Symfony\Component\HttpFoundation\NotInstalled $a,
    ) {
    }
}
