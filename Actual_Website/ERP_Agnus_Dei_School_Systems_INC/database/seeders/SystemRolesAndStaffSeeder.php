<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Teacher;

class SystemRolesAndStaffSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            1 => 'Admin',
            2 => 'Registrar',
            3 => 'Cashier',
            4 => 'Teacher',
            5 => 'Librarian',
            6 => 'Nurse',
            7 => 'Student',
            8 => 'Principal',
            9 => 'Directress',
        ];

        foreach ($roles as $id => $name) {
            DB::table('roles')->updateOrInsert(['id' => $id], ['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        $password = Hash::make('Agnus2026!');

        $staff = [
            ['name' => 'System Admin', 'email' => 'admin@agnusdei.local', 'role_id' => 1],
            ['name' => 'Head Registrar', 'email' => 'registrar@agnusdei.local', 'role_id' => 2],
            ['name' => 'Cashier Window 1', 'email' => 'cashier1@agnusdei.local', 'role_id' => 3],
            ['name' => 'Cashier Window 2', 'email' => 'cashier2@agnusdei.local', 'role_id' => 3],
            ['name' => 'School Principal', 'email' => 'principal@agnusdei.local', 'role_id' => 8],
            ['name' => 'School Directress', 'email' => 'directress@agnusdei.local', 'role_id' => 9],
            ['name' => 'Head Librarian', 'email' => 'library@agnusdei.local', 'role_id' => 5],
            ['name' => 'School Nurse', 'email' => 'clinic@agnusdei.local', 'role_id' => 6],
        ];

        foreach ($staff as $s) {
            User::updateOrCreate(
                ['email' => $s['email']],
                [
                    'name' => $s['name'],
                    'password' => $password,
                    'role_id' => $s['role_id'],
                    'status' => 'active',
                    'has_seen_welcome' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $teacherSeeds = [
            ['first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria.santos@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Jose', 'last_name' => 'Reyes', 'email' => 'jose.reyes@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'ana.cruz@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Paolo', 'last_name' => 'Garcia', 'email' => 'paolo.garcia@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Rosa', 'last_name' => 'Villanueva', 'email' => 'rosa.villanueva@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Daniel', 'last_name' => 'Mercado', 'email' => 'daniel.mercado@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Liza', 'last_name' => 'Mendoza', 'email' => 'liza.mendoza@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Mark', 'last_name' => 'Torres', 'email' => 'mark.torres@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Rina', 'last_name' => 'Flores', 'email' => 'rina.flores@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Dennis', 'last_name' => 'Aquino', 'email' => 'dennis.aquino@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Grace', 'last_name' => 'Domingo', 'email' => 'grace.domingo@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Carlo', 'last_name' => 'Bautista', 'email' => 'carlo.bautista@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Carla', 'last_name' => 'Navarro', 'email' => 'carla.navarro@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Vincent', 'last_name' => 'Luna', 'email' => 'vincent.luna@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Sheila', 'last_name' => 'Ramos', 'email' => 'sheila.ramos@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Adrian', 'last_name' => 'Castro', 'email' => 'adrian.castro@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Elaine', 'last_name' => 'Sy', 'email' => 'elaine.sy@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Patrick', 'last_name' => 'Lopez', 'email' => 'patrick.lopez@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Teresa', 'last_name' => 'Natividad', 'email' => 'teresa.natividad@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Nico', 'last_name' => 'Salazar', 'email' => 'nico.salazar@agnusdei.local', 'department' => 'Elementary'],
        ];

        $teacherNumber = 1;
        foreach ($teacherSeeds as $ts) {
            $fullName = $ts['first_name'] . ' ' . $ts['last_name'];
            $phone = '09' . str_pad((string) $teacherNumber, 9, '0', STR_PAD_LEFT);

            $user = User::updateOrCreate(
                ['email' => $ts['email']],
                [
                    'name' => $fullName,
                    'password' => $password,
                    'role_id' => 4,
                    'contact_number' => $phone,
                    'status' => 'active',
                    'has_seen_welcome' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            Teacher::updateOrCreate(
                ['email' => $ts['email']],
                [
                    'user_id' => $user->id,
                    'teacher_number' => 'TCH-' . str_pad((string) $teacherNumber, 4, '0', STR_PAD_LEFT),
                    'first_name' => $ts['first_name'],
                    'last_name' => $ts['last_name'],
                    'email' => $ts['email'],
                    'phone' => $phone,
                    'department' => $ts['department'],
                    'status' => 'active',
                ]
            );

            $teacherNumber++;
        }
    }
}
