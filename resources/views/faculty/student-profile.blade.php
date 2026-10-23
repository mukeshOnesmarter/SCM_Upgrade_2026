@extends('faculty.layout')
@section('title', 'Student Profile : ' . ucfirst($student->first_name . ' ' . $student->last_name))
@section('template')
  STUDENT PROFILE. 
  {{ $student }}
@endsection
