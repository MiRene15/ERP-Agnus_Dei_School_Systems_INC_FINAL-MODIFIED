<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SystemRolesAndStaffSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed Roles
        $roles = [
            1 => 'Admin', 2 => 'Registrar', 3 => 'Cashier', 
            4 => 'Teacher', 5 => 'Librarian', 6 => 'Nurse', 7 => 'Student'
        ];
        
        foreach ($roles as $id => $name) {
            DB::table('roles')->updateOrInsert(['id' => $id], ['name' => $name]);
        }

        $password = Hash::make('Agnus2026!');

        // 2. Seed Admin & Registrar
        User::updateOrCreate(
            ['email' => 'admin@agnusdei.local'], 
            ['name' => 'System Admin', 'password' => $password, 'role_id' => 1]
        );
        User::updateOrCreate(
            ['email' => 'registrar@agnusdei.local'], 
            ['name' => 'Head Registrar', 'password' => $password, 'role_id' => 2]
        );
        
        // 3. Seed EXACTLY Maximum 2 Cashiers
        User::updateOrCreate(
            ['email' => 'cashier1@agnusdei.local'], 
            ['name' => 'Cashier Window 1', 'password' => $password, 'role_id' => 3]
        );
        User::updateOrCreate(
            ['email' => 'cashier2@agnusdei.local'], 
            ['name' => 'Cashier Window 2', 'password' => $password, 'role_id' => 3]
        );
        
        // 4. Auxiliary Staff
        User::updateOrCreate(
            ['email' => 'library@agnusdei.local'], 
            ['name' => 'Head Librarian', 'password' => $password, 'role_id' => 5]
        );
        User::updateOrCreate(
            ['email' => 'clinic@agnusdei.local'], 
            ['name' => 'School Nurse', 'password' => $password, 'role_id' => 6]
        );
    }
}
