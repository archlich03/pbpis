<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balsavimo ataskaita - {{ $meeting->body->title }}</title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            background: #fff;
            max-width: 21cm;
            margin: 0 auto;
            padding: 2cm;
        }
        
        .document-header {
            text-align: center;
            margin-bottom: 2cm;
            border-bottom: 2px solid #000;
            padding-bottom: 1cm;
        }
        
        .document-title {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 0.5cm;
            letter-spacing: 1px;
        }
        
        .document-subtitle {
            font-size: 14pt;
            margin-bottom: 0.3cm;
        }
        
        .document-info {
            margin-bottom: 1.5cm;
            line-height: 1.8;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 0.3cm;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 200px;
        }
        
        .info-value {
            flex: 1;
        }
        
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 1.5cm;
            margin-bottom: 0.8cm;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 0.2cm;
        }
        
        .question-block {
            margin-bottom: 1.5cm;
            page-break-inside: avoid;
        }
        
        .question-title {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 0.5cm;
            padding: 0.3cm;
            background: #f5f5f5;
            border-left: 4px solid #333;
        }
        
        .vote-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.5cm;
        }
        
        .vote-table th {
            background: #e0e0e0;
            font-weight: bold;
            text-align: left;
            padding: 0.4cm;
            border: 1px solid #000;
        }
        
        .vote-table td {
            padding: 0.3cm;
            border: 1px solid #666;
        }
        
        .vote-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .vote-for {
            color: #006400;
            font-weight: bold;
        }
        
        .vote-against {
            color: #8B0000;
            font-weight: bold;
        }
        
        .vote-abstain {
            color: #B8860B;
            font-weight: bold;
        }

        .no-vote {
            color: #000000;
            font-weight: bold;
        }
        
        .timestamp {
            font-size: 10pt;
            color: #555;
            font-style: italic;
        }
        
        .no-votes {
            font-style: italic;
            color: #666;
            padding: 0.5cm;
            text-align: center;
        }
        
        .document-footer {
            margin-top: 2cm;
            padding-top: 1cm;
            border-top: 1px solid #000;
            font-size: 10pt;
            color: #666;
        }
        
        .signature-block {
            margin-top: 2cm;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-line {
            width: 45%;
        }
        
        .signature-line p {
            margin-bottom: 0.3cm;
        }
        
        .signature-line .line {
            border-bottom: 1px solid #000;
            margin-top: 1cm;
            margin-bottom: 0.2cm;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: #4F46E5;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-family: Arial, sans-serif;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #4338CA;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="print-button no-print">🖨️ Spausdinti</button>
    
    <div class="document-header">
        <div class="document-title">Balsavimo ataskaita</div>
        <div class="document-subtitle">{{ $meeting->body->title }}</div>
    </div>
    
    <div class="document-info">
        <div class="info-row">
            <span class="info-label">Posėdžio data:</span>
            <span class="info-value">{{ $meeting->meeting_date->format('Y-m-d') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Balsavimo pradžia:</span>
            <span class="info-value">{{ $meeting->vote_start->format('Y-m-d H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Balsavimo pabaiga:</span>
            <span class="info-value">{{ $meeting->vote_end->format('Y-m-d H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Posėdžio tipas:</span>
            <span class="info-value">{{ $meeting->is_evote ? 'Elektroninis' : 'Fizinis' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Dalyvavo narių:</span>
            <span class="info-value">{{ $presentMembers->count() }} iš {{ $meeting->body->members->count() }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Dokumentas sugeneruotas:</span>
            <span class="info-value">{{ now()->format('Y-m-d H:i:s') }}</span>
        </div>
    </div>
    
    <div class="section-title">Balsavimo rezultatai pagal klausimus</div>
    
    @foreach ($questions as $question)
        <div class="question-block">
            <div class="question-title">
                {{ $loop->iteration }}. {{ $question->title }}
            </div>
            
            @if ($question->type == 'Nebalsuoti')
                <p class="no-votes">Šiam klausimui balsavimas nebuvo reikalingas.</p>
            @else
                <table class="vote-table">
                    <thead>
                        <tr>
                            <th style="width: 5%">Nr.</th>
                            <th style="width: 40%">Narys</th>
                            <th style="width: 20%">Balsas</th>
                            <th style="width: 35%">Balsavimo laikas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $questionVotes = [];
                            foreach ($presentMembers as $member) {
                                $vote = $question->votes()->where('user_id', $member->user_id)->first();
                                $questionVotes[] = [
                                    'member' => $member,
                                    'vote' => $vote
                                ];
                            }
                        @endphp
                        
                        @forelse ($questionVotes as $voteData)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $voteData['member']->pedagogical_name }} {{ $voteData['member']->name }}</td>
                                <td>
                                    @if ($voteData['vote'])
                                        @php
                                            $voteClass = match($voteData['vote']->choice) {
                                                'Už' => 'vote-for',
                                                'Prieš' => 'vote-against',
                                                'Susilaiko' => 'vote-abstain',
                                                default => ''
                                            };
                                        @endphp
                                        <span class="{{ $voteClass }}">{{ $voteData['vote']->choice }}</span>
                                    @else
                                        <span class="text-gray-500 no-vote">Nebalsavo</span>
                                    @endif
                                </td>
                                <td class="timestamp">
                                    @if ($voteData['vote'])
                                        {{ $voteData['vote']->created_at->format('Y-m-d H:i:s') }}
                                    @else
                                        <span class="text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="no-votes">Nė vienas dalyvis nedalyvavo posėdyje.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                
                @php
                    $voteCounts = $meeting->getVoteCounts($question);
                @endphp
                <p style="margin-top: 0.5cm; font-weight: bold;">
                    Balsavimo santrauka: 
                    <span class="vote-for">Už: {{ $voteCounts['Už'] ?? 0 }}</span>, 
                    <span class="vote-against">Prieš: {{ $voteCounts['Prieš'] ?? 0 }}</span>, 
                    <span class="vote-abstain">Susilaikė: {{ $voteCounts['Susilaiko'] ?? 0 }}</span>
                </p>
            @endif
        </div>
    @endforeach

    <div class="signature-block">
        <div class="signature-line">
            <p>Sekretorius:</p>
            <div class="line"></div>
            <p style="text-align: center;">{{ optional($meeting->secretary)->pedagogical_name }} {{ optional($meeting->secretary)->name }}</p>
        </div>
        <div class="signature-line">
            <p>Pirmininkas:</p>
            <div class="line"></div>
            <p style="text-align: center;">{{ optional($meeting->body->chairman)->pedagogical_name }} {{ optional($meeting->body->chairman)->name }}</p>
        </div>
    </div>
</body>
</html>
