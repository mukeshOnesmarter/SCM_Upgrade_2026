<?php

namespace App\Http\Controllers;

class CourseController extends Controller
{
    public function index()
    {
        return view('courses.index');
    }

    public function leadershipFinance()
    {
        return view('courses.leadership-finance');
    }

    public function activityBook()
    {
        return view('courses.activity-book');
    }

    public function cpim()
    {
        return view('courses.cpim');
    }

    public function cscp()
    {
        return view('courses.cscp');
    }

    public function cltd()
    {
        return view('courses.cltd');
    }

    public function ctsc()
    {
        return view('courses.ctsc');
    }

    public function projectManagement()
    {
        return view('courses.project-management');
    }

    public function leadership()
    {
        return view('courses.leadership');
    }

    public function finance()
    {
        return view('courses.finance');
    }

    public function scorDs()
    {
        return view('courses.scor-ds');
    }

    public function networkDesign()
    {
        return view('courses.network-design');
    }
}
