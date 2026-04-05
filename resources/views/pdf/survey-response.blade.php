<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; line-height: 1.5; padding: 30px; }
        .header { border-bottom: 2px solid #10b981; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { font-size: 18px; color: #111827; margin-bottom: 4px; }
        .header p { font-size: 11px; color: #6b7280; }
        .meta { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 16px; margin-bottom: 20px; }
        .meta-row { display: flex; margin-bottom: 4px; }
        .meta-label { font-weight: bold; color: #374151; width: 140px; }
        .meta-value { color: #6b7280; }
        .question { margin-bottom: 16px; page-break-inside: avoid; }
        .question-header { font-weight: bold; color: #111827; font-size: 12px; margin-bottom: 4px; }
        .question-num { display: inline-block; background: #10b981; color: white; width: 20px; height: 20px; text-align: center; border-radius: 50%; font-size: 10px; line-height: 20px; margin-right: 6px; }
        .category { display: inline-block; font-size: 9px; background: #e5e7eb; color: #6b7280; padding: 1px 6px; border-radius: 8px; margin-left: 4px; }
        .answer { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 8px 12px; color: #166534; font-size: 11px; }
        .answer-rating { display: inline-block; background: #fbbf24; color: white; padding: 2px 8px; border-radius: 4px; font-weight: bold; }
        .footer { margin-top: 30px; padding-top: 10px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 9px; color: #9ca3af; }
        table.meta-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.meta-table td { padding: 4px 8px; font-size: 11px; }
        table.meta-table td:first-child { font-weight: bold; color: #374151; width: 150px; }
        table.meta-table td:last-child { color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $survey->title }}</h1>
        <p>{{ $survey->description ?? "So'rovnoma natijalari" }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <td>Respondent:</td>
            <td>{{ $response->respondent_name ?? 'Anonim' }}</td>
        </tr>
        @if($response->respondent_phone)
        <tr>
            <td>Telefon:</td>
            <td>{{ $response->respondent_phone }}</td>
        </tr>
        @endif
        @if($response->respondent_email)
        <tr>
            <td>Email:</td>
            <td>{{ $response->respondent_email }}</td>
        </tr>
        @endif
        @if($response->respondent_region)
        <tr>
            <td>Hudud:</td>
            <td>{{ $response->respondent_region }}</td>
        </tr>
        @endif
        <tr>
            <td>Sana:</td>
            <td>{{ $response->completed_at ? $response->completed_at->format('d.m.Y H:i') : $response->created_at->format('d.m.Y H:i') }}</td>
        </tr>
        <tr>
            <td>Vaqt:</td>
            <td>{{ $response->time_spent ? floor($response->time_spent / 60) . ' daq ' . ($response->time_spent % 60) . ' son' : '-' }}</td>
        </tr>
    </table>

    @php $num = 0; @endphp
    @foreach($response->answers as $answer)
        @php $num++; @endphp
        <div class="question">
            <div class="question-header">
                <span class="question-num">{{ $num }}</span>
                {{ $answer->question->question ?? 'Savol' }}
                @if($answer->question && $answer->question->category)
                    <span class="category">{{ $answer->question->category }}</span>
                @endif
            </div>
            <div class="answer">
                @if($answer->rating_value !== null)
                    <span class="answer-rating">{{ $answer->rating_value }} / {{ $answer->question && $answer->question->type === 'scale' ? '10' : '5' }}</span>
                @elseif($answer->selected_options && count($answer->selected_options) > 0)
                    {{ implode(', ', $answer->selected_options) }}
                @else
                    {{ $answer->answer ?? '-' }}
                @endif
            </div>
        </div>
    @endforeach

    <div class="footer">
        BiznesPilot AI &middot; {{ now()->format('d.m.Y') }}
    </div>
</body>
</html>
