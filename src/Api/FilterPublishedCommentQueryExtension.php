<?php

namespace App\Api;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Comment;
use App\Enum\PublishState;
use Doctrine\ORM\QueryBuilder;

class FilterPublishedCommentQueryExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->applyPublishedCommentFilter($resourceClass, $queryBuilder);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->applyPublishedCommentFilter($resourceClass, $queryBuilder);
    }

    private function applyPublishedCommentFilter(string $resourceClass, QueryBuilder $queryBuilder): void
    {
        if (Comment::class === $resourceClass) {
            $entityAlias = $queryBuilder->getRootAliases()[0];

            $queryBuilder->andWhere(sprintf("%s.state = :publishedState", $entityAlias))
                ->setParameter('publishedState', PublishState::Published);
        }
    }
}
