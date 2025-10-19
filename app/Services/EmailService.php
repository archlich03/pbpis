<?php

namespace App\Services;

use App\Models\EmailQueue;
use App\Models\Meeting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Queue an email for sending.
     */
    public function queueEmail(
        string $subject,
        string $body,
        array $recipients,
        ?string $meetingId = null,
        ?int $userId = null
    ): EmailQueue {
        return EmailQueue::create([
            'subject' => $subject,
            'body' => $body,
            'recipients' => $recipients,
            'meeting_id' => $meetingId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Process pending emails (up to $limit).
     */
    public function processPendingEmails(int $limit = 5): int
    {
        $emails = EmailQueue::orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($emails as $email) {
            try {
                $this->sendEmail($email);
                
                // Log to audit
                AuditLogService::log(
                    $email->user_id,
                    'email_sent',
                    '127.0.0.1', // Cron job IP
                    'Laravel Scheduler',
                    [
                        'email_id' => $email->email_id,
                        'subject' => $email->subject,
                        'recipient_count' => count($email->recipients),
                        'meeting_id' => $email->meeting_id,
                    ]
                );
                
                // Delete email after successful send
                $email->delete();
                
                $processed++;
            } catch (\Exception $e) {
                // Check if it's an SMTP configuration error
                $isSMTPError = str_contains($e->getMessage(), 'Connection') 
                    || str_contains($e->getMessage(), 'SMTP') 
                    || str_contains($e->getMessage(), 'authentication');
                
                Log::error('Failed to send email', [
                    'email_id' => $email->email_id,
                    'subject' => $email->subject,
                    'error' => $e->getMessage(),
                    'smtp_error' => $isSMTPError,
                ]);
                
                // If SMTP is not configured, keep email in queue for retry
                // Otherwise, delete to prevent retry loop
                if (!$isSMTPError) {
                    $email->delete();
                }
                // If SMTP error, email stays in queue and will be retried next run
            }
        }

        return $processed;
    }

    /**
     * Send an email using BCC.
     */
    private function sendEmail(EmailQueue $email): void
    {
        // Get the sender's email (system email)
        $fromEmail = config('mail.from.address');
        $fromName = config('mail.from.name');

        // Add basic email styling for better rendering
        $styledBody = '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">' 
            . $email->body 
            . '</div>';

        Mail::send([], [], function ($message) use ($email, $fromEmail, $fromName, $styledBody) {
            $message->from($fromEmail, $fromName)
                ->to($fromEmail) // Send to self
                ->bcc($email->recipients) // Add all recipients as BCC
                ->subject($email->subject)
                ->html($styledBody);
        });
    }

    /**
     * Get email template for voting period start.
     */
    public static function getVotingStartTemplate(Meeting $meeting, string $locale = 'lt'): array
    {
        if ($locale === 'en') {
            return [
                'subject' => 'Voting Period Started - ' . $meeting->body->title,
                'body' => "<p>Dear Body Member,</p>

<p>The voting period for the meeting of <strong>{$meeting->body->title}</strong> has started.</p>

<p><strong>Meeting Date:</strong> {$meeting->meeting_date->format('Y-m-d')}</p>
<p><strong>Voting Period:</strong> {$meeting->vote_start->format('Y-m-d H:i')} - {$meeting->vote_end->format('Y-m-d H:i')}</p>

<p>Please log in to the system and cast your votes on the agenda items.</p>

<p>Meeting link: " . route('meetings.show', $meeting) . "</p>

<p>Your participation is important for the decision-making process.</p>

<p>Best regards,<br>
{$meeting->secretary->name}</p>",
            ];
        }

        return [
            'subject' => 'Balsavavimas dėl „' . $meeting->body->title . '“ darinio posėdžio',
            'body' => "<p>Sveiki,</p>

<p>Prasidėjo balsavimo laikotarpis <strong>{$meeting->body->title}</strong> posėdžiui.</p>

<p><strong>Posėdžio data:</strong> {$meeting->meeting_date->format('Y-m-d')}</p>
<p><strong>Balsavimo laikotarpis:</strong> {$meeting->vote_start->format('Y-m-d H:i')} - {$meeting->vote_end->format('Y-m-d H:i')}</p>

<p>Prašome prisijungti prie sistemos ir balsuoti dėl darbotvarkės klausimų.</p>

<p>Posėdžio nuoroda: " . route('meetings.show', $meeting) . "</p>

<p>Jūsų dalyvavimas yra svarbus sprendimų priėmimo procesui.</p>

<p>Pagarbiai,<br>
{$meeting->secretary->name}</p>",
        ];
    }

    /**
     * Get email template for agenda change.
     */
    public static function getAgendaChangeTemplate(Meeting $meeting, string $locale = 'lt'): array
    {
        if ($locale === 'en') {
            return [
                'subject' => 'Agenda Changed for "' . $meeting->body->title . '" body meeting',
                'body' => "<p>Hello,</p>

<p>The agenda for the meeting of <strong>{$meeting->body->title}</strong> has been changed.</p>

<p><strong>Meeting Date:</strong> {$meeting->meeting_date->format('Y-m-d')}</p>
<p><strong>Voting Period:</strong> {$meeting->vote_start->format('Y-m-d H:i')} - {$meeting->vote_end->format('Y-m-d H:i')}</p>

<p>Please review the updated agenda items and cast your votes accordingly.</p>

<p>Meeting link: " . route('meetings.show', $meeting) . "</p>

<p>Best regards,<br>
{$meeting->secretary->name}</p>",
            ];
        }

        return [
            'subject' => 'Darbotvarkė pakeista dėl „' . $meeting->body->title . '“ darinio posėdžio',
            'body' => "<p>Sveiki,</p>

<p><strong>{$meeting->body->title}</strong> posėdžio darbotvarkė buvo pakeista.</p>

<p><strong>Posėdžio data:</strong> {$meeting->meeting_date->format('Y-m-d')}</p>
<p><strong>Balsavimo laikotarpis:</strong> {$meeting->vote_start->format('Y-m-d H:i')} - {$meeting->vote_end->format('Y-m-d H:i')}</p>

<p>Prašome peržiūrėti atnaujintus darbotvarkės klausimus ir balsuoti atitinkamai.</p>

<p>Posėdžio nuoroda: " . route('meetings.show', $meeting) . "</p>

<p>Pagarbiai,<br>
{$meeting->secretary->name}</p>",
        ];
    }

    /**
     * Get email template for voting reminder.
     */
    public static function getVotingReminderTemplate(Meeting $meeting, string $locale = 'lt'): array
    {
        if ($locale === 'en') {
            return [
                'subject' => 'Voting Reminder for "' . $meeting->body->title . '" body meeting',
                'body' => "<p>Hello,</p>

<p>This is a reminder to cast your votes for the meeting of <strong>{$meeting->body->title}</strong>.</p>

<p><strong>Meeting Date:</strong> {$meeting->meeting_date->format('Y-m-d')}</p>
<p><strong>Voting Deadline:</strong> {$meeting->vote_end->format('Y-m-d H:i')}</p>

<p>If you haven't voted yet, please do so before the deadline.</p>

<p>Meeting link: " . route('meetings.show', $meeting) . "</p>

<p>Best regards,<br>
{$meeting->secretary->name}</p>",
            ];
        }

        return [
            'subject' => 'Priminimas balsuoti dėl „' . $meeting->body->title . '“ darinio posėdžio',
            'body' => "<p>Sveiki,</p>

<p>Primename apie balsavimą <strong>{$meeting->body->title}</strong> posėdyje.</p>

<p><strong>Posėdžio data:</strong> {$meeting->meeting_date->format('Y-m-d')}</p>
<p><strong>Balsavimo pabaiga:</strong> {$meeting->vote_end->format('Y-m-d H:i')}</p>

<p>Jei dar nebalsavote, prašome tai padaryti iki nurodyto termino.</p>

<p>Posėdžio nuoroda: " . route('meetings.show', $meeting) . "</p>

<p>Pagarbiai,<br>
{$meeting->secretary->name}</p>",
        ];
    }

    /**
     * Get blank email template.
     */
    public static function getBlankTemplate(Meeting $meeting, string $locale = 'lt'): array
    {
        if ($locale === 'en') {
            return [
                'subject' => 'Information regarding "' . $meeting->body->title . '" body meeting',
                'body' => "<p>Hello,</p>

<p>[Your message here]</p>

<p>Meeting link: " . route('meetings.show', $meeting) . "</p>

<p>Regards,<br>
{$meeting->secretary->name}</p>",
            ];
        }

        return [
            'subject' => 'Informacija dėl „' . $meeting->body->title . '“ darinio posėdžio',
            'body' => "<p>Sveiki,</p>

<p>[Jūsų žinutė čia]</p>

<p>Posėdžio nuoroda: " . route('meetings.show', $meeting) . "</p>

<p>Pagarbiai,<br>
{$meeting->secretary->name}</p>",
        ];
    }
}
