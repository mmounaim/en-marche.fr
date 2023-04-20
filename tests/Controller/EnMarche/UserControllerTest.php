<?php

namespace Tests\App\Controller\EnMarche;

use App\Entity\Adherent;
use App\Entity\AdherentChangeEmailToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\App\AbstractEnMarcheWebCaseTest;
use Tests\App\Controller\ControllerTestTrait;

/**
 * @group functional
 * @group membership
 */
class UserControllerTest extends AbstractEnMarcheWebCaseTest
{
    use ControllerTestTrait;

    public function testUserCannotReplaceYourEmailByOneAlreadyUsed(): void
    {
        $this->authenticateAsAdherent($this->client, 'carl999@example.fr');

        $crawler = $this->client->request(Request::METHOD_GET, '/parametres/mon-compte');

        $crawler = $this->client->submit($crawler->selectButton('Enregistrer')->form(), [
            'adherent_profile[emailAddress]' => 'referent@en-marche-dev.fr',
        ]);

        $errors = $crawler->filter('.em-form--error');

        $this->assertStatusCode(Response::HTTP_OK, $this->client);
        self::assertSame('Cette adresse e-mail existe déjà.', $errors->eq(0)->text());
    }

    public function testUserCanValidateYourNewEmail(): void
    {
        $this->authenticateAsAdherent($this->client, 'carl999@example.fr');

        $crawler = $this->client->request('GET', '/parametres/mon-compte');

        $this->client->submit($crawler->selectButton('Enregistrer')->form(), [
            'adherent_profile[emailAddress]' => 'new.mail@test.com',
        ]);

        $this->assertClientIsRedirectedTo('/parametres/mon-compte', $this->client);

        $crawler = $this->client->followRedirect();

        $this->assertStatusCode(200, $this->client);

        $this->assertStringContainsString(
            'Nous avons envoyé un e-mail à new.mail@test.com pour vérifier votre adresse e-mail. Cliquez sur le lien qui y est présent pour valider le changement.',
            $crawler->filter('.flash--info')->eq(1)->text()
        );

        $token = $this->getRepository(AdherentChangeEmailToken::class)->findLastUnusedByEmail('new.mail@test.com');

        $this->client->request(Request::METHOD_GET, sprintf('/valider-changement-email/%s/%s', $token->getAdherentUuid(), $token->getValue()));
        $this->assertClientIsRedirectedTo('/', $this->client);

        $flash = $this->client->getRequest()->getSession()->getFlashBag()->get('info');
        self::assertCount(1, $flash);
        self::assertSame('adherent.change_email.success', current($flash));

        $this->manager->clear(Adherent::class);
        $adherent = $this->getAdherentRepository()->findOneByUuid($token->getAdherentUuid()->toString());
        self::assertSame('new.mail@test.com', $adherent->getEmailAddress());
    }
}
