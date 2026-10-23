@extends('student.simulation.decisions-completed.layout')
@section('title', 'Service Management')
@section('quarterClosureInfo')
  @if (isset($quarterClosureInfo['message']))
    <p>{{ $quarterClosureInfo['message'] }}</p>
  @else
    <div class="serviceManagementClosure">
      @foreach ($quarterClosureInfo as $item)
        <div class="servicetype">
          Region:({{ $item->region->name }}) {{ $item->region->custome_name }}
          <p><span> Service Type:</span> {{ $item->serviceOutsourcing->level_title ?? 'None' }}</p>
        </div>
      @endforeach
    </div>
  @endif
@endsection
@include('student.simulation.decisionsModal.service-management')
