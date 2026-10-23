<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateFacultyRequest;
use App\Models\Faculty;
use App\Models\FacultyProfile;
use App\Support\NewAccountPassword;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FacultyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $faculties = Faculty::with('profile')->get();
        $faculties->each(function ($item) {
            $item->join_date = Carbon::parse($item->join_date)->format('m/d/Y');
        });

        return view('admin.faculties.index', compact('faculties'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('admin.faculties.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(StoreFacultyRequest $request)
    {
        try {
            // Insert Faculty
            $faculty = Faculty::create([
                'faculty_code' => 'FAC'.rand(10000000, 99999999),
                'role' => 'User',
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => NewAccountPassword::placeholder(),
                'phone' => $request->phone ?? '1234567890',
                'department' => 'Computer Science',
                'designation' => 'Professor',
                'join_date' => now(),
                'status' => 'Active',
            ]);

            // Insert Faculty Profile with default values
            FacultyProfile::create([
                'faculty_id' => $faculty->id,
                'profile_picture' => null,
                'address' => null,
                'dob' => null,
                'gender' => null,
                'qualifications' => null,
                'research_interests' => null,
                'experience' => 0,
                'bio' => null,
                'social_links' => json_encode([]),
            ]);

            // A link to set their own password (there's no shared default any more).
            NewAccountPassword::sendSetLink($faculty, 'faculty');

            return redirect()
                ->back()
                ->with('success', 'Faculty created. We’ve emailed them a link to set their password.');
        } catch (\Exception $e) {
            Log::error('Faculty create failed: '.$e->getMessage());

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Something went wrong while creating the faculty. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Faculty $faculty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Faculty $faculty)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateFacultyRequest $request, Faculty $faculty)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Faculty $faculty)
    {
        //
    }
}
