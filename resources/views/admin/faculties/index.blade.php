@extends('admin.layout.static')
@section('title', 'Faculties')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Faculty Management</h4>
    <p>Manage faculty members and their simulation assignments. Track faculty participation, update records, and ensure
      proper role allocation.</p>
  </div>
  <div class="facultyManagement">
    <div class="buttonCreate">
      <a href="{{ route('admin.faculties.create') }}" class="create_record">
        <img src="{{ URL::asset('images/icon/add.svg') }}" alt=""> Add New Faculty</a>
    </div>
    @foreach ($faculties as $item)
      <div class="tableRow">
        <div class="item profilePicture">
          @if (is_null($item->profile?->profile_picture))
            <img src="{{ URL::asset('images/avatar-user.svg') }}" alt="">
          @else
            <img src="{{ URL::asset('storage/uploads/faculty/' . $item->profile?->profile_picture) }}" alt="">
          @endif
        </div>
        <div class="item title">
          <label for="heading">{{ $item->first_name . ' ' . $item->last_name }}</label>
          <small><i class="fa fa-envelope-o" aria-hidden="true"></i> {{ $item->email }}</small>
          <small>Join Date: {{ $item->join_date }}</small>
        </div>
      </div>
    @endforeach
  </div>
@endsection
