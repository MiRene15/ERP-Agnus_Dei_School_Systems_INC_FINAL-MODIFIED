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
    public function index()
    {
        $totalAnnouncements = Announcement::count();
        $totalSections = Section::count();
        $totalStudents = Enrollment::where('status', 'Active')->count();
        $recentAnnouncements = Announcement::latest()->take(5)->get();

        return view('portal.principal.dashboard', compact(
            'totalAnnouncements', 'totalSections', 'totalStudents', 'recentAnnouncements'
        ));
    }

    // ─── Schedules (per grade & per teacher) ────────────────────
    public function schedules()
    {
        $gradeLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $selectedGrade = request('grade_level', $gradeLevels[0]);

        $query = Classes::with('subject', 'teacher', 'schedules')
            ->where('grade_level', $selectedGrade)
            ->where('school_year', active_school_year());

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

        return view('portal.principal.schedules', compact('gradeLevels', 'selectedGrade', 'classes', 'days'));
    }

    public function schedulesStore(Request $request)
    {
        $data = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
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
            return back()->with('error', 'This time slot conflicts with an existing schedule.');
        }

        Schedule::create($data);

        return back()->with('success', 'Schedule added.');
    }

    public function schedulesDestroy(Schedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Schedule removed.');
    }

    // ─── Student Grades (read-only view) ────────────────────────
    public function grades()
    {
        $gradeLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $selectedGrade = request('grade_level', $gradeLevels[0]);

        $query = Enrollment::with('student', 'section', 'grades', 'subjects')
            ->whereHas('section', function ($q) use ($selectedGrade) {
                $q->where('grade_level', $selectedGrade);
            })
            ->where('school_year', active_school_year())
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

        return view('portal.principal.grades', compact(
            'gradeLevels', 'selectedGrade', 'enrollments', 'sections', 'subjects'
        ));
    }

    // ─── Announcements (CRUD) ──────────────────────────────────
    public function announcements()
    {
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
