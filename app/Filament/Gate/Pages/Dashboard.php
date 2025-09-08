<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use App\Services\QRCodeService;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.gate.pages.dashboard';

    /**
     * @param non-empty-string $code
     */
    public function processScan(string $code, QRCodeService $qrCodeService): void
    {
        $data = $qrCodeService->decodeTicketContent($code);

        $this->redirect(Checkin::getUrl([
            'userId' => $data['user_id'],
            'eventId' => $data['event_id'],
        ]));
    }
}
