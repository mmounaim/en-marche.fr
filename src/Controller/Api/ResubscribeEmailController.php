<?php

namespace App\Controller\Api;

use App\Entity\Adherent;
use App\Mailchimp\SignUp\SignUpHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/resubscribe-email", name="api_resubscribe_email_payload", methods={"GET"})
 *
 * @IsGranted("IS_AUTHENTICATED_FULLY")
 */
class ResubscribeEmailController extends AbstractController
{
    public function __invoke(SignUpHandler $signUpHandler): Response
    {
        /** @var Adherent $adherent */
        $adherent = $this->getUser();

        return $this->json([
            'url' => $signUpHandler->getMailchimpSignUpHost(),
            'payload' => $signUpHandler->generatePayload($adherent),
        ]);
    }
}
