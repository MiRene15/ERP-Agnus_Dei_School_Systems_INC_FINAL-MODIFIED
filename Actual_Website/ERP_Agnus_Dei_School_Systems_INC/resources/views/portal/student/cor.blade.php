<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Certificate of Registration — {{ $student->first_name }} {{ $student->last_name }}</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f3f4f6; color: #111; }
        .toolbar { position: fixed; top: 0; left: 0; right: 0; z-index: 50; background: #1a365d; padding: 10px 24px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 8px rgba(0,0,0,.15); }
        .toolbar a { color: #fff; text-decoration: none; font-size: 14px; font-weight: 500; opacity: .85; transition: opacity .2s; }
        .toolbar a:hover { opacity: 1; }
        .toolbar button { background: #fff; color: #1a365d; border: none; padding: 8px 20px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .toolbar button:hover { background: #e5e7eb; }
        .page-wrap { padding: 70px 24px 24px; display: flex; justify-content: center; }
        .cor-page { width: 297mm; background: #fff; padding: 10mm 14mm; box-shadow: 0 4px 20px rgba(0,0,0,.08); border-radius: 2px; position: relative; }
        .cor-header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 4px; margin-bottom: 6px; }
        .cor-header .school-name { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .cor-header .school-addr { font-size: 9px; color: #555; margin-top: 1px; }
        .cor-header .doc-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-top: 6px; border-top: 1.5px solid #111; padding-top: 5px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1px 20px; font-size: 9px; margin-bottom: 6px; border: 1px solid #ccc; padding: 4px 8px; border-radius: 2px; }
        .info-grid p { line-height: 1.5; }
        .info-grid .lbl { font-weight: 700; color: #444; }
        .section-title { font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #333; border-bottom: 1.5px solid #999; padding-bottom: 1px; margin: 4px 0 3px; }
        table.cor-table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 4px; }
        table.cor-table th { background: #e8edf3; text-align: left; padding: 2px 4px; border: 1px solid #999; font-weight: 700; font-size: 8px; text-transform: uppercase; letter-spacing: .5px; color: #333; }
        table.cor-table td { padding: 2px 4px; border: 1px solid #bbb; line-height: 1.3; }
        table.cor-table tbody tr:nth-child(even) { background: #f8f9fb; }
        table.cor-table tfoot td { font-weight: 700; background: #e8edf3; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 8px; text-align: center; font-size: 9px; }
        .sig-box .sig-line { border-top: 1px solid #222; width: 65%; margin: 0 auto 2px; }
        .sig-box .sig-name { font-weight: 700; }
        .sig-box .sig-role { color: #666; font-size: 7.5px; }
        .cor-footer { margin-top: 4px; padding-top: 3px; border-top: 1px solid #ccc; text-align: center; font-size: 7.5px; color: #888; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap { padding: 0; }
            .cor-page { box-shadow: none; border-radius: 0; width: 100%; padding: 8mm 10mm; margin: 0; }
            @page { size: A4 landscape; margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('student.dashboard') }}">&#8592; Back to Portal</a>
        <button onclick="window.print()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print COR
        </button>
    </div>
    <div class="page-wrap">
        <div class="cor-page">
            <div class="cor-header">
                <div style="display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:4px;">
                    <img src="{{ asset('images/agnus_logo.png') }}" alt="Logo" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                    <div style="text-align:left;">
                        <p class="school-name">Agnus Dei School Systems Inc.</p>
                        <p class="school-addr">Brgy. Catmon, Pandan, Antique</p>
                    </div>
                </div>
                <p class="doc-title">Certificate of Registration</p>
            </div>
            <div class="info-grid">
                <p><span class="lbl">Student Name:</span> {{ $student->first_name }} {{ $student->middle_name ? $student->middle_name . ' ' : '' }}{{ $student->last_name }}</p>
                <p><span class="lbl">Grade Level:</span> {{ $enrollment->section->grade_level }}</p>
                <p><span class="lbl">Student No.:</span> {{ $student->student_number }}</p>
                <p><span class="lbl">Section:</span> {{ $enrollment->section->section_name }}</p>
                <p><span class="lbl">LRN:</span> {{ $student->legacy_lrn ?? 'N/A' }}</p>
                <p><span class="lbl">School Year:</span> {{ $enrollment->school_year }}</p>
                @if($enrollment->strand)
                <p><span class="lbl">Strand:</span> {{ $enrollment->strand }}</p>
                @endif
            </div>
            <p class="section-title">Enrolled Subjects</p>
            <table class="cor-table">
                <thead>
                    <tr>
                        <th style="width:3%">#</th>
                        <th style="width:18%">Subject</th>
                        <th style="width:32%">Schedule</th>
                        <th style="width:8%">Room</th>
                        <th style="width:15%">Teacher</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollment->subjects as $idx => $cls)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td style="font-weight:600">{{ $cls->subject->name }}</td>
                        <td>
                            @foreach($cls->schedules as $sched)
                                {{ $sched->day_of_week }} {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}@if(!$loop->last)<br>@endif
                            @endforeach
                        </td>
                        <td>{{ $cls->room ?? ($cls->schedules->first()?->room ?? '—') }}</td>
                        <td>{{ $cls->teacher->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center" style="padding:8px;color:#888">No subjects assigned.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($feeSchedules->isNotEmpty())
            @php
                $isSHS = in_array($enrollment->section->grade_level, ['Grade 11', 'Grade 12']);
            @endphp
            <p class="section-title">Fee Assessment</p>
            <table class="cor-table">
                <thead>
                    <tr>
                        <th style="width:35%">{{ $isSHS ? 'Term' : 'School Year' }}</th>
                        <th class="text-right" style="width:20%">Tuition</th>
                        <th class="text-right" style="width:20%">Miscellaneous</th>
                        <th class="text-right" style="width:25%">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @if($isSHS)
                        @foreach($feeSchedules as $fs)
                        <tr>
                            <td>{{ $fs->term }}</td>
                            <td class="text-right">₱{{ number_format($fs->tuition_fee, 2) }}</td>
                            <td class="text-right">₱{{ number_format($fs->misc_fee, 2) }}</td>
                            <td class="text-right" style="font-weight:600">₱{{ number_format($fs->tuition_fee + $fs->misc_fee, 2) }}</td>
                        </tr>
                        @endforeach
                    @else
                        <tr>
                            <td>{{ $enrollment->school_year }}</td>
                            <td class="text-right">₱{{ number_format($feeSchedules->sum('tuition_fee'), 2) }}</td>
                            <td class="text-right">₱{{ number_format($feeSchedules->sum('misc_fee'), 2) }}</td>
                            <td class="text-right" style="font-weight:600">₱{{ number_format($feeSchedules->sum('tuition_fee') + $feeSchedules->sum('misc_fee'), 2) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total Assessed</td>
                        <td class="text-right">₱{{ number_format($feeSchedules->sum('tuition_fee'), 2) }}</td>
                        <td class="text-right">₱{{ number_format($feeSchedules->sum('misc_fee'), 2) }}</td>
                        <td class="text-right">₱{{ number_format($feeSchedules->sum('tuition_fee') + $feeSchedules->sum('misc_fee'), 2) }}</td>
                    </tr>
                    @if($ledger && $ledger->discount_applied > 0)
                    <tr>
                        <td colspan="3" class="text-right" style="font-weight:400;color:#555">Discount ({{ ucfirst($ledger->discount_type ?? 'N/A') }})</td>
                        <td class="text-right" style="color:#16a34a">-₱{{ number_format($ledger->discount_applied, 2) }}</td>
                    </tr>
                    @endif
                    @if($ledger)
                    <tr>
                        <td colspan="3" class="text-right" style="font-weight:400;color:#555">Total Paid</td>
                        <td class="text-right" style="color:#16a34a">₱{{ number_format($ledger->total_paid, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-right" style="font-weight:400;color:#555">Balance</td>
                        <td class="text-right" style="{{ $ledger->balance > 0 ? 'color:#dc2626' : 'color:#16a34a' }}">₱{{ number_format($ledger->balance, 2) }}</td>
                    </tr>
                    @endif
                </tfoot>
            </table>
            @endif
            <div class="signatures">
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <p class="sig-name">{{ $student->first_name }} {{ $student->last_name }}</p>
                    <p class="sig-role">Student</p>
                </div>
                @if($principalName)
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <p class="sig-name">{{ $principalName }}</p>
                    <p class="sig-role">School Principal</p>
                </div>
                @endif
                @if($directressName)
                <div class="sig-box">
                    <div class="sig-line"></div>
                    <p class="sig-name">{{ $directressName }}</p>
                    <p class="sig-role">School Directress</p>
                </div>
                @endif
            </div>
            <div class="cor-footer">
                <p>This document is system-generated and valid upon presentation. | Issued: {{ now()->format('F d, Y') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
