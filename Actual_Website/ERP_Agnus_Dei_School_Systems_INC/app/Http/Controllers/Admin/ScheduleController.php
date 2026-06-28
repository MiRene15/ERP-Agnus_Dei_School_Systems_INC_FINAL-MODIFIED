<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Models\Section;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $classes = Classes::with('subject', 'teacher', 'schedules')
            ->where('school_year', active_school_year())
            ->orderBy('grade_level')
            ->orderBy('section')
            ->get()
            ->groupBy('grade_level');

        return view('portal.admin.schedules.index', compact('classes'));
    }

    public function create()
    {
        $subjects = Subject::all();
        $teachers = User::where('role_id', 4)->where('status', 'active')->get();
        $sections = Section::where('is_active', true)->get();

        return view('portal.admin.schedules.create', compact('subjects', 'teachers', 'sections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'grade_level' => 'required|string|max:20',
            'school_year' => 'required|string|max:20',
            'semester' => 'required|string|max:20',
            'room' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $section = Section::findOrFail($data['section_id']);

        $class = Classes::create([
            'subject_id' => $data['subject_id'],
            'teacher_id' => $data['teacher_id'],
            'section' => $section->section_name,
            'grade_level' => $data['grade_level'],
            'school_year' => $data['school_year'],
            'semester' => $data['semester'],
            'room' => $data['room'] ?? null,
            'capacity' => $data['capacity'] ?? null,
            'status' => 'active',
        ]);

        return redirect()->route('admin.schedules.slots', $class)
            ->with('success', 'Class created. Now add schedule slots.');
    }

    public function manageSlots(Classes $class)
    {
        $class->load('subject', 'teacher', 'schedules');
        return view('portal.admin.schedules.slots', compact('class'));
    }

    public function storeSlot(Request $request, Classes $class)
    {
        $data = $request->validate([
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
        ]);

        Schedule::create([
            'class_id' => $class->id,
            'day_of_week' => $data['day_of_week'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'room' => $data['room'] ?? $class->room,
        ]);

        return back()->with('success', 'Schedule slot added.');
    }

    public function destroySlot(Schedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'Schedule slot removed.');
    }

    public function edit(Classes $class)
    {
        $class->load('subject', 'teacher', 'schedules');
        $subjects = Subject::all();
        $teachers = User::where('role_id', 4)->where('status', 'active')->get();
        $sections = Section::where('is_active', true)->get();

        return view('portal.admin.schedules.edit', compact('class', 'subjects', 'teachers', 'sections'));
    }

    public function update(Request $request, Classes $class)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'grade_level' => 'required|string|max:20',
            'school_year' => 'required|string|max:20',
            'semester' => 'required|string|max:20',
            'room' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $section = Section::findOrFail($data['section_id']);

        $class->update([
            'subject_id' => $data['subject_id'],
            'teacher_id' => $data['teacher_id'],
            'section' => $section->section_name,
            'grade_level' => $data['grade_level'],
            'school_year' => $data['school_year'],
            'semester' => $data['semester'],
            'room' => $data['room'] ?? null,
            'capacity' => $data['capacity'] ?? null,
        ]);

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Class updated.');
    }

    public function destroy(Classes $class)
    {
        $class->delete();
        return back()->with('success', 'Class deleted.');
    }
}
