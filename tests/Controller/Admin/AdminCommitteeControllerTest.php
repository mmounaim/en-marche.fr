<?php

namespace Tests\App\Controller\Admin;

use App\Committee\CommitteeAdherentMandateManager;
use App\DataFixtures\ORM\LoadAdherentData;
use App\DataFixtures\ORM\LoadCommitteeData;
use App\Entity\AdherentMandate\AbstractAdherentMandate;
use App\Entity\AdherentMandate\CommitteeAdherentMandate;
use App\Entity\AdherentMandate\CommitteeMandateQualityEnum;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\App\Controller\ControllerTestTrait;

/**
 * @group functional
 * @group admin
 */
class AdminCommitteeControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    private $committeeRepository;
    private $adherentRepository;
    private $committeeMandateRepository;

    /**
     * @dataProvider provideActions
     */
    public function testCannotChangeMandateIfCommitteeNotApprovedAction(string $action): void
    {
        $committee = $this->committeeRepository->findOneByUuid(LoadCommitteeData::COMMITTEE_2_UUID);
        $adherent = $this->getAdherentRepository()->findOneByUuid(LoadAdherentData::ADHERENT_1_UUID);

        $this->assertFalse($committee->isApproved());

        $this->authenticateAsAdmin($this->client);

        $this->client->request(
            Request::METHOD_GET,
            \sprintf('/admin/committee/%s/members/%s/%s-mandate', $committee->getId(), $adherent->getId(), $action)
        );
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST, $this->client->getResponse());
    }

    public function testCannotReplaceInactiveMandate(): void
    {
        /** @var CommitteeAdherentMandate $mandate */
        $mandate = $this->committeeMandateRepository->findOneBy([
            'finishAt' => new \DateTime('2018-05-05 12:12:12'),
        ]);

        $this->authenticateAsAdmin($this->client);

        $this->client->request(
            Request::METHOD_GET,
            \sprintf('/admin/committee/mandates/%s/replace', $mandate->getId())
        );
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());
        $this->assertClientIsRedirectedTo(\sprintf('/admin/committee/%s/mandates', $mandate->getCommittee()->getId()), $this->client);
    }

    public function testCannotReplaceMandateWhenNoAdherent(): void
    {
        /** @var CommitteeAdherentMandate $mandate */
        $mandate = $this->committeeMandateRepository->findOneBy([
            'beginAt' => new \DateTime('2020-10-10 10:10:10'),
        ]);

        $this->authenticateAsAdmin($this->client);

        $crawler = $this->client->request(
            Request::METHOD_GET,
            \sprintf('/admin/committee/mandates/%s/replace', $mandate->getId())
        );

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $crawler = $this->client->submit($crawler->selectButton('Suivant')->form());

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $errors = $crawler->filter('.sonata-ba-field-error-messages li');

        $this->assertCount(1, $errors);
        $this->assertSame('L\'adhérent du mandat ne doit pas être vide.', trim($errors->first()->text()));
    }

    public function testCannotReplaceMandateWhenNotCorrectGender(): void
    {
        $adherent = $this->adherentRepository->findOneByUuid(LoadAdherentData::ADHERENT_5_UUID);
        /** @var CommitteeAdherentMandate $mandate */
        $mandate = $this->committeeMandateRepository->findOneBy([
            'beginAt' => new \DateTime('2020-10-10 10:10:10'),
        ]);

        $this->authenticateAsAdmin($this->client);

        $crawler = $this->client->request(
            Request::METHOD_GET,
            \sprintf('/admin/committee/mandates/%s/replace', $mandate->getId())
        );

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $crawler = $this->client->submit($crawler->selectButton('Suivant')->form([
            'committee_mandate_command[adherent]' => $adherent->getId(),
            'committee_mandate_command[_token]' => $crawler->filter('input[name="committee_mandate_command[_token]"]')->attr('value'),
        ]));

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $errors = $crawler->filter('.sonata-ba-field-error-messages li');

        $this->assertCount(1, $errors);
        $this->assertSame('Le genre de l\'adhérent ne correspond pas au genre du mandat.', trim($errors->first()->text()));
    }

    public function testCannotReplaceMandateWhenAdherentIsMinor(): void
    {
        $adherent = $this->adherentRepository->findOneByUuid(LoadAdherentData::ADHERENT_11_UUID);
        /** @var CommitteeAdherentMandate $mandate */
        $mandate = $this->committeeMandateRepository->findOneBy([
            'beginAt' => new \DateTime('2020-10-11 11:11:11'),
        ]);

        $this->authenticateAsAdmin($this->client);

        $crawler = $this->client->request(
            Request::METHOD_GET,
            \sprintf('/admin/committee/mandates/%s/replace', $mandate->getId())
        );

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $crawler = $this->client->submit($crawler->selectButton('Suivant')->form([
            'committee_mandate_command[adherent]' => $adherent->getId(),
            'committee_mandate_command[_token]' => $crawler->filter('input[name="committee_mandate_command[_token]"]')->attr('value'),
        ]));

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $errors = $crawler->filter('.sonata-ba-field-error-messages li');

        $this->assertCount(1, $errors);
        $this->assertSame('L\'adhérent ne doit pas être Parlementaire ou mineur.', trim($errors->first()->text()));
    }

    public function testCanReplaceMandateEvenAnotherSupervisor(): void
    {
        $adherent = $this->adherentRepository->findOneByUuid(LoadAdherentData::ADHERENT_7_UUID);
        /** @var CommitteeAdherentMandate $mandate */
        $mandate = $this->committeeMandateRepository->findOneBy([
            'beginAt' => new \DateTime('2020-10-10 10:10:10'),
        ]);
        $foundMandate = $this->committeeMandateRepository->findOneBy([
            'adherent' => $adherent->getId(),
            'committee' => $mandate->getCommittee()->getId(),
        ]);

        $this->assertNull($mandate->getFinishAt());
        $this->assertTrue($mandate->isProvisional());
        $this->assertSame(CommitteeMandateQualityEnum::SUPERVISOR, $mandate->getQuality());
        $this->assertNull($foundMandate);

        $this->authenticateAsAdmin($this->client);

        $crawler = $this->client->request(
            Request::METHOD_GET,
            \sprintf('/admin/committee/mandates/%s/replace', $mandate->getId())
        );

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $crawler = $this->client->submit($crawler->selectButton('Suivant')->form([
            'committee_mandate_command[adherent]' => $adherent->getId(),
            'committee_mandate_command[_token]' => $crawler->filter('input[name="committee_mandate_command[_token]"]')->attr('value'),
        ]));

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->assertCount(0, $crawler->filter('.sonata-ba-field-error-messages li'));
        $this->assertCount(1, $warning = $crawler->filter('.alert-warning'));
        $this->assertStringContainsString('Attention, cet adhérent est déjà Animateur dans le comité', $warning->text());

        $this->client->submit($crawler->selectButton('Confirmer')->form());

        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->manager->clear();

        /** @var CommitteeAdherentMandate $newMandate */
        $newMandate = $this->committeeMandateRepository->findOneBy([
            'adherent' => $adherent->getId(),
            'committee' => $mandate->getCommittee()->getId(),
        ]);

        $mandate = $this->committeeMandateRepository->findOneBy([
            'beginAt' => new \DateTime('2020-10-10 10:10:10'),
        ]);

        $this->assertNotNull($mandate->getFinishAt());
        $this->assertSame(AbstractAdherentMandate::REASON_REPLACED, $mandate->getReason());
        $this->assertNotNull($newMandate);
        $this->assertTrue($newMandate->isProvisional());
        $this->assertSame(CommitteeMandateQualityEnum::SUPERVISOR, $newMandate->getQuality());
    }

    public function testCanReplaceSupervisorMandate(): void
    {
        $adherent = $this->adherentRepository->findOneByUuid(LoadAdherentData::ADHERENT_9_UUID);
        /** @var CommitteeAdherentMandate $mandate */
        $mandate = $this->committeeMandateRepository->findOneBy([
            'beginAt' => new \DateTime('2020-10-11 11:11:11'),
        ]);
        $foundMandate = $this->committeeMandateRepository->findOneBy([
            'adherent' => $adherent->getId(),
            'committee' => $mandate->getCommittee()->getId(),
        ]);

        $this->assertNull($mandate->getFinishAt());
        $this->assertFalse($mandate->isProvisional());
        $this->assertSame(CommitteeMandateQualityEnum::SUPERVISOR, $mandate->getQuality());
        $this->assertNull($foundMandate);

        $this->authenticateAsAdmin($this->client);

        $crawler = $this->client->request(
            Request::METHOD_GET,
            \sprintf('/admin/committee/mandates/%s/replace', $mandate->getId())
        );

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $crawler = $this->client->submit($crawler->selectButton('Suivant')->form([
            'committee_mandate_command[adherent]' => $adherent->getId(),
            'committee_mandate_command[_token]' => $crawler->filter('input[name="committee_mandate_command[_token]"]')->attr('value'),
        ]));

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->assertCount(0, $crawler->filter('.sonata-ba-field-error-messages li'));
        $this->assertCount(1, $warning = $crawler->filter('.alert-warning'));
        $this->assertStringContainsString('Attention, cet adhérent est déjà Animateur dans le comité', $warning->text());

        $this->client->submit($crawler->selectButton('Confirmer')->form());

        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->manager->clear();

        /** @var CommitteeAdherentMandate $newMandate */
        $newMandate = $this->committeeMandateRepository->findOneBy([
            'adherent' => $adherent->getId(),
            'committee' => $mandate->getCommittee()->getId(),
        ]);

        $mandate = $this->committeeMandateRepository->findOneBy([
            'beginAt' => new \DateTime('2020-10-11 11:11:11'),
        ]);

        $this->assertNotNull($mandate->getFinishAt());
        $this->assertSame(AbstractAdherentMandate::REASON_REPLACED, $mandate->getReason());
        $this->assertNotNull($newMandate);
        $this->assertTrue($newMandate->isProvisional());
        $this->assertSame(CommitteeMandateQualityEnum::SUPERVISOR, $newMandate->getQuality());
    }

    public function provideActions(): iterable
    {
        yield [CommitteeAdherentMandateManager::CREATE_ACTION];
        yield [CommitteeAdherentMandateManager::FINISH_ACTION];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->init();

        $this->committeeRepository = $this->getCommitteeRepository();
        $this->committeeMandateRepository = $this->getCommitteeMandateRepository();
        $this->adherentRepository = $this->getAdherentRepository();
    }

    protected function tearDown(): void
    {
        $this->kill();

        $this->committeeRepository = null;
        $this->committeeMandateRepository = null;
        $this->adherentRepository = null;

        parent::tearDown();
    }
}
