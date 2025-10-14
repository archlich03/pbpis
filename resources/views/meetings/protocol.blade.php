<title>{{ __('Meeting Protocol') }}</title>
<style>
@font-face {
    font-family:'FreeSerif';
    src:url("{{ storage_path('fonts/FreeSerif-Regular.ttf') }}") format("truetype");
    font-weight:normal;
    font-style:normal;
}
@font-face {
    font-family:'FreeSerif';
    src:url("{{ storage_path('fonts/FreeSerif-Bold.ttf') }}") format("truetype");
    font-weight:bold;
    font-style:normal;
}
@font-face {
    font-family:'FreeSerif';
    src:url("{{ storage_path('fonts/FreeSerif-Italic.ttf') }}") format("truetype");
    font-weight:normal;
    font-style:italic;
}
@font-face {
    font-family:'FreeSerif';
    src:url("{{ storage_path('fonts/FreeSerif-BoldItalic.ttf') }}") format("truetype");
    font-weight:bold;
    font-style:italic;
}
body {
    font-family: 'Times New Roman', Times, serif;
    font-size: 16px;
    margin: 0;
    padding: 0;
    line-height: 1;  /* 1 means single spacing */
}

p, div, h1, h2, h3 {
    margin: 0;
    padding: 0;
    line-height: 1; 
}
</style>
<div style="margin: 0 auto; width: 100%;">
    <div style="text-align: center; font-weight: bold; font-size: 16px;">
        <span>
            VILNIAUS UNIVERSITETO<br>KAUNO FAKULTETO
        </span><br>
        <span>
            {{ $meeting->body->is_ba_sp? 'PIRMOSIOS' : 'ANTROSIOS' }} PAKOPOS STUDIJŲ PROGRAMOS
        </span><br>

        <span>
            „{{ \Illuminate\Support\Str::upper($meeting->body->title) }}“ KOMITETAS<br>
        </span><br>
        
        <span>
            {{ $meeting->is_evote? 'ELEKTRONINIO' : '' }} POSĖDŽIO PROTOKOLAS<br>
        </span><br>
    </div>
    <div>
        @if ($meeting->is_evote)
            <span>
                Balsavimas el. būdu vyko nuo {{ $meeting->vote_start->format('Y-m-d H:i') }} iki {{ $meeting->vote_end->format('Y-m-d H:i') }}.
            </span><br>
        @else
            <span>
                Posėdis vyko {{ $meeting->meeting_date->format('Y-m-d') }}; {{ $meeting->vote_start->format('H:i') }} - {{ $meeting->vote_end->format('H:i') }}.
            </span><br>
        @endif
        <span>
            Posėdžio 
            {{ $meeting->body->chairman->gender? 'pirmininkas' : 'pirmininkė' }}: {{ $meeting->body->chairman->pedagogical_name }} {{ $meeting->body->chairman->name }}.
        </span><br>
        <span>
            Posėdžio 
            {{ $meeting->secretary->gender? 'sekretorius' : 'sekretorė' }}: {{ $meeting->secretary->pedagogical_name }} {{ $meeting->secretary->name }}.
        </span><br>
        <span>
            Posėdyje dalyvavo: 
            @foreach ($meeting->body->members as $member)
                {{ $member->pedagogical_name }} {{ $member->name }}{{ $loop->last ? '.' : ';' }}
            @endforeach
        </span><br>
        <span>DARBOTVARKĖ:</span><br>
        <ol style='margin-top: 0px; margin-bottom: 0px;'>
            @foreach ($meeting->questions as $question)
                <li>{{ $question->title }}{{ $loop->last ? '.' : ';' }}</li>
            @endforeach
        </ol>
        <ol style='margin-top: 0px; margin-bottom: 0px;'>
            @foreach ($meeting->questions as $question)
                <li>
                    <span>SVARSTYTA. {{ $question->title }}.</span><br>
                    @if($question->presenter)
                        <span>
                            {{ $question->presenter->gender? 'Pranešėjas' : 'Pranešėja' }} 
                            {{ ($question->presenter->user_id == $meeting->body->chairman->user_id && $meeting->body->chairman->gender == 1) ? 'SPK pirmininkas ' : '' }}
                            {{ ($question->presenter->user_id == $meeting->body->chairman->user_id && $meeting->body->chairman->gender == 0) ? 'SPK pirmininkė ' : '' }}
                            {{ $question->presenter->pedagogical_name }} {{ $question->presenter->name }}.
                        </span> 
                    @endif
                    <div class="prose prose-sm max-w-none inline">
                        {!! $question->summary? $question->summary : 'Vyko diskusija.' !!}
                    </div>
                    @if ($question->type != "Nebalsuoti")
                        @php
                            $statuses = [];
                            foreach (\App\Models\Vote::STATUSES as $status) {
                                $count = $question->votes()->where('choice', $status)->count();
                                array_push($statuses, [$status, $count]);
                            }
                        @endphp
                        @if (collect($statuses)->firstWhere(0, 'Už')[1] >= count($meeting->body->members))
                            <span>Pritarta bendru sutarimu.</span>
                        @else
                            <span>
                                Balsuojama: 
                                @foreach ($statuses as $status)
                                    <span>
                                        {{ $status[0] }}: {{ $status[1] }}{{ $loop->last ? '.' : ';' }}
                                    </span>
                                @endforeach
                            </span>
                        @endif
                        <span><br>
                            {{ $question->decision != ''? 'NUTARTA. ' : '' }}{{ $question->decision }}
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</div>