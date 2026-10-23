@extends('admin.layout.static')
@section('title', 'Firms')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Firm Directory</h4>
    <p>Manage firms involved in simulations. Track their roles, associated suppliers, and contributions to different
      scenarios.</p>
  </div>
  <div class="firmDirectory">
    @foreach ($firms as $firm)
      <div class="tableRow">
        <div class="item">
          {{ $firm->custome_name }}
        </div>
      </div>
    @endforeach
  </div>
@endsection
