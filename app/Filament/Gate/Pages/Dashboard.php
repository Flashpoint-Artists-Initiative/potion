<?php

declare(strict_types=1);

namespace App\Filament\Gate\Pages;

use App\Services\QRCodeService;
use Filament\Actions\Action;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static string $view = 'filament.gate.pages.dashboard';

    protected static ?string $navigationLabel = 'Scan';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('search')
                ->label('Search for Attendee')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->url(Search::getUrl()),
        ];
    }

    /**
     * @param  non-empty-string  $code
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
