<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\TimeSheet;

class FollowUpReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $timeSheet;
    public $lastRemark;

    /**
     * Create a new message instance.
     */
    public function __construct(TimeSheet $timeSheet, $lastRemark)
    {
        $this->timeSheet = $timeSheet;
        $this->lastRemark = $lastRemark;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.follow_up_reminder')
                    ->subject('Follow-Up Reminder - Client: ' . $this->timeSheet->full_name)
                    ->with([
                        'timeSheet' => $this->timeSheet,
                        'lastRemark' => $this->lastRemark,
                    ]);
    }
}
