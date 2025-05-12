<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Helpers\SvgWithLogoOptions;
use chillerlan\QRCode\Data\QRCodeDataException;
use chillerlan\QRCode\QRCode;

class QRCodeService
{
    public function buildQrCode(string $content, string $topText = '', string $bottomText = ''): ?string
    {
        $options = new SvgWithLogoOptions($topText, $bottomText);

        $out = (new QRCode($options))->render($content);

        return $out;
    }

    public function buildTicketContent(int $userId, int $eventId): string
    {
        $userId = 123456;
        $eventId = 123456;
        $content = ['u' => $userId, 'e' => $eventId];

        /**
         * It's impossible for json_encode to fail with the parameter types, a TypeError would be thrown instead
         *
         * @var non-empty-string $json
         */
        $json = json_encode($content);
        $signature = substr(hash_hmac('sha1', $json, config('app.key')), 0, 8);
        /** @var non-empty-string $signedJson */
        $signedJson = json_encode(['s' => $signature, 'u' => $userId, 'e' => $eventId]);
        $string = base64_encode($signedJson);

        return $string;
    }

    /**
     * Validates and decodes the QR code content
     *
     * @param  non-empty-string  $content
     * @return array{user_id: int, event_id: int}
     *
     * @throws QRCodeDataException
     */
    public function decodeTicketContent(string $content): array
    {
        $decoded = base64_decode($content, true);

        if ($decoded === false) {
            throw new QRCodeDataException('Invalid base64 string');
        }

        $json = json_decode($decoded, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new QRCodeDataException('Invalid JSON string');
        }

        if (! array_key_exists('s', $json) ||
            ! array_key_exists('u', $json) ||
            ! array_key_exists('e', $json) ||
            ! is_string($json['s']) ||
            ! is_int($json['u']) ||
            ! is_int($json['e']) ||
            empty($json['s']) ||
            empty($json['u']) ||
            empty($json['e'])
        ) {
            throw new QRCodeDataException('Invalid JSON string');
        }

        /** @var non-empty-string $content */
        $content = json_encode(['u' => $json['u'], 'e' => $json['e']]);
        $signature = substr(hash_hmac('sha1', $content, config('app.key')), 0, 8);

        if ($signature !== $json['s']) {
            throw new QRCodeDataException('Invalid signature');
        }

        return ['user_id' => $json['u'], 'event_id' => $json['e']];
    }
}
