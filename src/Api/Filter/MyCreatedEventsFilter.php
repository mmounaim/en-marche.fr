<?php

namespace App\Api\Filter;

use ApiPlatform\Doctrine\Orm\Filter\AbstractFilter;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Event\BaseEvent;
use App\Scope\ScopeGeneratorResolver;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;

final class MyCreatedEventsFilter extends AbstractFilter
{
    private const PROPERTY_NAME = 'only_mine';

    private Security $security;
    private ScopeGeneratorResolver $scopeGeneratorResolver;

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ) {
        if (
            BaseEvent::class !== $resourceClass
            || self::PROPERTY_NAME !== $property
            || !$user = $this->security->getUser()
        ) {
            return;
        }

        $scope = $this->scopeGeneratorResolver->generate();
        $user = $scope && $scope->getDelegatedAccess() ? $scope->getDelegator() : $user;

        $alias = $queryBuilder->getRootAliases()[0];
        $queryBuilder
            ->andWhere(sprintf('%s.organizer = :organizer', $alias))
            ->setParameter('organizer', $user)
        ;
    }

    public function getDescription(string $resourceClass): array
    {
        return [
            self::PROPERTY_NAME => [
                'property' => null,
                'type' => 'string',
                'required' => false,
            ],
        ];
    }

    /** @required */
    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    /** @required */
    public function setScopeGeneratorResolver(ScopeGeneratorResolver $scopeGeneratorResolver): void
    {
        $this->scopeGeneratorResolver = $scopeGeneratorResolver;
    }
}
