<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Enums\LockdownEnum;
use App\Models\Event;
use App\Models\Ticketing\Cart;
use App\Models\Ticketing\ReservedTicket;
use App\Models\Ticketing\TicketType;
use App\Models\Ticketing\Waiver;
use App\Models\User;
use App\Rules\TicketSaleRule;
use App\Services\CartService;
use App\Services\StripeService;
use Carbon\Carbon;
use Filament\Actions\Action as ActionsAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;

/**
 * @property Form $form
 */
class PurchaseTickets extends Page
{
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static string $view = 'filament.app.pages.purchase-tickets';

    protected static ?string $slug = 'purchase';

    /** @var array<string, mixed> */
    public array $data = [];

    protected ?Waiver $waiver;

    public ?Cart $cart;

    public bool $hasPurchasedTickets;
    
    public bool $eventIsFuture;

    protected int $ticketCount;

    public ?string $pageContent;

    public int $maxTickets;

    // Autofill reserved ticket checkbox from query string
    #[Url('reserved')]
    public ?int $reservedFilled = null;

    public function __construct()
    {
        $this->refreshProperties();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Event::where('id', Event::getCurrentEventId())->where('active', true)->exists();
    }

    protected function refreshProperties(): void
    {
        /** @var User */
        $user = Auth::user();
        $this->cart = app(CartService::class)->getActiveCart();
        $this->hasPurchasedTickets = $user->purchasedTickets()->currentEvent()->exists();
        $this->pageContent = Event::getCurrentEvent()?->ticketPurchaseContent?->formattedContent;
        $this->maxTickets = (int) (Event::getCurrentEvent()->ticketsPerSale ?? 4);
        $this->eventIsFuture = Event::getCurrentEvent()?->endDateCarbon->isFuture() ?? false;
    }

    public function form(Form $form): Form
    {
        if (LockdownEnum::Tickets->isLocked() || $this->eventIsFuture == false) {
            return $form->schema([
                Placeholder::make('locked')
                    ->label('')
                    ->content(new HtmlString('<h1 class="text-2xl text-center">Ticket sales are closed.</h1>'))
                    ->dehydrated(false),
            ]);
        }

        $this->waiver = Event::getCurrentEvent()?->waiver;
        // Call here so we get an accurate ticket count
        $ticketStep = $this->buildTicketsStep();
        $submitString = <<<'BLADE'
            <x-filament::button
                type="submit"
                size="sm"
                wire:target="checkout"
            >
                Checkout
            </x-filament::button>
        BLADE;

        if ($this->ticketCount === 0) {
            $submitString = '';
        }

        return $form
            ->schema([
                Wizard::make([
                    $this->buildWaiverStep(),
                    $ticketStep,
                ])
                    ->submitAction(new HtmlString(Blade::render($submitString))),
            ])
            ->statePath('data');
    }

    protected function buildTicketsStep(): Step
    {
        /** @var EloquentCollection<int,TicketType> $tickets */
        $tickets = TicketType::query()->currentEvent()->available()->get();
        $ticketSchema = $tickets
            ->sort(function (TicketType $a, TicketType $b) {
                // Put addon tickets at the bottom, otherwise sort by id
                if ($a->addon && ! $b->addon) {
                    return 1;
                }

                if ($b->addon && ! $a->addon) {
                    return -1;
                }

                return $a->id <=> $b->id;
            })
            ->map(function (TicketType $ticket, int $index) {
                // Hide anything without remaining tickets.
                // We do this here because remainingTicketCount is inaccessible in the query
                if ($ticket->remainingTicketCount <= 0) {
                    return null;
                }

                return ViewField::make('tickets.' . $ticket->id)
                    ->model($ticket)
                    ->default(0)
                    ->rules([new TicketSaleRule])
                    ->hiddenLabel()
                    ->view('forms.components.ticket-type-field');
            })->filter(fn ($item) => $item !== null)
            ->toArray();

        if (count($ticketSchema) === 0) {
            $nextTicketSaleDate = Event::getCurrentEvent()?->nextTicketSaleDate?->timezone('America/New_York')->format('F jS, Y g:i A T');
            if ($nextTicketSaleDate) {
                $ticketSchema[] = Placeholder::make('noTickets')
                    ->label('')
                    ->content(new HtmlString('<h1 class="text-2xl text-center">Tickets will be available for purchase on ' . $nextTicketSaleDate . '</h1>'));
            } else {
                $ticketSchema[] = Placeholder::make('noTickets')
                    ->label('')
                    ->content(new HtmlString('<h1 class="text-2xl text-center">There are no tickets available for this event.</h1>'));
            }
        }

        $reserved = ReservedTicket::query()->currentUser()->currentEvent()->canBePurchased()->get();
        $reservedSchema = $reserved->map(function (ReservedTicket $ticket) {
            return ViewField::make('reserved.' . $ticket->id)
                ->model($ticket)
                ->default($this->reservedFilled == $ticket->id)
                ->rules([new TicketSaleRule])
                ->hiddenLabel()
                ->view('forms.components.reserved-ticket-field')
                ->viewData(['expirationDate' => Carbon::parse($ticket->expiration_date)->toDayDateTimeString()]);
        });

        if ($reservedSchema->count() > 0) {
            $schema = [
                Section::make('General Sale Tickets')
                    ->schema($ticketSchema),
                Section::make('Your Reserved Tickets')
                    ->description('These Tickets are reserved for you specifically.')
                    ->schema($reservedSchema->toArray()),
            ];
        } else {
            $schema = $ticketSchema;
        }

        $this->ticketCount = $tickets->count() + $reserved->count();

        return Wizard\Step::make('Select Tickets')
            ->schema($schema)
            ->afterValidation($this->createCart(...));
    }

    /**
     * Create the waiver step of the form wizard.  Hidden if no waiver is found or the user has already signed it.
     */
    protected function buildWaiverStep(): Step
    {
        /** @var User */
        $user = Auth::user();
        $username = $user->legal_name;

        return Wizard\Step::make('Waivers')
            ->schema([
                Placeholder::make('title')
                    ->content(new HtmlString('<h1 class="text-2xl">' . ($this->waiver->title ?? '') . '</h1>'))
                    ->label('')
                    ->dehydrated(false),
                Placeholder::make('waiver')
                    ->content(new HtmlString($this->waiver->content ?? ''))
                    ->label('')
                    ->dehydrated(false),
                TextInput::make('signature')
                    ->label('I agree to the terms of the waiver and understand that I am signing this waiver electronically.')
                    ->helperText('You must enter your full legal name as it is shown on your ID and listed in your profile.')
                    ->required()
                    ->in([$username])
                    ->validationMessages([
                        'required' => 'You must agree to the terms of the waiver and sign it.',
                        'in' => 'The entered value must match your legal name, as listed in your profile.',
                    ])
                    ->hidden($this->waiver === null),
            ])
            ->hidden(fn () => ! $this->waiver || $user->waivers()->where('waiver_id', $this->waiver->id)->count() > 0)
            ->afterValidation($this->createCompletedWaiver(...));
    }

    public function createCart(CartService $cartService, StripeService $stripeService): void
    {
        /**
         * We receive data in this format:
         * $data['tickets'] = [
         *    $id => $quantity
         * ]
         * $data['reserved'] = [
         *    $id => $boolean
         * ]
         *
         * We need to get the data into this format:
         * $tickets = [
         *    ['id' => $id, 'quantity' => $quantity],
         * ]
         *
         * $reserved = [$id, $id, $id]
         */
        $data = $this->form->getState();
        $tickets = (new Collection($data['tickets'] ?? []))->map(function ($quantity, $id) {
            return [
                'id' => $id,
                'quantity' => $quantity,
            ];
        })->toArray();
        $reserved = (new Collection($data['reserved'] ?? []))->filter(fn ($value) => $value === true)->keys()->toArray();

        $cartService->expireAllUnexpiredCarts();
        $cart = $cartService->createCartAndItems($tickets, $reserved);
        $session = $stripeService->createCheckoutFromCart($cart);
        $cart->setStripeCheckoutIdAndSave($session->id);
    }

    protected function createCompletedWaiver(): void
    {
        if ($this->waiver) {
            $this->waiver->completedWaivers()->create([
                'user_id' => Auth::id(),
                'form_data' => [
                    'signature' => $this->data['signature'],
                ],
            ]);
        }
    }

    public function checkoutAction(): ActionsAction
    {
        return ActionsAction::make('checkout')
            ->url(Checkout::getUrl());
    }

    public function ticketInfoAction(): ActionsAction
    {
        return ActionsAction::make('ticketInfo')
            ->link()
            ->size('large')
            ->label('How does ticketing work?')
            ->modalContent(view('filament.app.modals.ticket-info'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->stickyModalHeader();
    }

    public function checkout(): void
    {
        App::call([$this, 'createCart']);
        redirect(Checkout::getUrl());
    }

    #[On('active-event-updated')]
    public function mount(): void
    {
        if (! Event::where('id', Event::getCurrentEventId())->where('active', true)->exists()) {
            abort(404);
        }

        $this->form->fill();
        $this->refreshProperties();
    }
}
