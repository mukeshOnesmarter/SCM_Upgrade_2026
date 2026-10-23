<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('students')->insert([
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'Baseline',
                'last_name' => 'Firm',
                'email' => 'baseline.firm@flexee.org',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '1234567890',
                'dob' => '2000-05-15',
                'gender' => 'Female',
                'department' => 'Computer Science',
                'join_date' => '2018-09-01',
                'graduation_date' => '2022-06-15',
            ],
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'Flexee',
                'last_name' => 'AI',
                'email' => 'aicontrolled.firm@flexee.org',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '9876543210',
                'dob' => '1999-08-20',
                'gender' => 'Male',
                'department' => 'Mathematics',
                'join_date' => '2017-09-01',
                'graduation_date' => '2021-06-15',
            ],
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'Charlie',
                'last_name' => 'Brown',
                'email' => 'charlie.brown@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '1122334455',
                'dob' => '2001-02-10',
                'gender' => 'Male',
                'department' => 'Physics',
                'join_date' => '2019-09-01',
                'graduation_date' => '2023-06-15',
            ],
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'Diana',
                'last_name' => 'Taylor',
                'email' => 'diana.taylor@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '6677889900',
                'dob' => '2002-01-25',
                'gender' => 'Female',
                'department' => 'Chemistry',
                'join_date' => '2020-09-01',
                'graduation_date' => null, // Ongoing
            ],
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'Ethan',
                'last_name' => 'Williams',
                'email' => 'ethan.williams@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '4455667788',
                'dob' => '1998-03-18',
                'gender' => 'Male',
                'department' => 'Biology',
                'join_date' => '2016-09-01',
                'graduation_date' => '2020-06-15',
            ],
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'Fiona',
                'last_name' => 'Garcia',
                'email' => 'fiona.garcia@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '9988776655',
                'dob' => '2001-11-30',
                'gender' => 'Female',
                'department' => 'Economics',
                'join_date' => '2019-09-01',
                'graduation_date' => null,
            ],
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'George',
                'last_name' => 'Harris',
                'email' => 'george.harris@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '2233445566',
                'dob' => '2000-07-22',
                'gender' => 'Male',
                'department' => 'History',
                'join_date' => '2018-09-01',
                'graduation_date' => '2022-06-15',
            ],
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'Hannah',
                'last_name' => 'Scott',
                'email' => 'hannah.scott@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '5566778899',
                'dob' => '2003-04-10',
                'gender' => 'Female',
                'department' => 'Philosophy',
                'join_date' => '2021-09-01',
                'graduation_date' => null,
            ],
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'Ivan',
                'last_name' => 'Lee',
                'email' => 'ivan.lee@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '3344556677',
                'dob' => '1999-02-28',
                'gender' => 'Male',
                'department' => 'Linguistics',
                'join_date' => '2017-09-01',
                'graduation_date' => '2021-06-15',
            ],
            [
                'student_code' => rand(10000000, 99999999),
                'first_name' => 'Julia',
                'last_name' => 'Martin',
                'email' => 'julia.martin@example.com',
                'password' => Hash::make(DemoSeeder::password()),
                'phone' => '4455778899',
                'dob' => '2000-10-14',
                'gender' => 'Female',
                'department' => 'Fine Arts',
                'join_date' => '2018-09-01',
                'graduation_date' => '2022-06-15',
            ],
        ]);
    }
}
