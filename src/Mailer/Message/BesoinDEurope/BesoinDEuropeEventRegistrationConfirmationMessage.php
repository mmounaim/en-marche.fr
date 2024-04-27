<?php

namespace App\Mailer\Message\BesoinDEurope;

use App\Entity\Event\EventRegistration;
use App\Mailer\Message\Renaissance\AbstractRenaissanceMessage;
use Ramsey\Uuid\Uuid;

class BesoinDEuropeEventRegistrationConfirmationMessage extends AbstractRenaissanceMessage
{
    public static function createFromRegistration(EventRegistration $registration, string $eventLink): self
    {
        $event = $registration->getEvent();
        $firstName = $registration->getFirstName();

        return new self(
            Uuid::uuid4(),
            $registration->getEmailAddress(),
            $firstName,
            '',
            static::getTemplateVars(
                $event->getName(),
                $event->getOrganizerName(),
                $eventLink
            ),
            static::getRecipientVars($firstName)
        );
    }

    private static function getTemplateVars(string $eventName, string $organizerName, string $eventLink): array
    {
        return [
            'event_name' => self::escape($eventName),
            'event_organiser' => self::escape($organizerName),
            'event_link' => $eventLink,
        ];
    }

    private static function getRecipientVars(string $firstName): array
    {
        return [
            'first_name' => self::escape($firstName),
        ];
    }
}
