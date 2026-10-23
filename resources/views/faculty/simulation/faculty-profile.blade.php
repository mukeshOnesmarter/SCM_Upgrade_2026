@extends('faculty.simulation.layout')
@section('title', 'Faculty Profile : ' . ucfirst($faculty->first_name . ' ' . $faculty->last_name))
@section('sectionTitle', 'Faculty Profile')
@section('sectionDescription',
  'Explore detailed information about the selected faculty member involved in the simulation.
  Review their assigned student groups, simulation roles, and overall contributions.')
@section('inheritTemplate')
  <h3> {{ ucwords($faculty->first_name . ' ' . $faculty->last_name) }} <br><small>{{ $faculty->email }}</small></h3>
  <p>Bio: {{ $faculty->profile?->bio }}</p>
  <ul style="list-style-type: none">
    <li><strong>Department:</strong> {{ $faculty->department }}</li>
    <li><strong>Designation:</strong> {{ $faculty->designation }}</li>
    <li><strong>Qualifications:</strong> {{ $faculty->profile?->qualifications }}</li>
    <li><strong>Research & Interests:</strong> {{ $faculty->profile?->research_interests }}</li>
  </ul>
@endsection
