<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Classes;
use App\Models\Grade;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;

class PrincipalController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $totalAnnouncements = Announcement::count();
        $totalSections = Section::count();
        $totalStudents = Enrollment::where('status', 'Active')->count();
        $recentAnnouncements = Announcement::latest()->take(5)->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.principal.partials.dashboard-results', compact(
                    'totalAnnouncements', 'totalSections', 'totalStudents', 'recentAnnouncements'
                ))->render(),
            ]);
        }

        return view('portal.principal.dashboard', compact(
            'totalAnnouncements', 'totalSections', 'totalStudents', 'recentAnnouncements'
        ));
    }

    // ─── Schedules (per grade & per teacher) ────────────────────
    public function schedules(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12', 'SHS'];
        $selectedGrade = request('grade_level', 'Grade 7');
        $selectedYear = request('school_year', active_school_year());
        $schoolYears = all_school_years();

        $query = Classes::with('subject', 'teacher', 'schedules')
            ->where('grade_level', $selectedGrade)
            ->where('school_year', $selectedYear);

        if (request('day')) {
            $query->whereHas('schedules', fn($q) => $q->where('day_of_week', request('day')));
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('subject', fn($sq) => $sq->where('name', 'like', "%{$search}%")
                    ->orWhere('subject_code', 'like', "%{$search}%"))
                    ->orWhereHas('teacher', fn($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $classes = $query->orderBy('section')->get();

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.principal.partials.schedules-results', compact('classes', 'days', 'selectedGrade', 'selectedYear'))->render(),
            ]);
        }

        return view('portal.principal.schedules', compact('gradeLevels', 'selectedGrade', 'classes', 'days', 'schoolYears', 'selectedYear'));
    }

    public function schedulesStore(Request $request)
    {
        $data = $request->validate([
            'grade_level' => 'nullable|string|max:50',
            'section' => 'nullable|string|max:100',
            'teacher_id' => 'nullable|exists:users,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:50',
        ]);

        // Normalize times to H:i:s for overlap comparison
        $start = $data['start_time'] . ':00';
        $end = $data['end_time'] . ':00';
        if (strlen($data['start_time']) === 8) $start = $data['start_time'];
        if (strlen($data['end_time']) === 8) $end = $data['end_time'];

        $conflict = Schedule::where('class_id', $data['class_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)->where('end_time', '>', $start);
            })
            ->exists();

        if ($conflict) {
            return back()->with('error', 'This time slot conflicts with an existing schedule for this class.');
        }

        $class = Classes::find($data['class_id']);
        if ($class && $class->teacher_id) {
            $teacherConflict = Schedule::where('day_of_week', $data['day_of_week'])
                ->whereHas('schoolClass', fn($q) => $q->where('teacher_id', $class->teacher_id))
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_time', '<', $end)->where('end_time', '>', $start);
                })->exists();
            if ($teacherConflict) {
                return back()->with('error', 'Teacher is already booked at this time on ' . $data['day_of_week'] . '.');
            }
        }

        if (!empty($data['room'])) {
            $roomConflict = Schedule::where('day_of_week', $data['day_of_week'])
                ->where('room', $data['room'])
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_time', '<', $end)->where('end_time', '>', $start);
                })->exists();
            if ($roomConflict) {
                return back()->with('error', 'Room ' . $data['room'] . ' is already booked at this time on ' . $data['day_of_week'] . '.');
            }
        }

        $schedule = Schedule::create([
            'class_id' => $data['class_id'],
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'room' => $data['room'] ?? null,
        ]);
        log_activity($schedule, 'Schedule Created', auth()->user()->name . ' created schedule for class #' . $data['class_id'] . ' on ' . $data['day_of_week'] . ' ' . $data['start_time'] . '-' . $data['end_time']);

        return back()->with('success', 'Schedule added.');
    }

    public function schedulesEdit(Schedule $schedule)
    {
        $schedule->load('schoolClass.subject', 'schoolClass.teacher');
        return view('portal.principal.schedules-edit', compact('schedule'));
    }

    public function schedulesUpdate(Request $request, Schedule $schedule)
    {
        $data = $request->validate([
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:50',
        ]);

        $start = $data['start_time'] . ':00';
        $end = $data['end_time'] . ':00';
        if (strlen($data['start_time']) === 8) $start = $data['start_time'];
        if (strlen($data['end_time']) === 8) $end = $data['end_time'];

        $conflict = Schedule::where('id', '!=', $schedule->id)
            ->where('class_id', $schedule->class_id)
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)->where('end_time', '>', $start);
            })->exists();
        if ($conflict) return back()->with('error', 'Time conflict for this class.');

        $class = $schedule->schoolClass;
        if ($class && $class->teacher_id) {
            $teacherConflict = Schedule::where('id', '!=', $schedule->id)
                ->where('day_of_week', $data['day_of_week'])
                ->whereHas('schoolClass', fn($q) => $q->where('teacher_id', $class->teacher_id))
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_time', '<', $end)->where('end_time', '>', $start);
                })->exists();
            if ($teacherConflict) return back()->with('error', 'Teacher already booked at this time.');
        }

        if (!empty($data['room'])) {
            $roomConflict = Schedule::where('id', '!=', $schedule->id)
                ->where('day_of_week', $data['day_of_week'])
                ->where('room', $data['room'])
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_time', '<', $end)->where('end_time', '>', $start);
                })->exists();
            if ($roomConflict) return back()->with('error', 'Room already booked at this time.');
        }

        $schedule->update($data);
        log_activity($schedule, 'Schedule Updated', auth()->user()->name . ' updated schedule #' . $schedule->id . ' to ' . $data['day_of_week'] . ' ' . $data['start_time'] . '-' . $data['end_time']);
        return redirect()->route('principal.schedules')->with('success', 'Schedule updated.');
    }

    public function schedulesManage(Request $request)
    {
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12', 'SHS'];
        $classes = Classes::with('subject', 'teacher')->where('school_year', active_school_year())->where('status','active')->orderBy('grade_level')->get();
        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        return view('portal.principal.schedules-manage', compact('gradeLevels','classes','days'));
    }

    public function schedulesDestroy(Schedule $schedule)
    {
        $info = 'Schedule #' . $schedule->id . ' (' . $schedule->day_of_week . ' ' . substr($schedule->start_time,0,5) . '-' . substr($schedule->end_time,0,5) . ') deleted';
        log_activity($schedule, 'Schedule Deleted', auth()->user()->name . ' deleted ' . $info);
        $schedule->delete();
        return back()->with('success', 'Schedule removed.');
    }

    // ─── Schedules CSV — hybrid import (friendly template: no class_id needed) ─
    public function schedulesTemplate()
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="schedules_template.csv"'];
        $columns = ['grade_level', 'section', 'subject_code', 'day_of_week', 'start_time', 'end_time', 'room'];
        $examples = [
            ['Grade 7', 'Charity', 'G7-ENG', 'Monday', '08:00', '09:00', 'J-101'],
            ['Grade 7', 'Hope', 'G7-MAT', 'Tuesday', '09:00', '10:00', 'J-102'],
        ];
        return response()->stream(function () use ($columns, $examples) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            foreach ($examples as $ex) fputcsv($out, $ex);
            fclose($out);
        }, 200, $headers);
    }

    public function schedulesImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Cannot read uploaded file.');
        }

        $header = fgetcsv($handle);
        $headerNorm = array_map(fn($h) => strtolower(trim($h)), $header ?? []);
        $legacy = ['class_id', 'day_of_week', 'start_time', 'end_time', 'room'];
        $friendly = ['grade_level', 'section', 'subject_code', 'day_of_week', 'start_time', 'end_time', 'room'];
        $isLegacy = $headerNorm === $legacy;
        $isFriendly = $headerNorm === $friendly;
        if (!$isLegacy && !$isFriendly) {
            fclose($handle);
            return back()->with('error', 'Invalid CSV header. Expected: ' . implode(',', $friendly) . ' (or legacy ' . implode(',', $legacy) . '). Download the template.');
        }

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($data, fn($v) => trim($v) !== '')) === 0) continue;
            if ($isLegacy) {
                if (count($data) < 5) { $rows[] = ['line' => $line, 'error' => 'Missing columns', 'data' => $data]; continue; }
                $rows[] = ['line' => $line, 'class_id' => trim($data[0]), 'day_of_week' => trim($data[1]), 'start_time' => trim($data[2]), 'end_time' => trim($data[3]), 'room' => trim($data[4])];
            } else {
                if (count($data) < 7) { $rows[] = ['line' => $line, 'error' => 'Missing columns (need 7)', 'data' => $data]; continue; }
                $grade = trim($data[0]); $section = trim($data[1]); $subjCode = trim($data[2]);
                $day = trim($data[3]); $start = trim($data[4]); $end = trim($data[5]); $room = trim($data[6]);
                $schoolYear = active_school_year();
                $class = Classes::where('grade_level', $grade)->where('section', $section)->where('school_year', $schoolYear)
                    ->whereHas('subject', fn($q) => $q->where('subject_code', $subjCode)->orWhere('name', $subjCode))
                    ->first();
                if (!$class) {
                    $rows[] = ['line' => $line, 'error' => "Class not found for $grade / $section / $subjCode ($schoolYear)", 'data' => $data];
                    continue;
                }
                $rows[] = ['line' => $line, 'class_id' => $class->id, 'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end, 'room' => $room, 'resolved' => "$grade $section $subjCode → Class #{$class->id} ({$class->subject->name} / {$class->teacher?->name})"];
            }
        }
        fclose($handle);

        $allowedDays = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
        $imported = 0;
        $errors = [];
        $skipped = [];

        foreach ($rows as $r) {
            if (isset($r['error'])) { $errors[] = "Line {$r['line']}: {$r['error']}"; continue; }
            $validator = \Illuminate\Support\Facades\Validator::make($r, [
                'class_id' => 'required|exists:classes,id',
                'day_of_week' => 'required|in:' . implode(',', $allowedDays),
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'room' => 'nullable|string|max:50',
            ]);
            if ($validator->fails()) {
                $errors[] = "Line {$r['line']}: " . implode(', ', $validator->errors()->all());
                continue;
            }

            $rStart = $r['start_time'] . ':00';
            $rEnd = $r['end_time'] . ':00';
            if (strlen($r['start_time']) === 8) $rStart = $r['start_time'];
            if (strlen($r['end_time']) === 8) $rEnd = $r['end_time'];
            $conflict = Schedule::where('class_id', $r['class_id'])
                ->where('day_of_week', $r['day_of_week'])
                ->where(function ($q) use ($rStart, $rEnd) {
                    $q->where('start_time', '<', $rEnd)->where('end_time', '>', $rStart);
                })->exists();

            if ($conflict) {
                $skipped[] = "Line {$r['line']}: time conflict for class {$r['class_id']} on {$r['day_of_week']} {$r['start_time']}-{$r['end_time']} — skipped.";
                continue;
            }

            $class = Classes::find($r['class_id']);
            if ($class && $class->teacher_id) {
                $teacherConflict = Schedule::where('day_of_week', $r['day_of_week'])
                    ->whereHas('schoolClass', fn($q) => $q->where('teacher_id', $class->teacher_id))
                    ->where(function ($q) use ($rStart, $rEnd) {
                        $q->where('start_time', '<', $rEnd)->where('end_time', '>', $rStart);
                    })->exists();
                if ($teacherConflict) {
                    $skipped[] = "Line {$r['line']}: teacher already booked on {$r['day_of_week']} {$r['start_time']}-{$r['end_time']} — skipped.";
                    continue;
                }
            }
            if (!empty($r['room'])) {
                $roomConflict = Schedule::where('day_of_week', $r['day_of_week'])
                    ->where('room', $r['room'])
                    ->where(function ($q) use ($rStart, $rEnd) {
                        $q->where('start_time', '<', $rEnd)->where('end_time', '>', $rStart);
                    })->exists();
                if ($roomConflict) {
                    $skipped[] = "Line {$r['line']}: room {$r['room']} already booked on {$r['day_of_week']} — skipped.";
                    continue;
                }
            }

            try {
                $created = Schedule::create([
                    'class_id' => $r['class_id'],
                    'day_of_week' => $r['day_of_week'],
                    'start_time' => $r['start_time'],
                    'end_time' => $r['end_time'],
                    'room' => $r['room'] ?: null,
                ]);
                log_activity($created, 'Schedule Imported', 'Imported schedule for class #' . $r['class_id'] . ' on ' . $r['day_of_week'] . ' ' . $r['start_time'] . '-' . $r['end_time']);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Line {$r['line']}: " . $e->getMessage();
            }
        }

        $msg = "{$imported} schedule(s) imported.";
        if ($skipped) $msg .= ' ' . count($skipped) . ' skipped (conflict).';
        if ($errors) $msg .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' (+' . (count($errors)-5) . ' more)' : '');

        $type = $imported > 0 ? 'success' : 'error';
        if ($skipped) $type = $imported > 0 ? 'success' : 'error';

        return back()->with($type, $msg)->with('import_errors', $errors)->with('import_skipped', $skipped);
    }

    // ─── Student Grades (read-only view) ────────────────────────
    public function grades(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $gradeLevels = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12', 'SHS'];
        $selectedGrade = request('grade_level', $gradeLevels[0]);
        $selectedYear = request('school_year', active_school_year());
        $schoolYears = all_school_years();

        $query = Enrollment::with('student', 'section', 'grades', 'subjects')
            ->whereHas('section', function ($q) use ($selectedGrade) {
                $q->where('grade_level', $selectedGrade);
            })
            ->where('school_year', $selectedYear)
            ->where('status', 'Active');

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($sq) use ($search) {
                    $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('section', function ($sq) use ($search) {
                    $sq->where('section_name', 'like', "%{$search}%");
                });
            });
        }

        $enrollments = $query->orderBy('id')->paginate(50)->withQueryString();

        $sections = Section::where('grade_level', $selectedGrade)->get();
        $subjects = Subject::where('grade_level', $selectedGrade)->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.principal.partials.grades-results', compact('enrollments', 'subjects', 'selectedGrade', 'selectedYear'))->render(),
            ]);
        }

        return view('portal.principal.grades', compact(
            'gradeLevels', 'selectedGrade', 'enrollments', 'sections', 'subjects', 'schoolYears', 'selectedYear'
        ));
    }

    // ─── Announcements (CRUD) ──────────────────────────────────
    public function announcements(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = Announcement::query();

        if (request('search')) {
            $query->where('title', 'like', '%' . request('search') . '%');
        }

        if (request('type') && request('type') !== 'All') {
            $query->where('type', request('type'));
        }

        if (request('status') && request('status') !== 'All') {
            $query->where('is_published', request('status') === 'Published');
        }

        $announcements = $query->latest()->paginate(15)->withQueryString();
        $counts = [
            'total' => Announcement::count(),
            'published' => Announcement::where('is_published', true)->count(),
            'draft' => Announcement::where('is_published', false)->count(),
            'events' => Announcement::where('type', 'event')->count(),
        ];

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.principal.partials.announcements-results', compact('announcements'))->render(),
            ]);
        }

        return view('portal.principal.announcements.index', compact('announcements', 'counts'));
    }

    public function announcementsCreate()
    {
        return view('portal.principal.announcements.create');
    }

    public function announcementsStore(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:announcement,event',
            'date' => 'required|date',
            'is_published' => 'nullable|boolean',
        ]);

        $data['admin_id'] = auth()->id();
        $data['is_published'] = $request->boolean('is_published');

        $announcement = Announcement::create($data);

        log_activity($announcement, 'Announcement Created', auth()->user()->name . ' created ' . $announcement->type . ': "' . $announcement->title . '" (' . ($announcement->is_published ? 'published' : 'draft') . ').');

        return redirect()->route('principal.announcements')
            ->with('success', 'Announcement/event created successfully.');
    }

    public function announcementsEdit(Announcement $announcement)
    {
        return view('portal.principal.announcements.edit', compact('announcement'));
    }

    public function announcementsUpdate(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:announcement,event',
            'date' => 'required|date',
            'is_published' => 'nullable|boolean',
        ]);

        $data['is_published'] = $request->boolean('is_published');

        $announcement->update($data);

        log_activity($announcement, 'Announcement Updated', auth()->user()->name . ' updated ' . $announcement->type . ': "' . $announcement->title . '".');

        return redirect()->route('principal.announcements')
            ->with('success', 'Announcement updated.');
    }

    public function announcementsDestroy(Announcement $announcement)
    {
        log_activity($announcement, 'Announcement Deleted', auth()->user()->name . ' deleted ' . $announcement->type . ': "' . $announcement->title . '".');
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }
}
