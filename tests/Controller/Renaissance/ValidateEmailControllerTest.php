<?php

namespace Tests\App\Controller\Renaissance;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\AbstractRenaissanceWebTestCase;
use Tests\App\Controller\ControllerTestTrait;

#[Group('functional')]
#[Group('controller')]
class ValidateEmailControllerTest extends AbstractRenaissanceWebTestCase
{
    use ControllerTestTrait;

    /** @dataProvider getEmails */
    public function testValidateEmailEndpoint(string $email, int $status): void
    {
        $crawler = $this->client->request('GET', '/v2/adhesion');
        $token = $crawler->filter('#email-validation-token')->attr('value');

        $this->client->jsonRequest('POST', '/api/validate-email', ['email' => $email, 'token' => $token]);
        $response = json_decode($this->client->getResponse()->getContent(), true);

        if (0 === $status) {
            self::assertSame('OK', $response);

            return;
        }

        if (1 === $status) {
            self::assertSame('warning', $response['status']);
        } else {
            self::assertSame('error', $response['status']);
        }

        if ($email) {
            if (1 === $status) {
                self::assertSame('Nous ne sommes pas parvenus à vérifier l\'existence de l\'adresse « '.$email.' ». Vérifiez votre saisie avant de continuer.', $response['message']);
            } else {
                self::assertSame('L\'adresse « '.$email.' » n\'est pas valide.', $response['message']);
            }
        } else {
            self::assertSame('L\'adresse email est nécessaire pour continuer.', $response['message']);
        }
    }

    public static function getEmails(): \Generator
    {
        yield current($params = ['techsupport@parti-renaissance.fr', 0]) => $params;
        yield current($params = ['warding-email@parti-renaissance123.fr', 1]) => $params;
        yield current($params = ['warding-email@yopmail.com', 2]) => $params;
        yield current($params = ['disabled-email@test.com', 2]) => $params;
        yield current($params = ['invalid-email', 2]) => $params;
        yield current($params = ['invalid-email@parti-renaissance', 2]) => $params;
        yield current($params = ['invalid-email@parti-renaissance..fr', 2]) => $params;
        yield current($params = ['', 2]) => $params;
    }
}
