<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report Card - {{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333; margin: 30px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { font-size: 16px; margin: 0 0 4px; text-transform: uppercase; }
        .header p { margin: 2px 0; font-size: 11px; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 2px 5px; font-size: 11px; }
        .info-table td:first-child { font-weight: bold; width: 120px; }
        .grades-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .grades-table th, .grades-table td { border: 1px solid #999; padding: 5px 6px; text-align: center; font-size: 10px; }
        .grades-table th { background-color: #f0f0f0; font-weight: bold; }
        .grades-table td.subject { text-align: left; font-weight: bold; }
        .passed { color: #155724; background-color: #d4edda; padding: 1px 5px; border-radius: 3px; font-size: 9px; }
        .failed { color: #721c24; background-color: #f8d7da; padding: 1px 5px; border-radius: 3px; font-size: 9px; }
        .footer { margin-top: 25px; }
        .footer-table { width: 100%; }
        .footer-table td { padding: 5px; font-size: 10px; vertical-align: top; }
        .signature { margin-top: 30px; }
        .signature td { padding: 15px 10px 0; font-size: 10px; }
        .average-row td { background-color: #f9f9f9; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AGNUS DEI SCHOOL SYSTEMS INC.</h1>
        <p>Student Progress Report Card (SF9)</p>
        <p>School Year: {{ $enrollment->school_year }}</p>
    </div>

    <table class="info-table">
        <tr><td>Student Name:</td><td>{{ strtoupper($enrollment->student->last_name) }}, {{ strtoupper($enrollment->student->first_name) }} {{ strtoupper($enrollment->student->middle_name ?? '') }}</td></tr>
        <tr><td>Grade Level:</td><td>{{ $enrollment->section->grade_level ?? 'N/A' }}</td></tr>
        <tr><td>Section:</td><td>{{ $enrollment->section->section_name ?? 'N/A' }}</td></tr>
        <tr><td>LRN:</td><td>{{ $enrollment->student->student_number ?? 'N/A' }}</td></tr>
        @if($enrollment->strand)
        <tr><td>Strand:</td><td>{{ $enrollment->strand }}</td></tr>
        @endif
        <tr><td>Adviser:</td><td>{{ $enrollment->section?->adviser?->name ?? 'N/A' }}</td></tr>
    </table>

    <table class="grades-table">
        <thead>
            <tr>
                <th style="width:30%; text-align:left;">Learning Areas</th>
                @foreach($gradingPeriods as $period)
                <th>{{ $period }}</th>
                @endforeach
                <th>Final</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subjects as $subject)
            <tr>
                <td class="subject">{{ $subject->subject }}</td>
                @foreach($gradingPeriods as $period)
                <td>{{ $subject->{$period} }}</td>
                @endforeach
                <td><strong>{{ $subject->final }}</strong></td>
                <td>
                    @if($subject->remarks === 'Passed')
                        <span class="passed">PASSED</span>
                    @elseif($subject->remarks === 'Failed')
                        <span class="failed">FAILED</span>
                    @else
                        —
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; padding:15px;">No grades available.</td></tr>
            @endforelse
        </tbody>
        @if($subjects->isNotEmpty())
        <tfoot>
            <tr class="average-row">
                <td style="text-align:left;">General Average</td>
                <td></td><td></td><td></td>
                <td>{{ $overallAverage ? number_format($overallAverage, 2) : '—' }}</td>
                <td>
                    @if(($overallAverage ?? 0) >= 75)
                        <span class="passed">PASSED</span>
                    @elseif($overallAverage > 0)
                        <span class="failed">FAILED</span>
                    @else
                        —
                    @endif
                </td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td><strong>Remarks:</strong></td>
                <td></td>
                <td style="text-align:right;"><strong>Date:</strong> {{ now()->format('F d, Y') }}</td>
            </tr>
        </table>
        <table class="signature" width="100%">
            <tr>
                <td style="text-align:center;">
                    <hr style="width:70%;">
                    <strong>{{ $enrollment->student->first_name }} {{ $enrollment->student->last_name }}</strong><br>
                    Student
                </td>
                <td style="text-align:center;">
                    <hr style="width:70%;">
                    <strong>Adviser</strong><br>
                    Class Adviser
                </td>
                <td style="text-align:center;">
                    <hr style="width:70%;">
                    <strong>Registrar</strong><br>
                    Registrar's Office
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
