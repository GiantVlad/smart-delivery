<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderAssignedToTaskNotification extends Notification
{
    use Queueable;

    private string $uuid;
    private ?array $data;

    public function __construct(string $uuid, array $data)
    {
        $this->uuid = $uuid;
        $this->data = $data;

    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line("The order " . ($this->data['orderUuid'] ?? '') . " has been assigned to the task $this->uuid.")
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our Smart Delivery service!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
