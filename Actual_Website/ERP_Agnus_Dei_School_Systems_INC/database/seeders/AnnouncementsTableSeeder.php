<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Announcement;
use App\Models\User;
use Carbon\Carbon;

class AnnouncementsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Find an admin user to author the announcements
        $admin = User::whereHas('role', function ($query) {
            $query->where('name', 'Admin');
        })->first();

        $adminId = $admin ? $admin->id : 1;

        // Latest Announcements (idempotent — key on title)
        Announcement::updateOrCreate(
            ['title' => 'Enrollment for SY 2026-2027 Opens'],
            [
                'admin_id' => $adminId,
                'content' => 'We are officially opening our doors for early registration. Secure your slot for the upcoming school year today.',
                'type' => 'announcement',
                'date' => Carbon::now()->subDays(2),
                'is_published' => true,
            ]
        );

        Announcement::updateOrCreate(
            ['title' => 'New Robotics Lab Facility'],
            [
                'admin_id' => $adminId,
                'content' => 'We are excited to announce the completion of our new state-of-the-art Robotics Lab for Senior High School STEM students.',
                'type' => 'announcement',
                'date' => Carbon::now()->subDays(5),
                'is_published' => true,
            ]
        );

        // Upcoming Events
        Announcement::updateOrCreate(
            ['title' => 'Parent-Teacher Orientation'],
            [
                'admin_id' => $adminId,
                'content' => 'Join us for the annual orientation to discuss the new curriculum, school policies, and campus guidelines.',
                'type' => 'event',
                'date' => Carbon::now()->addDays(10),
                'is_published' => true,
            ]
        );

        Announcement::updateOrCreate(
            ['title' => 'Intramurals Opening Ceremony'],
            [
                'admin_id' => $adminId,
                'content' => 'The highly anticipated annual sports fest begins! Wear your team colors and show your school spirit.',
                'type' => 'event',
                'date' => Carbon::now()->addDays(20),
                'is_published' => true,
            ]
        );
    }
}
