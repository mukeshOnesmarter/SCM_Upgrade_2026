@extends('admin.layout.static')
@section('title', 'Faculties')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Admin Management</h4>
    <p>Manage administrators, their roles, and system access. Assign permissions, update records, and ensure proper
      role-based access control.</p>
  </div>
  <div class="facultyManagement">
    @foreach ($users as $item)
      <div class="tableRow">
        <div class="item profilePicture">
          <img src="{{ URL::asset('images/avatar-user.svg') }}" alt="">
        </div>
        <div class="item title">
          <label for="heading">{{ $item->first_name . ' ' . $item->last_name }}</label>
          <small><i class="fa fa-envelope-o" aria-hidden="true"></i> {{ $item->email }}</small>
          <small>Role: {{ ucwords(str_replace('-', ' ', $item->role)) }}</small>
        </div>
      </div>
    @endforeach
  </div>
@endsection
