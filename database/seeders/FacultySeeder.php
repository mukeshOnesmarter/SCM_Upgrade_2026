<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('faculties')->insert([
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'Partner',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '1234567890',
                'department' => 'Computer Science',
                'designation' => 'Professor',
                'join_date' => '2015-06-12',
                'status' => 'Active',
            ],
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'User',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '9876543210',
                'department' => 'Mathematics',
                'designation' => 'Associate Professor',
                'join_date' => '2017-08-19',
                'status' => 'Active',
            ],
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'Partner',
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'email' => 'michael.brown@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '1231231234',
                'department' => 'Physics',
                'designation' => 'HOD',
                'join_date' => '2012-03-10',
                'status' => 'Active',
            ],
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'User',
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'email' => 'emily.davis@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '4564564567',
                'department' => 'Biology',
                'designation' => 'Lecturer',
                'join_date' => '2019-01-22',
                'status' => 'Active',
            ],
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'User',
                'first_name' => 'Chris',
                'last_name' => 'Wilson',
                'email' => 'chris.wilson@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '7897897890',
                'department' => 'Chemistry',
                'designation' => 'Assistant Professor',
                'join_date' => '2018-09-15',
                'status' => 'Active',
            ],
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'Partner',
                'first_name' => 'Sarah',
                'last_name' => 'Miller',
                'email' => 'sarah.miller@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '1239874560',
                'department' => 'Electrical Engineering',
                'designation' => 'Dean',
                'join_date' => '2010-05-20',
                'status' => 'Active',
            ],
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'User',
                'first_name' => 'David',
                'last_name' => 'Moore',
                'email' => 'david.moore@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '5647382910',
                'department' => 'Mechanical Engineering',
                'designation' => 'Senior Lecturer',
                'join_date' => '2016-07-18',
                'status' => 'Active',
            ],
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'User',
                'first_name' => 'Laura',
                'last_name' => 'Taylor',
                'email' => 'laura.taylor@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '2109876543',
                'department' => 'Civil Engineering',
                'designation' => 'Assistant Professor',
                'join_date' => '2018-02-14',
                'status' => 'Active',
            ],
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'User',
                'first_name' => 'James',
                'last_name' => 'Anderson',
                'email' => 'james.anderson@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '8765432190',
                'department' => 'Information Technology',
                'designation' => 'Lecturer',
                'join_date' => '2020-10-05',
                'status' => 'Active',
            ],
            [
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'User',
                'first_name' => 'Sophia',
                'last_name' => 'Martin',
                'email' => 'sophia.martin@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '3456781234',
                'department' => 'Economics',
                'designation' => 'Research Assistant',
                'join_date' => '2021-01-11',
                'status' => 'Active',
            ],
        ]);
    }
}
