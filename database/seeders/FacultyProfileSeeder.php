<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacultyProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('faculty_profiles')->insert([
            [
                'faculty_id' => 1,
                'profile_picture' => 'faculty-01.jpg',
                'address' => '123 Main Street, Springfield, IL',
                'dob' => '1980-04-15',
                'gender' => 'Male',
                'qualifications' => 'Ph.D. in Computer Science',
                'research_interests' => 'Artificial Intelligence, Machine Learning, Data Science',
                'experience' => 10,
                'bio' => 'An experienced professor passionate about cutting-edge technology.',
                'social_links' => json_encode(['LinkedIn' => 'https://linkedin.com/in/johndoe', 'Twitter' => 'https://twitter.com/johndoe']),
            ],
            [
                'faculty_id' => 2,
                'profile_picture' => 'faculty-02.jpg',
                'address' => '456 Elm Street, Boston, MA',
                'dob' => '1985-07-20',
                'gender' => 'Female',
                'qualifications' => 'M.Sc. in Mathematics',
                'research_interests' => 'Topology, Differential Geometry, Algebraic Structures',
                'experience' => 8,
                'bio' => 'Committed to advancing research in pure mathematics.',
                'social_links' => json_encode(['LinkedIn' => 'https://linkedin.com/in/janesmith']),
            ],
            [
                'faculty_id' => 3,
                'profile_picture' => 'faculty-03.jpg',
                'address' => '789 Pine Street, Denver, CO',
                'dob' => '1975-11-10',
                'gender' => 'Male',
                'qualifications' => 'Ph.D. in Physics',
                'research_interests' => 'Quantum Mechanics, Astrophysics, Theoretical Physics',
                'experience' => 15,
                'bio' => 'HOD in Physics with extensive teaching and research expertise.',
                'social_links' => json_encode(['LinkedIn' => 'https://linkedin.com/in/michaelbrown']),
            ],
            [
                'faculty_id' => 4,
                'profile_picture' => 'faculty-04.jpg',
                'address' => '101 Maple Avenue, Seattle, WA',
                'dob' => '1990-03-25',
                'gender' => 'Female',
                'qualifications' => 'M.Sc. in Biology',
                'research_interests' => 'Microbiology, Genomics, Cell Biology',
                'experience' => 4,
                'bio' => 'A young researcher eager to explore the wonders of biology.',
                'social_links' => json_encode(['Twitter' => 'https://twitter.com/emilydavis']),
            ],
            [
                'faculty_id' => 5,
                'profile_picture' => 'faculty-05.jpg',
                'address' => '555 Oak Lane, Austin, TX',
                'dob' => '1988-02-14',
                'gender' => 'Male',
                'qualifications' => 'M.Sc. in Chemistry',
                'research_interests' => 'Organic Chemistry, Material Science',
                'experience' => 6,
                'bio' => 'An Assistant Professor passionate about innovative chemical research.',
                'social_links' => json_encode(['LinkedIn' => 'https://linkedin.com/in/chriswilson']),
            ],
            [
                'faculty_id' => 6,
                'profile_picture' => 'faculty-06.jpg',
                'address' => '789 Walnut Street, New York, NY',
                'dob' => '1970-12-05',
                'gender' => 'Female',
                'qualifications' => 'Ph.D. in Electrical Engineering',
                'research_interests' => 'Renewable Energy, Power Systems, IoT',
                'experience' => 20,
                'bio' => 'Dean of Engineering, dedicated to mentoring and leadership.',
                'social_links' => json_encode(['LinkedIn' => 'https://linkedin.com/in/sarahmiller']),
            ],
            [
                'faculty_id' => 7,
                'profile_picture' => 'faculty-07.jpg',
                'address' => '202 Birch Avenue, San Diego, CA',
                'dob' => '1982-05-30',
                'gender' => 'Male',
                'qualifications' => 'M.Sc. in Mechanical Engineering',
                'research_interests' => 'Robotics, CAD/CAM, Thermal Systems',
                'experience' => 12,
                'bio' => 'Senior Lecturer focusing on practical engineering applications.',
                'social_links' => json_encode(['Twitter' => 'https://twitter.com/davidmoore']),
            ],
            [
                'faculty_id' => 8,
                'profile_picture' => 'faculty-08.jpg',
                'address' => '333 Cedar Road, Portland, OR',
                'dob' => '1991-08-19',
                'gender' => 'Female',
                'qualifications' => 'M.Sc. in Civil Engineering',
                'research_interests' => 'Sustainable Construction, Structural Engineering',
                'experience' => 5,
                'bio' => 'Dedicated to sustainable development in civil engineering.',
                'social_links' => json_encode(['LinkedIn' => 'https://linkedin.com/in/laurataylor']),
            ],
            [
                'faculty_id' => 9,
                'profile_picture' => 'faculty-09.jpg',
                'address' => '909 Willow Street, Chicago, IL',
                'dob' => '1993-01-07',
                'gender' => 'Male',
                'qualifications' => 'M.Sc. in Information Technology',
                'research_interests' => 'Cybersecurity, Cloud Computing, Web Development',
                'experience' => 3,
                'bio' => 'A passionate lecturer enthusiastic about modern IT trends.',
                'social_links' => json_encode(['LinkedIn' => 'https://linkedin.com/in/jamesanderson']),
            ],
            [
                'faculty_id' => 10,
                'profile_picture' => null,
                'address' => '222 Spruce Lane, Atlanta, GA',
                'dob' => '1995-09-12',
                'gender' => 'Female',
                'qualifications' => 'M.A. in Economics',
                'research_interests' => 'Behavioral Economics, Econometrics',
                'experience' => 2,
                'bio' => 'Research assistant exploring the dynamics of behavioral economics.',
                'social_links' => json_encode(['Twitter' => 'https://twitter.com/sophiamartin']),
            ],
        ]);
    }
}
