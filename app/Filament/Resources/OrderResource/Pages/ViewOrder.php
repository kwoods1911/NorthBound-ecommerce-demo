<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderStatusService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Order $order */
        $order = $this->record;

        return collect($order->status->nextStates())
            ->map(fn (OrderStatus $to) => $this->transitionAction($to))
            ->all();
    }

    private function transitionAction(OrderStatus $to): Action
    {
        return Action::make("mark_{$to->value}")
            ->label("Mark as {$to->label()}")
            ->icon($to === OrderStatus::Cancelled ? 'heroicon-o-x-circle' : 'heroicon-o-arrow-right-circle')
            ->color($to === OrderStatus::Cancelled ? 'danger' : 'primary')
            ->requiresConfirmation()
            ->modalDescription("This moves the order to \"{$to->label()}\" and stamps the time. It does not change inventory.")
            ->action(function (OrderStatusService $service) use ($to) {
                $service->transition($this->record, $to);

                Notification::make()
                    ->success()
                    ->title("Order marked as {$to->label()}.")
                    ->send();
            });
    }
}
