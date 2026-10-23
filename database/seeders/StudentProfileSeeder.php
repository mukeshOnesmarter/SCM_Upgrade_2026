<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StudentProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('student_profiles')->insert([
            [
                'student_id' => 1,
                'profile_picture' => 'student-01.jpg',
                'address' => '123 Main Street, Springfield, USA',
                'nationality' => 'American',
                'emergency_contact' => '9876543210',
                'bio' => 'Passionate about AI and Machine Learning.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/alicejohnson', 'Twitter' => 'twitter.com/alicejohnson']),
                'academic_interests' => 'Artificial Intelligence, Data Science',
                'skills' => 'Python, R, Machine Learning',
            ],
            [
                'student_id' => 2,
                'profile_picture' => 'student-02.jpg',
                'address' => '456 Elm Street, Riverdale, USA',
                'nationality' => 'Canadian',
                'emergency_contact' => '8765432109',
                'bio' => 'Mathematics enthusiast, with a passion for teaching.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/bobsmith', 'Twitter' => 'twitter.com/bobsmith']),
                'academic_interests' => 'Pure Mathematics, Algebra',
                'skills' => 'Matlab, LaTeX',
            ],
            [
                'student_id' => 3,
                'profile_picture' => 'student-03.jpg',
                'address' => '789 Pine Street, Metropolis, USA',
                'nationality' => 'British',
                'emergency_contact' => '7654321098',
                'bio' => 'Physics lover, aspiring astrophysicist.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/charliebrown', 'Twitter' => 'twitter.com/charliebrown']),
                'academic_interests' => 'Astrophysics, Quantum Mechanics',
                'skills' => 'MATLAB, Research Writing',
            ],
            [
                'student_id' => 4,
                'profile_picture' => 'student-04.jpg',
                'address' => '321 Oak Street, Gotham City, USA',
                'nationality' => 'Indian',
                'emergency_contact' => '6543210987',
                'bio' => 'Chemistry explorer and future researcher.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/dianataylor', 'Twitter' => 'twitter.com/dianataylor']),
                'academic_interests' => 'Organic Chemistry, Analytical Chemistry',
                'skills' => 'ChemDraw, Research Analysis',
            ],
            [
                'student_id' => 5,
                'profile_picture' => 'student-05.jpg',
                'address' => '987 Maple Avenue, Smallville, USA',
                'nationality' => 'Australian',
                'emergency_contact' => '5432109876',
                'bio' => 'Biology student with an interest in genetics.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/ethanwilliams', 'Twitter' => 'twitter.com/ethanwilliams']),
                'academic_interests' => 'Genetics, Molecular Biology',
                'skills' => 'Lab Techniques, Report Writing',
            ],
            [
                'student_id' => 6,
                'profile_picture' => 'student-06.jpg',
                'address' => '321 Cherry Lane, Star City, USA',
                'nationality' => 'German',
                'emergency_contact' => '4321098765',
                'bio' => 'Economics student passionate about macroeconomics.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/fionagarcia', 'Twitter' => 'twitter.com/fionagarcia']),
                'academic_interests' => 'Macroeconomics, Econometrics',
                'skills' => 'Data Analysis, Stata',
            ],
            [
                'student_id' => 7,
                'profile_picture' => 'student-07.jpg',
                'address' => '654 Walnut Street, Central City, USA',
                'nationality' => 'Mexican',
                'emergency_contact' => '3210987654',
                'bio' => 'History student with a focus on world wars.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/georgeharris', 'Twitter' => 'twitter.com/georgeharris']),
                'academic_interests' => 'World War II, Ancient Civilizations',
                'skills' => 'Archival Research, Writing',
            ],
            [
                'student_id' => 8,
                'profile_picture' => 'student-08.jpg',
                'address' => '789 Cedar Street, Coast City, USA',
                'nationality' => 'Japanese',
                'emergency_contact' => '2109876543',
                'bio' => 'Philosophy major, aspiring to be an academic.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/hannahscott', 'Twitter' => 'twitter.com/hannahscott']),
                'academic_interests' => 'Ethics, Existential Philosophy',
                'skills' => 'Critical Thinking, Writing',
            ],
            [
                'student_id' => 9,
                'profile_picture' => 'student-09.jpg',
                'address' => '456 Birch Road, Emerald City, USA',
                'nationality' => 'Korean',
                'emergency_contact' => '1098765432',
                'bio' => 'Linguistics student with a love for phonetics.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/ivanlee', 'Twitter' => 'twitter.com/ivanlee']),
                'academic_interests' => 'Phonetics, Syntax',
                'skills' => 'Linguistic Analysis, Writing',
            ],
            [
                'student_id' => 10,
                'profile_picture' => null,
                'address' => '123 Sycamore Avenue, Hill Valley, USA',
                'nationality' => 'Russian',
                'emergency_contact' => '0987654321',
                'bio' => 'Fine arts major with an interest in sculpture.',
                'social_links' => json_encode(['LinkedIn' => 'linkedin.com/juliamartin', 'Twitter' => 'twitter.com/juliamartin']),
                'academic_interests' => 'Sculpture, Art History',
                'skills' => 'Sculpting, Painting',
            ],
        ]);
    }
}
