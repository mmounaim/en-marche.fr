<?php

namespace App\Controller\Api;

use App\AdherentProfile\AdherentProfile;
use App\AdherentProfile\AdherentProfileConfiguration;
use App\AdherentProfile\AdherentProfileHandler;
use App\Donation\DonationManager;
use App\Donation\Paybox\PayboxPaymentUnsubscription;
use App\Entity\Adherent;
use App\Exception\PayboxPaymentUnsubscriptionException;
use App\Membership\MembershipRequestHandler;
use App\Membership\MembershipSourceEnum;
use App\OAuth\TokenRevocationAuthority;
use App\Repository\DonationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(path: '/v3/profile', name: 'app_api_user_profile')]
class ProfileController extends AbstractController
{
    private const READ_PROFILE_SERIALIZATION_GROUPS = [
        'profile_read',
    ];

    private const WRITE_PROFILE_SERIALIZATION_GROUPS = [
        'profile_write',
    ];

    private const WRITE_UNCERTIFIED_PROFILE_SERIALIZATION_GROUPS = [
        'uncertified_profile_write',
    ];

    #[IsGranted('ROLE_OAUTH_SCOPE_READ:PROFILE')]
    #[Route(path: '/me', name: '_show', methods: ['GET'])]
    public function show(SerializerInterface $serializer): JsonResponse
    {
        /** @var Adherent $user */
        $user = $this->getUser();

        return JsonResponse::fromJsonString(
            $serializer->serialize($user, 'json', [
                AbstractObjectNormalizer::GROUPS => self::READ_PROFILE_SERIALIZATION_GROUPS,
            ])
        );
    }

    #[IsGranted('ROLE_OAUTH_SCOPE_READ:PROFILE')]
    #[Route(path: '/me/donations', name: '_donations_show', methods: ['GET'])]
    public function showDonations(SerializerInterface $serializer, DonationManager $donationManager): JsonResponse
    {
        /** @var Adherent $user */
        $user = $this->getUser();

        return JsonResponse::fromJsonString(
            $serializer->serialize(
                $donationManager->getHistory($user),
                'json',
                [
                    AbstractObjectNormalizer::GROUPS => ['donation_read'],
                ]
            )
        );
    }

    #[IsGranted('ROLE_OAUTH_SCOPE_READ:PROFILE')]
    #[Route(path: '/me/donations/cancel', name: '_donations_cancel', methods: ['POST'])]
    public function cancelDonations(
        EntityManagerInterface $entityManager,
        DonationRepository $donationRepository,
        PayboxPaymentUnsubscription $payboxPaymentUnsubscription,
        LoggerInterface $logger
    ): JsonResponse {
        /** @var Adherent $user */
        $user = $this->getUser();

        $donations = $donationRepository->findAllSubscribedDonationByEmail($user->getEmailAddress());

        foreach ($donations as $donation) {
            try {
                $payboxPaymentUnsubscription->unsubscribe($donation);
                $entityManager->flush();
                $payboxPaymentUnsubscription->sendConfirmationMessage($donation, $user);

                $logger->info(\sprintf('Subscription donation id(%d) from user email %s have been cancel successfully.', $donation->getId(), $user->getEmailAddress()));
            } catch (PayboxPaymentUnsubscriptionException $e) {
                $logger->error(\sprintf('Subscription donation id(%d) from user email %s have an error.', $donation->getId(), $user->getEmailAddress()), ['exception' => $e]);
            }
        }

        return new JsonResponse('OK');
    }

    #[Route(path: '/{uuid}', name: '_update', methods: ['PUT'])]
    #[Security("is_granted('ROLE_OAUTH_SCOPE_WRITE:PROFILE') and user == adherent")]
    public function update(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        AdherentProfileHandler $handler,
        Adherent $adherent
    ): JsonResponse {
        $json = $request->getContent();

        $adherentProfile = AdherentProfile::createFromAdherent($adherent);

        $groups = self::WRITE_PROFILE_SERIALIZATION_GROUPS;
        if (!$adherent->isCertified()) {
            $groups = array_merge($groups, self::WRITE_UNCERTIFIED_PROFILE_SERIALIZATION_GROUPS);
        }

        $serializer->deserialize($json, AdherentProfile::class, 'json', [
            AbstractObjectNormalizer::OBJECT_TO_POPULATE => $adherentProfile,
            AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
            AbstractObjectNormalizer::GROUPS => $groups,
        ]);

        $validationGroups = ['api_put_validation'];
        if ($adherent->isAdherent()) {
            $validationGroups[] = 'Default';
        }

        $violations = $validator->validate($adherentProfile, null, $validationGroups);

        if (0 === $violations->count()) {
            $handler->update($adherent, $adherentProfile);

            return new JsonResponse('OK');
        }

        $errors = $serializer->serialize($violations, 'jsonproblem');

        return JsonResponse::fromJsonString($errors, JsonResponse::HTTP_BAD_REQUEST);
    }

    #[IsGranted('ROLE_OAUTH_SCOPE_WRITE:PROFILE')]
    #[Route(path: '/configuration', name: '_configuration', methods: ['GET'])]
    public function configuration(AdherentProfileConfiguration $adherentProfileConfiguration): JsonResponse
    {
        return new JsonResponse($adherentProfileConfiguration->build());
    }

    #[Route(path: '/unregister', name: '_unregister', methods: ['POST'])]
    #[Security("is_granted('UNREGISTER', user)")]
    public function terminateMembershipAction(
        MembershipRequestHandler $handler,
        TokenRevocationAuthority $tokenRevocationAuthority
    ): Response {
        /** @var Adherent $user */
        $user = $this->getUser();

        $handler->terminateMembership(
            $user,
            null,
            $user instanceof Adherent && MembershipSourceEnum::BESOIN_D_EUROPE === $user->getSource(),
            '[API] Compte supprimé par l\'adhérent'
        );

        $tokenRevocationAuthority->revokeUserTokens($user);

        return $this->json('OK');
    }
}
