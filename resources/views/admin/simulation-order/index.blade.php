@extends('admin.layout.static')
@section('title', 'Simulation Order')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Simulation Order</h4>
    <p>Manage firms involved in simulations. Track their roles, associated suppliers, and contributions to different
      scenarios.</p>
  </div>

  <div class="buttonCreate">
  <a href="{{ route('admin.simulation-order.create') }}" class="create_record">
    <img src="{{ URL::asset('images/icon/add.svg') }}" alt=""> Create New Order</a>
  </div> 
@endsection
