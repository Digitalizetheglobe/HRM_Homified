<?php

namespace App\Mail;

use App\Models\BookingForm;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $employee;
    public $project;
    public $firstPayment;
    public $contactNumber;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(BookingForm $booking, $employee, $project, $firstPayment, $contactNumber = '+91 99231 96779')
    {
        $this->booking = $booking;
        $this->employee = $employee;
        $this->project = $project;
        $this->firstPayment = $firstPayment;
        $this->contactNumber = $contactNumber;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        try {
            $subject = 'Booking Confirmation - ' . ($this->project->project_name ?? 'Project');
            
            \Log::info('Building booking confirmation email', [
                'booking_id' => $this->booking->id,
                'subject' => $subject,
                'to' => $this->booking->primary_applicant_email
            ]);
            
            $mail = $this->subject($subject)
                        ->view('emails.booking_confirmation');
            
            // Attach PDF if generated successfully
            \Log::info('Generating PDF for email attachment');
            $pdfData = $this->generatePdf();
            if (!empty($pdfData)) {
                \Log::info('PDF generated successfully, attaching to email');
                $mail->attachData($pdfData, 'booking-confirmation-' . $this->booking->id . '.pdf', [
                    'mime' => 'application/pdf',
                ]);
            } else {
                \Log::warning('PDF generation failed or returned empty, continuing without attachment');
            }
            
            return $mail;
        } catch (\Exception $e) {
            \Log::error('Error building booking confirmation email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generate PDF for attachment
     */
    private function generatePdf()
    {
        try {
            if (class_exists('Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \PDF::loadView('booking.booking_form_pdf', ['booking' => $this->booking]);
                return $pdf->output();
            } else {
                // Fallback: return empty string if PDF library not available
                \Log::warning('PDF library not available for booking confirmation email');
                return '';
            }
        } catch (\Exception $e) {
            \Log::error('Error generating PDF for booking confirmation email: ' . $e->getMessage());
            return '';
        }
    }
}
