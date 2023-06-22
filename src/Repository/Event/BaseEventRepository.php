<?php

namespace App\Repository\Event;

use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Entity\Adherent;
use App\Entity\Coalition\Coalition;
use App\Entity\Event\BaseEvent;
use App\Entity\Event\BaseEventCategory;
use App\Entity\Event\CauseEvent;
use App\Entity\Event\CoalitionEvent;
use App\Entity\Event\CommitteeEvent;
use App\Entity\Event\DefaultEvent;
use App\Entity\Event\EventRegistration;
use App\Entity\Geo\Zone;
use App\Event\EventTypeEnum;
use App\Event\ListFilter;
use App\Repository\GeoZoneTrait;
use App\Repository\PaginatorTrait;
use App\Repository\UuidEntityRepositoryTrait;
use Cake\Chronos\Chronos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

class BaseEventRepository extends ServiceEntityRepository
{
    use UuidEntityRepositoryTrait;
    use PaginatorTrait;
    use GeoZoneTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BaseEvent::class);
    }

    public function findOnePublishedBySlug(string $slug): ?BaseEvent
    {
        return $this
            ->createSlugQueryBuilder($slug)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @param Zone[] $zones
     *
     * @return BaseEvent[]|PaginatorInterface
     */
    public function findManagedByPaginator(array $zones, int $page = 1, int $limit = 50): PaginatorInterface
    {
        $qb = $this->createQueryBuilder('event')
            ->select('event', 'organizer')
            ->leftJoin('event.organizer', 'organizer')
            ->where('event.published = :published')
            ->where('event.renaissanceEvent = :re_event')
            ->orderBy('event.beginAt', 'DESC')
            ->addOrderBy('event.name', 'ASC')
            ->setParameter('published', true)
            ->setParameter('re_event', false)
        ;

        $this->withGeoZones(
            $zones,
            $qb,
            'event',
            BaseEvent::class,
            'e2',
            'zones',
            'z2',
            function (QueryBuilder $zoneQueryBuilder, string $entityClassAlias) {
                $zoneQueryBuilder->andWhere(sprintf('%s.published = :published', $entityClassAlias));
            }
        );

        return $this->configurePaginator($qb, $page, $limit);
    }

    /**
     * @return DefaultEvent[]|PaginatorInterface
     */
    public function findEventsByOrganizerPaginator(
        Adherent $organizer,
        int $page = 1,
        int $limit = 50
    ): PaginatorInterface {
        $qb = $this
            ->createQueryBuilder('event')
            ->andWhere('event.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('event.createdAt', 'DESC')
        ;

        return $this->configurePaginator($qb, $page, $limit);
    }

    public function findOneActiveBySlug(string $slug): ?BaseEvent
    {
        return $this
            ->createSlugQueryBuilder($slug)
            ->andWhere('event.status IN (:statuses)')
            ->setParameter('statuses', CommitteeEvent::ACTIVE_STATUSES)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findWithRegistrationByUuids(array $uuids, UserInterface $user): array
    {
        self::validUuids($uuids);

        return $this->createQueryBuilder('event')
            ->innerJoin(EventRegistration::class, 'registration', Join::WITH, 'registration.event = event')
            ->andWhere('registration.adherentUuid = :adherent_uuid')
            ->andWhere('event.uuid IN (:uuids)')
            ->setParameter('adherent_uuid', $user->getUuidAsString())
            ->setParameter('uuids', $uuids)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findEventsForCoalition(Coalition $coalition, int $page = 1, int $limit = 30): PaginatorInterface
    {
        $subCoalitionEventsQuery = $this->getEntityManager()->createQueryBuilder()
            ->from(CoalitionEvent::class, 'coe')
            ->select('coe.id')
            ->where('coe.coalition = :coalition')
            ->getDQL()
        ;

        $subCauseEventsQuety = $this->getEntityManager()->createQueryBuilder()
            ->from(CauseEvent::class, 'cae')
            ->select('cae.id')
            ->leftJoin('cae.cause', 'cause')
            ->where('cause.coalition = :coalition')
            ->getDQL()
        ;

        $qb = $this->createQueryBuilder('e')
            ->addSelect('IF(e INSTANCE OF :coalition_type, IF(e.beginAt > :now, 3, 1), IF(e.beginAt > :now, 2, 0)) as HIDDEN custom_order')
            ->leftJoin('e.organizer', 'organizer')
            ->andWhere((new Orx())
                ->add(sprintf('e.id IN (%s)', $subCoalitionEventsQuery))
                ->add(sprintf('e.id IN (%s)', $subCauseEventsQuety))
            )
            ->andWhere('e.published = :true')
            ->andWhere('e.status IN (:statuses)')
            ->setParameters([
                'true' => true,
                'statuses' => BaseEvent::ACTIVE_STATUSES,
                'coalition' => $coalition,
                'coalition_type' => EventTypeEnum::TYPE_COALITION,
                'now' => new Chronos('now'),
            ])
            ->orderBy('custom_order', 'DESC')
            ->addOrderBy('e.beginAt', 'DESC')
        ;

        return $this->configurePaginator($qb, $page, $limit);
    }

    protected function createSlugQueryBuilder(string $slug): QueryBuilder
    {
        return $this
            ->createQueryBuilder('event')
            ->select('event', 'organizer')
            ->leftJoin('event.organizer', 'organizer')
            ->where('event.slug = :slug')
            ->andWhere('event.published = :published')
            ->setParameters([
                'published' => true,
                'slug' => $slug,
            ])
        ;
    }

    public function findEventsToRemind(
        \DateTimeInterface $startAfter,
        \DateTimeInterface $startBefore,
        string $mode = null
    ): array {
        $qb = $this
            ->createQueryBuilder('event')
            ->andWhere((new Orx())
                ->add(sprintf('event INSTANCE OF %s', DefaultEvent::class))
                ->add(sprintf('event INSTANCE OF %s', CommitteeEvent::class))
            )
            ->andWhere('event.beginAt >= :start_after')
            ->andWhere('event.beginAt < :start_before')
            ->andWhere('event.reminded = :false')
            ->setParameters([
                'start_after' => $startAfter,
                'start_before' => $startBefore,
                'false' => false,
            ])
        ;

        if ($mode) {
            $qb
                ->andWhere('event.mode = :mode')
                ->setParameter('mode', $mode)
            ;
        }

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllByFilter(ListFilter $filter, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('event');

        $qb
            ->leftJoin('event.category', 'category')
            ->where('event.published = :published')
            ->andWhere('event.renaissanceEvent = :re_event')
            ->andWhere((new Orx())
                ->add(sprintf('event INSTANCE OF %s', DefaultEvent::class))
                ->add(sprintf('event INSTANCE OF %s', CommitteeEvent::class))
            )
            ->andWhere($qb->expr()->in('event.status', BaseEvent::ACTIVE_STATUSES))
            ->andWhere('event.beginAt >= CONVERT_TZ(:now, \'Europe/Paris\', event.timeZone)')
            ->andWhere('category IS NULL OR category.status = :status')
            ->orderBy('event.beginAt', 'ASC')
            ->addOrderBy('event.name', 'ASC')
            ->setParameter('published', true)
            ->setParameter('re_event', true)
            ->setParameter('now', new \DateTime('now'))
            ->setParameter('status', BaseEventCategory::ENABLED)
            ->setMaxResults($limit)
        ;

        if ($name = $filter->getName()) {
            $qb
                ->andWhere('event.name LIKE :name')
                ->setParameter('name', '%'.$name.'%')
            ;
        }

        if ($category = $filter->getCategory()) {
            $qb
                ->andWhere('category = :category')
                ->setParameter('category', $category)
            ;
        }

        $zone = $filter->getZone() ?? $filter->getDefaultZone();
        $this->withGeoZones(
            [$zone],
            $qb,
            'event',
            BaseEvent::class,
            'e2',
            'zones',
            'z2',
            function (QueryBuilder $zoneQueryBuilder, string $entityClassAlias) {
                $zoneQueryBuilder->andWhere(sprintf('%s.published = :published', $entityClassAlias));
            }
        );

        return $qb->getQuery()->getResult();
    }
}
