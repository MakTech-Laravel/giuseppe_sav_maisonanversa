<?php

namespace App\Enums;

enum InquiryType: string
{
    case Contact = 'contact';
    case Corner = 'corner';
    case Appointment = 'appointment';
    case Consult = 'consult';
    case Feedback = 'feedback';

    /**
     * @return list<self>
     */
    public static function contactKinds(): array
    {
        return [
            self::Appointment,
            self::Consult,
            self::Feedback,
        ];
    }

    /**
     * @return list<self>
     */
    public static function appointmentInbox(): array
    {
        return [
            self::Appointment,
            self::Consult,
            self::Contact,
        ];
    }

    public function isContactKind(): bool
    {
        return in_array($this, self::contactKinds(), true);
    }

    public function isAppointmentInbox(): bool
    {
        return in_array($this, self::appointmentInbox(), true);
    }

    public function subject(): string
    {
        return match ($this) {
            self::Appointment => 'Afspraak aanvraag',
            self::Consult => 'Privé consult aanvraag',
            self::Feedback => 'Feedback',
            self::Corner => 'Club Corner partnership',
            self::Contact => 'Contact',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Appointment => 'Afspraak',
            self::Consult => 'Privé consult',
            self::Feedback => 'Feedback',
            self::Corner => 'Club Corner',
            self::Contact => 'Contact',
        };
    }

    public function confirmationSubject(): string
    {
        return match ($this) {
            self::Appointment => 'Wij hebben uw afspraakaanvraag ontvangen',
            self::Consult => 'Wij hebben uw consultaanvraag ontvangen',
            self::Feedback => 'Dank voor uw feedback',
            self::Corner => 'Wij hebben uw aanvraag ontvangen',
            self::Contact => 'Wij hebben uw bericht ontvangen',
        };
    }
}
