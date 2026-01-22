<?php

declare(strict_types=1);

namespace Lynkbyte\EvolutionApi\DTOs\Message;

use Lynkbyte\EvolutionApi\DTOs\BaseDto;

/**
 * DTO for sending contact messages.
 */
final class SendContactMessageDto extends BaseDto
{
    /**
     * @param array<array{fullName: string, wuid: string, phoneNumber: string, organization?: string, email?: string, url?: string}> $contact
     */
    public function __construct(
        public readonly string $number,
        public readonly array $contact,
        public readonly ?int $delay = null,
        public readonly ?array $quoted = null,
    ) {
        $this->validateRequired(['number', 'contact']);
    }

    /**
     * Create a single contact message.
     */
    public static function single(
        string $number,
        string $fullName,
        string $wuid,
        string $phoneNumber,
        ?string $organization = null,
        ?string $email = null,
        ?string $url = null
    ): self {
        $contactData = [
            'fullName' => $fullName,
            'wuid' => $wuid,
            'phoneNumber' => $phoneNumber,
        ];

        if ($organization !== null) {
            $contactData['organization'] = $organization;
        }
        if ($email !== null) {
            $contactData['email'] = $email;
        }
        if ($url !== null) {
            $contactData['url'] = $url;
        }

        return new self(number: $number, contact: [$contactData]);
    }

    /**
     * Create multiple contacts message.
     *
     * @param array<array{fullName: string, wuid: string, phoneNumber: string, organization?: string, email?: string, url?: string}> $contacts
     */
    public static function multiple(string $number, array $contacts): self
    {
        return new self(number: $number, contact: $contacts);
    }

    /**
     * {@inheritdoc}
     */
    public function toApiPayload(): array
    {
        $payload = [
            'number' => $this->number,
            'contact' => $this->contact,
        ];

        if ($this->delay !== null) {
            $payload['delay'] = $this->delay;
        }

        if ($this->quoted !== null) {
            $payload['quoted'] = $this->quoted;
        }

        return $payload;
    }
}
