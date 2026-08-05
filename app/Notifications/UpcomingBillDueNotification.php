<?php

namespace App\Notifications;

use App\Models\UpcomingBill;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingBillDueNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly UpcomingBill $bill)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Upcoming household bill')
            ->line("{$this->bill->name} is due on {$this->bill->due_on?->toDateString()}.")
            ->line("Amount: {$this->bill->amount_minor} minor units.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'household_id' => $this->bill->household_id,
            'upcoming_bill_id' => $this->bill->id,
            'title' => 'Upcoming bill',
            'message' => "{$this->bill->name} is due on {$this->bill->due_on?->toDateString()}",
            'amount_minor' => $this->bill->amount_minor,
        ];
    }
}
