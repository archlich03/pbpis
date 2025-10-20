<?php

namespace App\Http\Controllers;

use App\Models\Body;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;

class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $sortable = ['meeting_date', 'status', 'vote_start', 'vote_end', 'secretary_id', 'body_id'];
        $sort = in_array($request->get('sort'), $sortable) ? $request->get('sort') : 'meeting_date';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $perPage = in_array($request->get('perPage'), ['10', '20', '50', '100']) ? $request->get('perPage') : 20;

        $meetings = Meeting::with(['body', 'secretary'])
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->appends($request->query());

        return view('meetings.index', compact('meetings'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Body $body)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $users = User::orderBy('name', 'asc')->get();
        return view('meetings.create', ['body' => $body, 'users' => $users]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Body $body)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $request->validate([
            'secretary_id' => ['required', 'integer', 'exists:users,user_id'],
            'is_evote' => ['required', 'in:0,1'],
            'meeting_date' => ['required', 'date'],
            'vote_start' => ['nullable', 'date'],
            'vote_end' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('vote_start') && $request->input('vote_end') && $request->input('vote_start') > $request->input('vote_end')) {
                    $fail('Vote end must be later than vote start');
                }
            }],
        ]);

        $meeting = new Meeting();
        $meeting->secretary_id = $request->input('secretary_id');
        $meeting->body_id = $body->body_id;
        $meeting->is_evote = $request->input('is_evote');
        $meeting->meeting_date = $request->input('meeting_date');
        $meeting->vote_start = $request->input('vote_start');
        $meeting->vote_end = $request->input('vote_end');
        $meeting->save();

        return redirect()->route('bodies.show', $body);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::findOrFail($id);
        $users = User::orderBy('name', 'asc')->get();

        if (!Auth::user()->isPrivileged() && !$meeting->body->members->contains(Auth::user())) {
            abort(403);
        }

        if ($meeting->status != 'Suplanuotas' && now() < $meeting->vote_start && now()) {
            $meeting->status = 'Suplanuotas';
            $meeting->save();
        } elseif ($meeting->status != 'Vyksta' && now() >= $meeting->vote_start && now() <= $meeting->vote_end) {
            $meeting->status = 'Vyksta';
            $meeting->save();
        } elseif ($meeting->status != 'Baigtas' && now() >= $meeting->vote_end) {
            $meeting->status = 'Baigtas';
            $meeting->save();
        }

        // Load all discussions for this meeting (for AI consent count)
        $allDiscussions = \App\Models\Discussion::whereIn('question_id', $meeting->questions->pluck('question_id'))->get();

        // Check AI feature availability and daily rate limit
        $geminiApiKey = config('services.gemini.api_key');
        $aiEnabled = !empty($geminiApiKey);
        $aiDailyLimit = config('services.gemini.max_requests_per_day', 10);
        $aiUsedToday = 0;
        
        if ($aiEnabled) {
            $aiUsedToday = \App\Models\AuditLog::where('action', 'ai_summary_generated')
                ->where('created_at', '>=', now()->startOfDay())
                ->count();
        }

        return view('meetings.show', [
            'meeting' => $meeting, 
            'users' => $users,
            'allDiscussions' => $allDiscussions,
            'aiEnabled' => $aiEnabled,
            'aiDailyLimit' => $aiDailyLimit,
            'aiUsedToday' => $aiUsedToday,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }
        $meeting = Meeting::findOrFail($id);
        $bodies = Body::orderBy('title', 'asc')->get();
        $users = User::orderBy('name', 'asc')->get();
        return view('meetings.edit', ['meeting' => $meeting, 'bodies' => $bodies, 'users' => $users]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Meeting $meeting)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $request->validate([
            'secretary_id' => ['required', 'integer', 'exists:users,user_id'],
            'is_evote' => ['required', 'in:0,1'],
            'meeting_date' => ['required', 'date'],
            'vote_start' => ['nullable', 'date'],
            'vote_end' => ['nullable', 'date', function ($attribute, $value, $fail) use ($request) {
                if ($request->input('vote_start') && $request->input('vote_end') && $request->input('vote_start') > $request->input('vote_end')) {
                    $fail('Vote end must be later than vote start');
                }
            }],
        ]);

        // First update the meeting fields with the new input values
        $meeting->secretary_id = $request->input('secretary_id');
        $meeting->is_evote = $request->input('is_evote');
        $meeting->meeting_date = $request->input('meeting_date');
        $meeting->vote_start = $request->input('vote_start');
        $meeting->vote_end = $request->input('vote_end');

        $now = Carbon::now();
        $voteStart = Carbon::parse($meeting->vote_start);
        $voteEnd = Carbon::parse($meeting->vote_end);

        if ($now->lt($voteStart)) {
            $meeting->status = 'Suplanuotas';  // Planned
        } elseif ($now->between($voteStart, $voteEnd)) {
            $meeting->status = 'Vyksta';       // Started
        } else {
            $meeting->status = 'Baigtas';      // Finished
        }

        $meeting->save();

        return redirect()->route('meetings.show', ['meeting' => $meeting]);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $meeting = Meeting::findOrFail($id);
        $body = $meeting->body; // Get the associated body before deleting the meeting

        // Step 1: Get all questions related to this meeting
        $questions = $meeting->questions; // This fetches the collection of Question models

        // Step 2: Iterate through each question and delete its associated votes
        foreach ($questions as $question) {
            $question->votes()->delete(); // Correctly deletes votes for each question
        }

        // Step 3: After all votes are deleted, delete the questions belonging to this meeting
        $meeting->questions()->delete(); // Correctly deletes questions associated with this meeting

        // Step 4: Finally, delete the meeting itself
        $meeting->delete();

        // Redirect back to the show page of the parent body
        return redirect()->route('bodies.show', $body)
                         ->with('success', 'Meeting and all related data deleted successfully.');
    }

    
    public function protocol(Meeting $meeting)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        return view('meetings.protocol', ['meeting' => $meeting]);
    }

    public function protocolPDF(Meeting $meeting)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $pdf = PDF::loadView('meetings.protocol', ['meeting' => $meeting]);
        $name = $meeting->body->title . ' ' . ($meeting->body->is_ba_sp ? 'BA' : 'MA') . ' ' . $meeting->meeting_date->format('Y-m-d') . ' protokolas.pdf';
        return $pdf->download($name); 
    }

    public function protocolDOCX(Meeting $meeting)
    {
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        $phpWord = new PhpWord();
        
        // Define numbered list styles - separate for agenda and questions
        $phpWord->addNumberingStyle(
            'agendaNumbering',
            [
                'type' => 'multilevel',
                'levels' => [
                    [
                        'format' => 'decimal',
                        'text' => '%1.',
                        'left' => 360,
                        'hanging' => 360,
                        'tabPos' => 360,
                    ],
                ],
            ]
        );
        
        $phpWord->addNumberingStyle(
            'questionsNumbering',
            [
                'type' => 'multilevel',
                'levels' => [
                    [
                        'format' => 'decimal',
                        'text' => '%1.',
                        'left' => 360,
                        'hanging' => 360,
                        'tabPos' => 360,
                    ],
                ],
            ]
        );
        
        // Set default font for HTML content
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);
        
        $section = $phpWord->addSection([
            'marginLeft' => 1134,
            'marginRight' => 1134,
            'marginTop' => 1134,
            'marginBottom' => 1134,
        ]);

        // Title styles
        $titleStyle = ['name' => 'Times New Roman', 'size' => 12, 'bold' => true];
        $normalStyle = ['name' => 'Times New Roman', 'size' => 12];
        $centerAlign = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];

        // Title section
        $section->addText('VILNIAUS UNIVERSITETO', $titleStyle, $centerAlign);
        $section->addText('KAUNO FAKULTETO', $titleStyle, $centerAlign);
        $section->addText(
            ($meeting->body->is_ba_sp ? 'PIRMOSIOS' : 'ANTROSIOS') . ' PAKOPOS STUDIJŲ PROGRAMOS',
            $titleStyle,
            $centerAlign
        );
        $section->addText(
            '„' . mb_strtoupper($meeting->body->title) . '" KOMITETAS',
            $titleStyle,
            $centerAlign
        );
        $section->addText(
            ($meeting->is_evote ? 'ELEKTRONINIO ' : '') . 'POSĖDŽIO PROTOKOLAS',
            $titleStyle,
            $centerAlign
        );
        $section->addTextBreak();

        // Meeting details
        if ($meeting->is_evote) {
            $section->addText(
                'Balsavimas el. būdu vyko nuo ' . $meeting->vote_start->format('Y-m-d H:i') . 
                ' iki ' . $meeting->vote_end->format('Y-m-d H:i') . '.',
                $normalStyle
            );
        } else {
            $section->addText(
                'Posėdis vyko ' . $meeting->meeting_date->format('Y-m-d') . '; ' . 
                $meeting->vote_start->format('H:i') . ' - ' . $meeting->vote_end->format('H:i') . '.',
                $normalStyle
            );
        }

        $section->addText(
            'Posėdžio ' . ($meeting->body->chairman->gender ? 'pirmininkas' : 'pirmininkė') . ': ' .
            $meeting->body->chairman->pedagogical_name . ' ' . $meeting->body->chairman->name . '.',
            $normalStyle
        );

        $section->addText(
            'Posėdžio ' . ($meeting->secretary->gender ? 'sekretorius' : 'sekretorė') . ': ' .
            $meeting->secretary->pedagogical_name . ' ' . $meeting->secretary->name . '.',
            $normalStyle
        );

        // Attendees
        $attendeesText = 'Posėdyje dalyvavo: ';
        foreach ($meeting->body->members as $index => $member) {
            $attendeesText .= $member->pedagogical_name . ' ' . $member->name;
            $attendeesText .= ($index === count($meeting->body->members) - 1) ? '.' : '; ';
        }
        $section->addText($attendeesText, $normalStyle);
        
        // Add line break before agenda
        $section->addTextBreak();

        // Agenda
        $section->addText('DARBOTVARKĖ:', $normalStyle);
        foreach ($meeting->questions as $index => $question) {
            $section->addListItem(
                $question->title . ($index === count($meeting->questions) - 1 ? '.' : ';'),
                0,
                $normalStyle,
                'agendaNumbering'
            );
        }

        // Questions details - match HTML format exactly with separate numbering
        foreach ($meeting->questions as $index => $question) {
            // Start numbered list item with just "SVARSTYTA. Title."
            $section->addListItem(
                'SVARSTYTA. ' . $question->title . '.',
                0,
                $normalStyle,
                'questionsNumbering'
            );
            
            // Add presenter info on new line if exists
            if ($question->presenter) {
                $presenterText = ($question->presenter->gender ? 'Pranešėjas' : 'Pranešėja');
                
                if ($question->presenter->user_id == $meeting->body->chairman->user_id) {
                    $presenterText .= ' ' . ($meeting->body->chairman->gender ? 'SPK pirmininkas' : 'SPK pirmininkė');
                }
                
                $presenterText .= ' ' . $question->presenter->pedagogical_name . ' ' . $question->presenter->name . '.';
                $section->addText($presenterText, $normalStyle);
            }
            
            // Add summary with HTML support
            if ($question->summary) {
                Html::addHtml($section, $question->summary, false, false);
            } else {
                $section->addText('Vyko diskusija.', $normalStyle);
            }

            // Voting results
            if ($question->type != "Nebalsuoti") {
                $statuses = [];
                foreach (\App\Models\Vote::STATUSES as $status) {
                    $count = $question->votes()->where('choice', $status)->count();
                    $statuses[] = [$status, $count];
                }

                $forVotes = collect($statuses)->firstWhere(0, 'Už')[1] ?? 0;
                
                if ($forVotes >= count($meeting->body->members)) {
                    $section->addText('Pritarta bendru sutarimu.', $normalStyle);
                } else {
                    $votingText = 'Balsuojama: Už: ' . ($statuses[0][1] ?? 0) . '; Prieš: ' . ($statuses[1][1] ?? 0) . '; Susilaiko: ' . ($statuses[2][1] ?? 0) . '.';
                    $section->addText($votingText, $normalStyle);
                }

                if ($question->decision != '') {
                    $section->addText('NUTARTA. ' . $question->decision, $normalStyle);
                }
            }
        }

        // Generate filename
        $filename = $meeting->body->title . ' ' . 
                   ($meeting->body->is_ba_sp ? 'BA' : 'MA') . ' ' . 
                   $meeting->meeting_date->format('Y-m-d') . ' protokolas.docx';

        // Save to temp file and download
        $tempFile = tempnam(sys_get_temp_dir(), 'protocol');
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Display voting report for the meeting
     *
     * @param Meeting $meeting
     * @return \Illuminate\Http\Response
     */
    public function votingReport(Meeting $meeting)
    {
        // Only allow access to IT admins and secretaries
        if (!Auth::user()->isPrivileged()) {
            abort(403);
        }

        // Get all present members (those with attendance records), sorted alphabetically
        $attendanceRecords = $meeting->attendances()->pluck('user_id');
        $presentMembers = $meeting->body->members
            ->filter(function ($member) use ($attendanceRecords) {
                return $attendanceRecords->contains($member->user_id);
            })
            ->sortBy('name');

        // Get all questions with votes
        $questions = $meeting->questions()->with(['votes' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->get();

        return view('meetings.voting-report', compact('meeting', 'presentMembers', 'questions'));
    }
}