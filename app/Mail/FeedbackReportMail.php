<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackReportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $reportType,
        public readonly string $title,
        public readonly string $description,
        public readonly string $reporterName,
        public readonly string $reporterEmail,
        public readonly string $pageUrl,
        public readonly ?string $pageTitle = null,
        /** @var array<int, UploadedFile> */
        public readonly array $screenshots = [],
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->reporterEmail, $this->reporterName)],
            subject: sprintf('%s %s - %s', $this->reportTypeEmoji(), $this->reportTypeLabel(), $this->title),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.feedback-report',
            with: [
                'reportTypeLabel' => $this->reportTypeLabel(),
                'reportTypeEmoji' => $this->reportTypeEmoji(),
                'title' => $this->title,
                'description' => $this->description,
                'reporterName' => $this->reporterName,
                'reporterEmail' => $this->reporterEmail,
                'pageUrl' => $this->pageUrl,
                'pageTitle' => $this->pageTitle,
                'screenshots' => array_map(
                    fn (UploadedFile $file) => $file->getClientOriginalName(),
                    $this->screenshots
                ),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return array_map(
            fn (UploadedFile $file) => Attachment::fromUploadedFile($file),
            $this->screenshots
        );
    }

    protected function reportTypeLabel(): string
    {
        return $this->reportType === 'bug' ? 'Bug' : 'Sugerencia';
    }

    protected function reportTypeEmoji(): string
    {
        return $this->reportType === 'bug' ? '🐛' : '💡';
    }
}
