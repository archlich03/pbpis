<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\AuditLog;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailController extends Controller
{
    /**
     * Show the email compose form.
     */
    public function compose(Meeting $meeting, Request $request)
    {
        // Authorization: Only privileged users can send emails
        if (!Auth::user()->isPrivileged()) {
            abort(403, 'Unauthorized to send emails.');
        }

        $templateType = $request->query('template', 'blank');
        $locale = app()->getLocale();

        // Get the appropriate template
        $template = match ($templateType) {
            'voting_start' => EmailService::getVotingStartTemplate($meeting, $locale),
            'agenda_change' => EmailService::getAgendaChangeTemplate($meeting, $locale),
            'voting_reminder' => EmailService::getVotingReminderTemplate($meeting, $locale),
            default => EmailService::getBlankTemplate($meeting, $locale),
        };

        // Get all body members' emails
        $recipients = $meeting->body->members->pluck('email')->toArray();

        return view('emails.compose', [
            'meeting' => $meeting,
            'template' => $template,
            'recipients' => $recipients,
            'templateType' => $templateType,
        ]);
    }

    /**
     * Queue the email for sending.
     */
    public function send(Meeting $meeting, Request $request, EmailService $emailService)
    {
        // Authorization: Only privileged users can send emails
        if (!Auth::user()->isPrivileged()) {
            abort(403, 'Unauthorized to send emails.');
        }

        // Check for 5-minute cooldown BEFORE validation
        $recentEmail = AuditLog::where('action', 'email_sent')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->whereJsonContains('details->meeting_id', $meeting->meeting_id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentEmail) {
            // Calculate when the cooldown expires (5 minutes after the email was sent)
            $cooldownExpires = $recentEmail->created_at->addMinutes(5);
            $remainingSeconds = now()->diffInSeconds($cooldownExpires, false);
            
            // If negative, cooldown has expired (shouldn't happen due to query, but safety check)
            if ($remainingSeconds <= 0) {
                // Allow sending
            } else {
                $remainingMinutes = ceil($remainingSeconds / 60);
                
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', __('Please wait :minutes more minute(s) before sending another email for this meeting.', ['minutes' => $remainingMinutes]));
            }
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
        ]);

        // Queue the email
        $emailService->queueEmail(
            subject: $validated['subject'],
            body: $validated['body'],
            recipients: $validated['recipients'],
            meetingId: $meeting->meeting_id,
            userId: Auth::id()
        );

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('success', __('Email has been sent successfully.'));
    }

    /**
     * Preview email template.
     */
    public function preview(Meeting $meeting, Request $request)
    {
        // Authorization: Only privileged users can preview emails
        if (!Auth::user()->isPrivileged()) {
            abort(403, 'Unauthorized to preview emails.');
        }

        $templateType = $request->query('template', 'blank');
        $locale = app()->getLocale();

        $template = match ($templateType) {
            'voting_start' => EmailService::getVotingStartTemplate($meeting, $locale),
            'agenda_change' => EmailService::getAgendaChangeTemplate($meeting, $locale),
            'voting_reminder' => EmailService::getVotingReminderTemplate($meeting, $locale),
            default => EmailService::getBlankTemplate($meeting, $locale),
        };

        return response()->json($template);
    }
}
