@extends('faculty.simulation.layout')
@section('title', 'Faculties')
@section('sectionTitle', 'Participating Faculties for Simulation')
@section('sectionDescription', 'View the list of faculties who are participating in the simulation.')
@section('inheritTemplate')
  @if (count($faculties) > 0)
    <div class="dataListing">
      @foreach ($faculties as $item)
        @if ($item->faculty->id == $sharedActiveUserData->id)
          <div class="listingRow">
            <div class="item title">
              <label for="heading">{{ $item->faculty->first_name . ' ' . $item->faculty->last_name }} (You)</label>
              <small>{{ $item->faculty->email }}</small>
            </div>
          </div>
        @endif
      @endforeach
      @foreach ($faculties as $item)
        @if ($item->faculty->id != $sharedActiveUserData->id)
          <div class="listingRow">
            <div class="item title">
              <label for="heading">{{ $item->faculty->first_name . ' ' . $item->faculty->last_name }}</label>
              <small>{{ $item->faculty->email }}</small>
            </div>
          </div>
        @endif
      @endforeach
    </div>
  @else
    <p>No Record Found.</p>
  @endif
@endsection
