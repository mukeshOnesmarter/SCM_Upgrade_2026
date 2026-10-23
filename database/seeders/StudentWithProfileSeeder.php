<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\StudentProfile;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentWithProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {
            DB::beginTransaction(); // Start Transaction
            $faker = Faker::create(); // Faker instance

            for ($i = 11; $i <= 50; $i++) {
                // Generate Fake Email
                $email = $faker->unique()->safeEmail;

                // Create Student
                $student = Student::create([
                    'student_code' => rand(10000000, 99999999),
                    'first_name' => $faker->firstName,
                    'last_name' => $faker->lastName,
                    'email' => $email,
                    'password' => Hash::make(DemoSeeder::password()), // Default Password
                    'phone' => $faker->phoneNumber,
                    'dob' => $faker->date('Y-m-d', '2005-12-31'), // Max date: 2005-12-31
                    'gender' => $faker->randomElement(['Male', 'Female']),
                    'department' => $faker->randomElement(['Computer Science', 'Mathematics', 'Physics', 'Biology']),
                    'join_date' => $faker->date('Y-m-d', '2019-09-01'),
                    'graduation_date' => $faker->optional()->date('Y-m-d', '2024-06-30'),
                ]);

                // Create Student Profile
                StudentProfile::create([
                    'student_id' => $student->id,
                    'profile_picture' => null,
                    'address' => $faker->address,
                    'nationality' => $faker->country,
                    'emergency_contact' => $faker->phoneNumber,
                    'bio' => $faker->sentence,
                    'social_links' => json_encode([
                        'LinkedIn' => $faker->url,
                        'Twitter' => $faker->url,
                    ]),
                    'academic_interests' => $faker->words(3, true),
                    'skills' => $faker->words(4, true),
                ]);
            }

            DB::commit();

            return response()->json(['message' => '15 students created, invited, and enrolled successfully!'], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback on Error

            return response()->json(['error' => 'Something went wrong: '.$e->getMessage()], 500);
        }
    }
}
