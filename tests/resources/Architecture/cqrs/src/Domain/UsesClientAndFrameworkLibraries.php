<?php
declare(strict_types=1);

namespace App\Domain;

final class UsesClientAndFrameworkLibraries
{
    public function __construct(
        public \Aws\ArchitectureFixture $a,
        public \GuzzleHttp\ArchitectureFixture $b,
        public \Elasticsearch\ArchitectureFixture $c,
        public \Elastic\Elasticsearch\ArchitectureFixture $d,
        public \OpenSearch\ArchitectureFixture $e,
        public \Kununu\Elasticsearch\ArchitectureFixture $f,
        public \Kununu\Doctrine\ArchitectureFixture $g,
        public \Kununu\Hermes\ArchitectureFixture $h,
        public \League\Tactician\ArchitectureFixture $i,
        public \Symfony\Component\Routing\ArchitectureFixture $j,
        public \Symfony\Component\Security\ArchitectureFixture $k,
        public \Symfony\Component\Serializer\ArchitectureFixture $l,
        public \Symfony\Component\DependencyInjection\ArchitectureFixture $m,
        public \Symfony\Component\EventDispatcher\ArchitectureFixture $n,
    ) {
    }
}
