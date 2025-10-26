<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Meeting;
use App\Models\Question;
use App\Models\MeetingAttendance;
use App\Models\AuditLog;
use App\Services\GeminiAIService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DiscussionController extends Controller
{
    /**
     * Store a new discussion comment.
     */
    public function store(Request $request, Meeting $meeting, Question $question): RedirectResponse
    {
        $user = Auth::user();

        // Authorization: Only during voting phase (Vyksta)
        if ($meeting->status !== 'Vyksta') {
            abort(403, 'Discussions are only allowed during voting phase.');
        }

        // Only e-vote meetings have discussions
        if (!$meeting->is_evote) {
            abort(403, 'Discussions are only available for e-vote meetings.');
        }

        // Check if user is authorized (body member, secretary, or IT admin)
        $isBodyMember = $meeting->body->members->contains($user);
        $isSecretary = $user->role === 'Sekretorius';
        $isITAdmin = $user->role === 'IT administratorius';

        if (!$isBodyMember && !$isSecretary && !$isITAdmin) {
            abort(403, 'You are not authorized to participate in this discussion.');
        }

        // Rate limiting: 1 message per user per 10 seconds (prevent spam)
        $lastComment = Discussion::where('user_id', $user->user_id)
            ->where('question_id', $question->question_id)
            ->where('created_at', '>', now()->subSeconds(10))
            ->first();
        
        if ($lastComment) {
            $secondsRemaining = 10 - now()->diffInSeconds($lastComment->created_at);
            return redirect()
                ->route('meetings.show', $meeting)
                ->with('error', __('Please wait :seconds seconds before posting another comment.', ['seconds' => $secondsRemaining]));
        }

        // Validate
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:discussions,discussion_id',
        ]);

        // If parent_id is provided, verify it belongs to the same question
        if (!empty($validated['parent_id'])) {
            $parent = Discussion::findOrFail($validated['parent_id']);
            if ($parent->question_id !== $question->question_id) {
                abort(403, 'Cannot reply to a discussion from a different question.');
            }
        }

        // Create discussion
        $discussion = Discussion::create([
            'question_id' => $question->question_id,
            'user_id' => $user->user_id,
            'parent_id' => $validated['parent_id'] ?? null,
            'content' => $validated['content'],
        ]);

        // Auto-mark body members as attending when they post a comment
        if ($isBodyMember) {
            $attendance = MeetingAttendance::firstOrCreate(
                [
                    'meeting_id' => $meeting->meeting_id,
                    'user_id' => $user->user_id,
                ],
                [
                    'status' => 'Dalyvauja',
                ]
            );
            
            // If attendance record exists but status is not attending, update it
            if ($attendance->status !== 'Dalyvauja') {
                $attendance->update(['status' => 'Dalyvauja']);
            }
        }

        // Store active question in session to preserve tab state
        session(['active_question_id' => $question->question_id]);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('status', 'discussion-created');
    }

    /**
     * Update an existing discussion comment.
     */
    public function update(Request $request, Meeting $meeting, Question $question, Discussion $discussion): RedirectResponse
    {
        $user = Auth::user();

        // Authorization: Only during voting phase
        if ($meeting->status !== 'Vyksta') {
            abort(403, 'Discussions can only be edited during voting phase.');
        }

        // Only e-vote meetings have discussions
        if (!$meeting->is_evote) {
            abort(403);
        }

        // Only the author can edit their own discussion
        if ($discussion->user_id !== $user->user_id) {
            abort(403, 'You can only edit your own comments.');
        }

        // Validate
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        // Update
        $discussion->update([
            'content' => $validated['content'],
        ]);

        // Store active question in session to preserve tab state
        session(['active_question_id' => $question->question_id]);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('status', 'discussion-updated');
    }

    /**
     * Delete a discussion comment.
     */
    public function destroy(Request $request, Meeting $meeting, Question $question, Discussion $discussion): RedirectResponse
    {
        $user = Auth::user();

        // Authorization: Only during voting phase
        if ($meeting->status !== 'Vyksta') {
            abort(403, 'Discussions can only be deleted during voting phase.');
        }

        // Only e-vote meetings have discussions
        if (!$meeting->is_evote) {
            abort(403);
        }

        // Check authorization: author, secretary, or IT admin
        $isAuthor = $discussion->user_id === $user->user_id;
        $isSecretary = $user->role === 'Sekretorius';
        $isITAdmin = $user->role === 'IT administratorius';

        if (!$isAuthor && !$isSecretary && !$isITAdmin) {
            abort(403, 'You are not authorized to delete this comment.');
        }

        // Soft delete (will cascade to replies)
        $discussion->delete();

        // Store active question in session to preserve tab state
        session(['active_question_id' => $question->question_id]);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('status', 'discussion-deleted');
    }

    /**
     * Toggle AI consent for a discussion comment.
     */
    public function toggleAIConsent(Request $request, Meeting $meeting, Question $question, Discussion $discussion): JsonResponse
    {
        $user = Auth::user();

        // Only secretary and IT admin can toggle AI consent
        if (!in_array($user->role, ['Sekretorius', 'IT administratorius'])) {
            abort(403, 'Only secretaries and IT administrators can manage AI consent.');
        }

        // Only e-vote meetings have discussions
        if (!$meeting->is_evote) {
            abort(403);
        }

        // Toggle the consent
        $discussion->ai_consent = !$discussion->ai_consent;
        $discussion->save();

        return response()->json([
            'success' => true,
            'ai_consent' => $discussion->ai_consent,
        ]);
    }

    /**
     * Generate AI summary for a question's discussions.
     */
    public function generateAISummary(Request $request, Meeting $meeting, Question $question, GeminiAIService $geminiService): RedirectResponse
    {
        $user = Auth::user();

        // Only secretary and IT admin can generate AI summaries
        if (!in_array($user->role, ['Sekretorius', 'IT administratorius'])) {
            abort(403, 'Only secretaries and IT administrators can generate AI summaries.');
        }

        // Only e-vote meetings have discussions
        if (!$meeting->is_evote) {
            abort(403);
        }

        // Meeting must be finished
        if ($meeting->status !== 'Baigtas') {
            return redirect()
                ->route('meetings.show', $meeting)
                ->with('error', __('AI summaries can only be generated for finished meetings.'));
        }

        // Check daily rate limit
        $maxRequestsPerDay = config('services.gemini.max_requests_per_day', 10);
        $startOfDay = now()->startOfDay();
        $todayGenerations = AuditLog::where('action', 'ai_summary_generated')
            ->where('created_at', '>=', $startOfDay)
            ->count();

        if ($todayGenerations >= $maxRequestsPerDay) {
            return redirect()
                ->route('meetings.show', $meeting)
                ->with('error', __('Daily AI summary generation limit reached (:count/:max). Please try again tomorrow.', [
                    'count' => $todayGenerations,
                    'max' => $maxRequestsPerDay
                ]));
        }

        // Check 5-minute cooldown per question
        $fiveMinutesAgo = now()->subMinutes(5);
        $recentGeneration = AuditLog::where('action', 'ai_summary_generated')
            ->where('user_id', $user->user_id)
            ->where('created_at', '>=', $fiveMinutesAgo)
            ->whereRaw("JSON_EXTRACT(details, '$.question_id') = ?", [$question->question_id])
            ->first();

        if ($recentGeneration) {
            $minutesRemaining = 5 - now()->diffInMinutes($recentGeneration->created_at);
            return redirect()
                ->route('meetings.show', $meeting)
                ->with('error', __('Please wait :minutes more minute(s) before generating another AI summary for this question.', ['minutes' => $minutesRemaining]));
        }

        // Get all discussions with AI consent, ordered from oldest to newest
        $discussions = Discussion::where('question_id', $question->question_id)
            ->where('ai_consent', true)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($discussions->isEmpty()) {
            return redirect()
                ->route('meetings.show', $meeting)
                ->with('error', __('No comments with AI consent found for this question.'));
        }

        // Prepare comments for AI with parent context
        $comments = $discussions->map(function ($discussion) use ($discussions) {
            $comment = [
                'id' => $discussion->discussion_id,
                'name' => $discussion->user->name,
                'content' => $discussion->content,
                'parent_id' => $discussion->parent_id,
            ];
            
            // If this is a reply, include parent comment info
            if ($discussion->parent_id) {
                $parent = $discussions->firstWhere('discussion_id', $discussion->parent_id);
                if ($parent) {
                    $comment['parent_author'] = $parent->user->name;
                }
            }
            
            return $comment;
        })->toArray();

        // Generate summary
        $result = $geminiService->generateMeetingSummary($comments, $question->title);

        if (!$result['success']) {
            // Log the failure to audit log
            AuditLog::log(
                $user->user_id,
                'ai_summary_failed',
                $request->ip(),
                $request->userAgent(),
                [
                    'meeting_id' => $meeting->meeting_id,
                    'question_id' => $question->question_id,
                    'question_title' => $question->title,
                    'comments_count' => $discussions->count(),
                    'error' => $result['error'],
                ]
            );

            return redirect()
                ->route('meetings.show', $meeting)
                ->with('error', __('Failed to generate AI summary. Please try again later.') . ' (' . $result['error'] . ')');
        }

        $summary = $result['summary'];

        // Truncate if necessary (summary column limit)
        $summary = $geminiService->truncateSummary($summary, 5000);

        // Update question's summary
        $question->update(['summary' => $summary]);

        // Log the successful AI summary generation
        AuditLog::log(
            $user->user_id,
            'ai_summary_generated',
            $request->ip(),
            $request->userAgent(),
            [
                'meeting_id' => $meeting->meeting_id,
                'question_id' => $question->question_id,
                'question_title' => $question->title,
                'comments_count' => $discussions->count(),
                'summary_length' => mb_strlen($summary),
            ]
        );

        // Store active question in session
        session(['active_question_id' => $question->question_id]);

        return redirect()
            ->route('meetings.show', $meeting)
            ->with('success', __('AI summary generated successfully and saved to question summary.'));
    }
}
