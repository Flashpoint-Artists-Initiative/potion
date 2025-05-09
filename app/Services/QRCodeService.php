<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Services\Helpers\SvgWithLogoOptions;
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
        $content = ['user_id' => $userId, 'event_id' => $eventId];

        /**
         * It's impossible for json_encode to fail with the parameter types, a TypeError would be thrown instead
         *
         * @var non-empty-string $json
         */
        $json = json_encode($content);

        return $json;
    }
}
