<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PersonalReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $employee;

    public $monthName;

    public $pdfContent;

    public $filename;

    public function __construct($employee, $monthName, $pdfContent, $filename)
    {
        $this->employee = $employee;
        $this->monthName = $monthName;
        $this->pdfContent = $pdfContent;
        $this->filename = $filename;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Monthly Attendance Report - {$this->monthName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.personal_report',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->filename)
                ->withMime('application/pdf'),
        ];
    }
}
