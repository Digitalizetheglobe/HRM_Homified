<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MissedPunchOutNotification extends Notification
{
    use Queueable;

    public $notificationData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $notificationData)
    {
        $this->notificationData = $notificationData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->notificationData['title'] ?? 'Missed Punch-Out Alert',
            'message' => $this->notificationData['message'] ?? 'You missed your punch out.',
            'attendance_id' => $this->notificationData['attendance_id'] ?? null,
            'date' => $this->notificationData['date'] ?? null,
            'clock_in' => $this->notificationData['clock_in'] ?? null,
            'url' => $this->notificationData['url'] ?? route('attendanceemployee.index'),
        ];
    }
}
