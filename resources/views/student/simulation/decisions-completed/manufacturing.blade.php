@extends('student.simulation.decisions-completed.layout')
@section('title', 'Manufacturing')
@section('quarterClosureInfo')
  @if (isset($quarterClosureInfo['message']))
    <p>{{ $quarterClosureInfo['message'] }}</p>
  @else
    <div class="manufacturingClosure">
      @foreach ($quarterClosureInfo as $item)
        @if ($item->product_id !== 0)
          <div class="product">
            <div class="sectionHeader">
              <small>Product -{{ str_pad($item->product_id, '2', '0', STR_PAD_LEFT) }}</small>
              <label for="heading">{{ $item->product['name'] }}</label>
            </div>
            <ul>
              <li><label>Production Quantity</label>
                <span><strong>Total Unit:</strong> {{ number_format($item->production_quantity ?? 0) }}</span>
              </li>
              <li><label>Emergency Production</label>
                <span><strong>Total Unit:</strong> {{ number_format($item->emergency_production ?? 0) }}</span>
              </li>
              <li><label>Adjustable Production</label>
                <span><strong>Total Unit:</strong> {{ number_format($item->adjustable_production ?? 0) }}</span>
              </li>
            </ul>
          </div>
        @else
          <div class="product">
            <div class="sectionHeader">
              <small>Product Zero</small>
              <label for="heading">Postponed Production</label>
            </div>
            <ul>
              <li><label>Production Quantity</label>
                <span>Total Unit: {{ number_format($item->production_quantity ?? 0) }}</span>
              </li>
              {{-- <li><label>Emergency Production</label>
                <span><strong>Total Unit:</strong> {{ number_format($item->emergency_production ?? 0) }}</span>
              </li>
              <li><label>Adjustable Production</label>
                <span><strong>Total Unit:</strong> {{ number_format($item->adjustable_production ?? 0) }}</span>
              </li> --}}
            </ul>
          </div>
        @endif
      @endforeach
    </div>
  @endif
@endsection
@include('student.simulation.decisionsModal.manufacturing')
