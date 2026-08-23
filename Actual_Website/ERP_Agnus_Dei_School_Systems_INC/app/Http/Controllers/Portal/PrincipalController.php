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
        $gradeLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $selectedGrade = request('grade_level', $gradeLevels[0]);
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
            'class_id' => 'required|exists:classes,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:50',
        ]);

        $conflict = Schedule::where('class_id', $data['class_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where(function ($q) use ($data) {
                $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                    ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']]);
            })
            ->exists();

        if ($conflict) {
            return back()->with('error', 'This time slot conflicts with an existing schedule for this class.');
        }

        $class = Classes::find($data['class_id']);
        if ($class && $class->teacher_id) {
            $teacherConflict = Schedule::where('day_of_week', $data['day_of_week'])
                ->whereHas('schoolClass', fn($q) => $q->where('teacher_id', $class->teacher_id))
                ->where(function ($q) use ($data) {
                    $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                      ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']]);
                })->exists();
            if ($teacherConflict) {
                return back()->with('error', 'Teacher is already booked at this time on ' . $data['day_of_week'] . '.');
            }
        }

        if (!empty($data['room'])) {
            $roomConflict = Schedule::where('day_of_week', $data['day_of_week'])
                ->where('room', $data['room'])
                ->where(function ($q) use ($data) {
                    $q->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                      ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']]);
                })->exists();
            if ($roomConflict) {
                return back()->with('error', 'Room ' . $data['room'] . ' is already booked at this time on ' . $data['day_of_week'] . '.');
            }
        }

        Schedule::create($data);

        return back()->with('success', 'Schedule added.');
    }

    public function schedulesDestroy(Schedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Schedule removed.');
    }

    // ─── Schedules CSV — hybrid import (manual stays, CSV optional) ─
    public function schedulesTemplate()
    {
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="schedules_template.csv"'];
        $columns = ['class_id', 'day_of_week', 'start_time', 'end_time', 'room'];
        $example = ['1', 'Monday', '08:00', '09:00', 'Room 101'];
        return response()->stream(function () use ($columns, $example) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns);
            fputcsv($out, $example);
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
        $expected = ['class_id', 'day_of_week', 'start_time', 'end_time', 'room'];
        $headerNorm = array_map(fn($h) => strtolower(trim($h)), $header ?? []);
        if ($headerNorm !== $expected) {
            fclose($handle);
            return back()->with('error', 'Invalid CSV header. Expected: ' . implode(',', $expected) . '. Download the template.');
        }

        $rows = [];
        $line = 1;
        while (($data = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($data, fn($v) => trim($v) !== '')) === 0) continue;
            if (count($data) < 5) {
                $rows[] = ['line' => $line, 'error' => 'Missing columns', 'data' => $data];
                continue;
            }
            $rows[] = [
                'line' => $line,
                'class_id' => trim($data[0]),
                'day_of_week' => trim($data[1]),
                'start_time' => trim($data[2]),
                'end_time' => trim($data[3]),
                'room' => trim($data[4]),
            ];
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

            $conflict = Schedule::where('class_id', $r['class_id'])
                ->where('day_of_week', $r['day_of_week'])
                ->where(function ($q) use ($r) {
                    $q->whereBetween('start_time', [$r['start_time'], $r['end_time']])
                      ->orWhereBetween('end_time', [$r['start_time'], $r['end_time']]);
                })->exists();

            if ($conflict) {
                $skipped[] = "Line {$r['line']}: time conflict for class {$r['class_id']} on {$r['day_of_week']} {$r['start_time']}-{$r['end_time']} — skipped.";
                continue;
            }

            $class = Classes::find($r['class_id']);
            if ($class && $class->teacher_id) {
                $teacherConflict = Schedule::where('day_of_week', $r['day_of_week'])
                    ->whereHas('schoolClass', fn($q) => $q->where('teacher_id', $class->teacher_id))
                    ->where(function ($q) use ($r) {
                        $q->whereBetween('start_time', [$r['start_time'], $r['end_time']])
                          ->orWhereBetween('end_time', [$r['start_time'], $r['end_time']]);
                    })->exists();
                if ($teacherConflict) {
                    $skipped[] = "Line {$r['line']}: teacher already booked on {$r['day_of_week']} {$r['start_time']}-{$r['end_time']} — skipped.";
                    continue;
                }
            }
            if (!empty($r['room'])) {
                $roomConflict = Schedule::where('day_of_week', $r['day_of_week'])
                    ->where('room', $r['room'])
                    ->where(function ($q) use ($r) {
                        $q->whereBetween('start_time', [$r['start_time'], $r['end_time']])
                          ->orWhereBetween('end_time', [$r['start_time'], $r['end_time']]);
                    })->exists();
                if ($roomConflict) {
                    $skipped[] = "Line {$r['line']}: room {$r['room']} already booked on {$r['day_of_week']} — skipped.";
                    continue;
                }
            }

            try {
                Schedule::create([
                    'class_id' => $r['class_id'],
                    'day_of_week' => $r['day_of_week'],
                    'start_time' => $r['start_time'],
                    'end_time' => $r['end_time'],
                    'room' => $r['room'] ?: null,
                ]);
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
        $gradeLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
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

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.principal.partials.announcements-results', compact('announcements'))->render(),
            ]);
        }

        return view('portal.principal.announcements.index', compact('announcements'));
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

        Announcement::create($data);

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

        return redirect()->route('principal.announcements')
            ->with('success', 'Announcement updated.');
    }

    public function announcementsDestroy(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }
}
