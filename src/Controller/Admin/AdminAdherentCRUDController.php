<?php

namespace App\Controller\Admin;

use App\Adherent\AdherentExtractCommand;
use App\Adherent\AdherentExtractCommandHandler;
use App\Adherent\BanManager;
use App\Adherent\Certification\CertificationAuthorityManager;
use App\Adherent\Certification\CertificationPermissions;
use App\Adherent\Command\SendResubscribeEmailCommand;
use App\Adherent\UnregistrationManager;
use App\Entity\Adherent;
use App\Entity\AdherentEmailSubscribeToken;
use App\Form\Admin\Adherent\CreateRenaissanceType;
use App\Form\Admin\Adherent\UnregistrationType;
use App\Form\Admin\Extract\AdherentExtractType;
use App\Form\ConfirmActionType;
use App\Renaissance\Membership\Admin\AdherentCreateCommandHandler;
use Sonata\AdminBundle\Controller\CRUDController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

class AdminAdherentCRUDController extends CRUDController
{
    public function banAction(Request $request, BanManager $adherentManagementAuthority): Response
    {
        $adherent = $this->admin->getSubject();

        $this->admin->checkAccess('ban', $adherent);

        if (!$adherentManagementAuthority->canBan($adherent)) {
            $this->addFlash(
                'error',
                'Il est possible d\'exclure uniquement les adhérents sans aucun rôle (animateur, référent etc.).'
            );

            return $this->redirectTo($request, $adherent);
        }

        $form = $this
            ->createForm(ConfirmActionType::class)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('allow')->isClicked()) {
                $adherentManagementAuthority->ban($adherent, $this->getUser());

                $this->addFlash('success', sprintf('L\'adhérent <b>%s</b> a bien été exclu.', $adherent->getFullName()));
            }

            return $this->redirectToList();
        }

        return $this->renderWithExtraParams('admin/adherent/ban.html.twig', [
            'form' => $form->createView(),
            'object' => $adherent,
            'action' => 'ban',
            'elements' => $this->admin->getShow(),
        ]);
    }

    public function terminateMembershipAction(Request $request, UnregistrationManager $unregistrationManager): Response
    {
        $adherent = $this->admin->getSubject();

        $this->admin->checkAccess('terminate_membership', $adherent);

        if (!$this->isGranted('UNREGISTER', $adherent)) {
            $this->addFlash(
                'error',
                'Il est possible de faire désadhérer uniquement les adhérents sans aucun rôle (animateur, référent, candidat etc.).'
            );

            return $this->redirectTo($request, $adherent);
        }

        $unregistrationCommand = $unregistrationManager->createUnregistrationCommand($this->getUser());

        $form = $this
            ->createForm(UnregistrationType::class, $unregistrationCommand)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $unregistrationManager->terminateMembership($adherent, $unregistrationCommand);

            $this->addFlash('success', sprintf('L\'adhérent <b>%s</b> a bien été supprimé.', $adherent->getFullName()));

            return $this->redirectToList();
        }

        return $this->renderWithExtraParams('admin/adherent/terminate_membership.html.twig', [
            'form' => $form->createView(),
            'object' => $adherent,
            'action' => 'terminate_membership',
        ]);
    }

    public function certifyAction(Request $request, CertificationAuthorityManager $certificationManager): Response
    {
        /** @var Adherent $adherent */
        $adherent = $this->admin->getSubject();

        $this->admin->checkAccess('certify', $adherent);

        if (!$this->isGranted(CertificationPermissions::CERTIFY, $adherent)) {
            $this->addFlash('error', sprintf(
                'L\'adhérent <b>%s</b> est déjà certifié.',
                $adherent->getFullName()
            ));

            return $this->redirectTo($request, $adherent);
        }

        $form = $this
            ->createForm(ConfirmActionType::class)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('allow')->isClicked()) {
                $certificationManager->certify($adherent, $this->getUser());

                $this->addFlash('success', sprintf(
                    'L\'adhérent <b>%s</b> a bien été certifié.',
                    $adherent->getFullName()
                ));
            }

            return $this->redirectTo($request, $adherent);
        }

        return $this->renderWithExtraParams('admin/adherent/certify.html.twig', [
            'form' => $form->createView(),
            'object' => $adherent,
            'action' => 'certify',
            'elements' => $this->admin->getShow(),
        ]);
    }

    public function uncertifyAction(Request $request, CertificationAuthorityManager $certificationManager): Response
    {
        /** @var Adherent $adherent */
        $adherent = $this->admin->getSubject();

        $this->admin->checkAccess('uncertify', $adherent);

        if (!$this->isGranted(CertificationPermissions::UNCERTIFY, $adherent)) {
            $this->addFlash('error', sprintf(
                'L\'adhérent <b>%s</b> n\'est pas certifié.',
                $adherent->getFullName()
            ));

            return $this->redirectTo($request, $adherent);
        }

        $form = $this
            ->createForm(ConfirmActionType::class)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('allow')->isClicked()) {
                $certificationManager->uncertify($adherent, $this->getUser());

                $this->addFlash('warning', sprintf(
                    'L\'adhérent <b>%s</b> n\'est plus certifié.',
                    $adherent->getFullName()
                ));
            }

            return $this->redirectTo($request, $adherent);
        }

        return $this->renderWithExtraParams('admin/adherent/uncertify.html.twig', [
            'form' => $form->createView(),
            'object' => $adherent,
            'action' => 'uncertify',
            'elements' => $this->admin->getShow(),
        ]);
    }

    public function extractAction(
        Request $request,
        AdherentExtractCommandHandler $adherentExtractCommandHandler
    ): Response {
        $this->admin->checkAccess('extract');

        $adherentExtractCommand = new AdherentExtractCommand();

        $form = $this
            ->createForm(AdherentExtractType::class, $adherentExtractCommand)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            return $adherentExtractCommandHandler->createResponse($adherentExtractCommand);
        }

        return $this->render('admin/adherent/extract/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function sendResubscribeEmailAction(Request $request, Adherent $adherent, MessageBusInterface $bus): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            $this->validateCsrfToken($request, 'admin.adherent.send_email');

            $bus->dispatch(new SendResubscribeEmailCommand($adherent, AdherentEmailSubscribeToken::TRIGGER_SOURCE_ADMIN));

            $this->addFlash('sonata_flash_success', 'E-mail a bien été envoyé');

            return $this->redirect($this->admin->generateObjectUrl('edit', $adherent));
        }

        return $this->renderWithExtraParams('admin/CRUD/confirm.html.twig', [
            'csrf_token' => $this->getCsrfToken('admin.adherent.send_email'),
            'action' => 'send_resubscribe_email',
            'message' => 'Êtes-vous sûr de vouloir envoyer un e-mail de réabonnement à <strong>'.$adherent->getFullName().'</strong> ?',
            'object' => $adherent,
            'cancel_action' => 'edit',
        ]);
    }

    public function createRenaissanceAction(
        Request $request,
        AdherentCreateCommandHandler $adherentCreateCommandHandler
    ): Response {
        $this->admin->checkAccess('create_renaissance');

        $adherentCreateCommand = $adherentCreateCommandHandler->createCommand();

        $form = $this
            ->createForm(CreateRenaissanceType::class, $adherentCreateCommand)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $adherentCreateCommandHandler->handle($adherentCreateCommand);

            $this->addFlash('sonata_flash_success', 'Le compte adhérent Renaissance a bien été créé.');

            return $this->redirect($this->admin->generateUrl('create_renaissance'));
        }

        return $this->renderWithExtraParams('admin/adherent/renaissance/create.html.twig', [
            'action' => 'create_renaissance',
            'object' => $adherentCreateCommand,
            'form' => $form->createView(),
        ]);
    }
}
