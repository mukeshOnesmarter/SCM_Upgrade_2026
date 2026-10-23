@extends('admin.layout')
@section('title', 'Courses')
@section('template')
  <div class="wraptemplateBody">
    <div class="templateBody">
      <div class="coursesListing">
        {{-- PAGETITLE --}}
        <div class="pageTitle">
          <h4>Simulation Management</h4>
          <p>Create, manage, and oversee all simulations. Assign faculty, monitor progress, and track key updates to
            ensure smooth execution.</p>
        </div>
        {{-- BUTTONCREATE --}}
        <div class="buttonCreate">
          <a href="{{ route(Route::has($createRoute = str_replace('index', 'create', Route::current()->getName())) ? $createRoute : 'admin.simulation.create') }}" class="create_record">
            <img src="{{ URL::asset('images/icon/add.svg') }}" alt=""> Add New Course</a>
        </div>

        {{-- INVOICEDETAIL --}}
        @if (count($courses) > 0)
          <div class="invoiceDetail">
            <div class="item">
              <h6>Total Invoice <small>Total billed amount</small></h6>
              <span>{{ config('currency.symbol') . $totalInvoice }}</span>
            </div>
            <div class="item receive">
              <h6>Receive Amount <small>Amount received from clients</small></h6>
              <span>{{ config('currency.symbol') . $receiveAmount }}</span>
            </div>
            <div class="item balance">
              <h6>Balance Amount <small>Pending amount to be collected</small></h6>
              <span>{{ config('currency.symbol') . $balanceAmount }}</span>
            </div>
            <div class="item">&nbsp;</div>
          </div>
        @endif 

        {{-- DATALISTINGS --}}
        <div class="courseListings" id="courseListings">
          @if (count($courses) > 0)
            <div class="headRow">
              <div class="item">Simulation
                <small>Course Title & Institute</small>
              </div>
              <div class="item">Start Date
                <small>Date the Course Begins</small>
              </div>
              <div class="item">End Date
                <small>Date the Course Concludes</small>
              </div>
              <div class="item">Licence
                <small>Allocated Licences</small>
              </div>
              <div class="item">Invoice Status
                <small>Billing & Payment Status</small>
              </div>
            </div>
            <div class="dataTable"> 
              @foreach ($courses as $item)
                <div class="tableRow">
                  <div class="item title">
                    <label for="title"> {{ $item->title }} </label>
                    <small>Institution: {{ $item->organization }}</small>
                    @if ($sharedActiveUserData->role === 'super-admin')
                      <small> Created By: {{ $item->simulationAdmin ? ucwords($item->simulationAdmin->first_name . ' ' . $item->simulationAdmin->last_name) : 'N/A' }} </small>
                    @endif 
                  </div>
                  <div class="item">
                    {{ $item->start_date }}</div>
                  <div class="item">
                    {{ $item->end_date }}</div>
                  <div class="item">
                    {{ $item->licence_count }}</div>
                  <div class="item invoice">
                    {{ '$' . $item->licence_count * $item->fee }}
                    <small
                      class="{{ $item->payment_status === 'settled' ? 'success' : '' }}">{{ ucfirst($item->payment_status) }}</small>
                  </div>
                  <div class="item">
                    <a href="{{ route('admin.simulation.show', $item->id) }}" class="create_record">
                      <i class="fa fa-external-link" aria-hidden="true"></i></a>
                  </div>
                </div>
              @endforeach
            </div>
          @else
            <p>Get started by creating a new simulation to assign to faculty and manage the process effectively.</p>
          @endif
        </div>
      </div>
      <p>&nbsp;</p>
    </div>
  </div>
@endsection
