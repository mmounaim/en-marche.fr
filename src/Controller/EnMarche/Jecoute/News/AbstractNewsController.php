<?php

namespace App\Controller\EnMarche\Jecoute\News;

use App\Controller\EnMarche\AccessDelegatorTrait;
use App\Entity\Adherent;
use App\Entity\Jecoute\News;
use App\Form\ConfirmActionType;
use App\Form\Jecoute\NewsFormType;
use App\JeMarche\NotificationTopicBuilder;
use App\Repository\Geo\ZoneRepository;
use App\Repository\Jecoute\NewsRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractNewsController extends AbstractController
{
    use AccessDelegatorTrait;

    protected $newsRepository;
    protected $zoneRepository;

    public function __construct(NewsRepository $newsRepository, ZoneRepository $zoneRepository)
    {
        $this->newsRepository = $newsRepository;
        $this->zoneRepository = $zoneRepository;
    }

    /**
     * @Route("", name="news_list", methods={"GET"})
     */
    public function jecouteNewsListAction(Request $request): Response
    {
        return $this->renderTemplate('jecoute/news/news_list.html.twig', [
            'news' => $this->getNews($this->getMainUser($request->getSession())),
        ]);
    }

    /**
     * @Route(
     *     path="/creer",
     *     name="news_create",
     *     methods={"GET|POST"},
     * )
     */
    public function jecouteNewsCreateAction(
        Request $request,
        ObjectManager $manager,
        NotificationTopicBuilder $topicBuilder,
        UserInterface $user
    ): Response {
        /** @var Adherent $user */
        $news = new News();
        $zones = $this->getZones($this->getMainUser($request->getSession()));
        if (1 === \count($zones)) {
            $news->setZone($zones[0]);
        }

        $form = $this
            ->createForm(NewsFormType::class, $news, ['zones' => $zones])
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $topic = $topicBuilder->buildTopic($news->getZone());

            $news->setTopic($news);
            $news->setAuthor($user);

            $manager->persist($news);
            $manager->flush();

            $this->addFlash('info', 'jecoute_news.create.success');

            return $this->redirectToNewseRoute('news_list');
        }

        return $this->renderTemplate('jecoute/news/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     path="/{uuid}/editer",
     *     name="news_edit",
     *     requirements={"uuid": "%pattern_uuid%"},
     *     methods={"GET|POST"}
     * )
     */
    public function jecouteNewsEditAction(Request $request, News $news, ObjectManager $manager): Response
    {
        $zones = $this->getZones($this->getMainUser($request->getSession()));

        $form = $this
            ->createForm(NewsFormType::class, $news, ['zones' => $zones, 'edit' => true])
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('info', 'jecoute_news.edit.success');

            return $this->redirectToNewseRoute('news_list');
        }

        return $this->renderTemplate('jecoute/news/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *     path="/{uuid}/supprimer",
     *     name="news_delete",
     *     requirements={"uuid": "%pattern_uuid%"},
     *     methods={"GET|POST"}
     * )
     */
    public function jecouteNewsDeleteAction(Request $request, News $news, ObjectManager $manager): Response
    {
        $form = $this->createForm(ConfirmActionType::class)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->remove($news);
            $manager->flush();

            $this->addFlash('info', 'jecoute_news.delete.success');

            return $this->redirectToNewseRoute('news_list');
        }

        return $this->renderTemplate('jecoute/news/delete.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    abstract protected function getSpaceName(): string;

    abstract protected function getZones(Adherent $adherent): array;

    /**
     * @return News[]
     */
    protected function getNews(Adherent $adherent): array
    {
        return $this->newsRepository->findByZone($this->getZones($adherent));
    }

    protected function renderTemplate(string $template, array $parameters = []): Response
    {
        return $this->render($template, array_merge(
            $parameters,
            [
                'base_template' => sprintf('jecoute/_base_%s_space.html.twig', $spaceName = $this->getSpaceName()),
                'space_name' => $spaceName,
            ]
        ));
    }

    protected function redirectToNewseRoute(string $subName, array $parameters = []): Response
    {
        return $this->redirectToRoute("app_jecoute_news_{$this->getSpaceName()}_${subName}", $parameters);
    }
}
