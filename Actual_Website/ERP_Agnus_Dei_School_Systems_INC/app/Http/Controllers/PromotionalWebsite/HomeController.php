<?php

namespace App\Http\Controllers\PromotionalWebsite;

use App\Http\Controllers\Controller;
use App\Models\Announcement;

class HomeController extends Controller
{
    public function index()
    {
        $announcements = Announcement::where('type', 'announcement')
            ->where('is_published', true)
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        $events = Announcement::where('type', 'event')
            ->where('is_published', true)
            ->orderBy('date', 'asc')
            ->take(5)
            ->get();

        return view('PromotionalWebsite.welcome', compact('announcements', 'events'));
    }
}
