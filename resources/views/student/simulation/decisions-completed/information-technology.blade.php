@extends('student.simulation.decisions-completed.layout')
@section('title', 'Information Technology')
@section('quarterClosureInfo')
  @if (isset($quarterClosureInfo['message']))
    <p>{{ $quarterClosureInfo['message'] }}</p>
  @else
    <div class="synchronizationClosure">
      <div class="item suppliersClosure">
        <div class="sectionHeading">
          <label for="heading">IT Synchronization with <small>Sub-Assembly Component Suppliers</small></label>
        </div>
        @foreach ($quarterClosureInfo->suppliers as $item)
          <div class="supplier">
            Supplier ({{ $item->supplier->name }}) | {{ $item->supplier->custome_name }}
            <p><strong>Materials Supplied:</strong>
              @foreach ($item->supplier->supplierComponent as $item)
                {{$item->subAssemblyComponent->name}}
                @if (!$loop->last)
                ,
                @endif
              @endforeach
            </p>
          </div>
        @endforeach
      </div>
      <div class="item carriersClosure">
        <div class="sectionHeading">
          <label for="heading">IT Synchronization with <small>Plant-To-DC Carriers</small></label>
        </div>
        @foreach ($quarterClosureInfo->carriers as $item)
          <div class="carrier">
            Carrier ({{ $item->carrier->name }})
            <p><strong>Plant-To-DC:</strong> Synchronization</p>
          </div>
        @endforeach
      </div>
    </div>
  @endif
@endsection
@include('student.simulation.decisionsModal.information-technology')
