@extends('student.simulation.decisions-completed.layout')
@section('title', 'Forecasting')
@section('quarterClosureInfo')
  @if (isset($quarterClosureInfo['message']))
    <p>{{ $quarterClosureInfo['message'] }}</p>
  @else
    <div class="forcastingClosure">
      @foreach ($quarterClosureInfo as $region)
        <div class="regionContainer">
          <div class="regionTitle">
            <h3>Region: ({{ $region->region->name }}) {{ $region->region->custome_name }}</h3>
          </div>
          <div class="productContainer">
            @foreach ($region->products as $product)
              <div class="product">
                <div class="productTitle">
                  <small>Product: {{ str_pad($product->product->id, '2', '0', STR_PAD_LEFT) }} </small>
                  <label for="heading"> {{ $product->product->name }}</label>
                </div>
                <div class="firmContainer">
                  @foreach ($product->firms as $firm)
                    <div class="firm"> 
                      @if (count($product->firms) > 1)
                        <label for="">Firm: ({{ $firm->firm->name }}) <small>{{ $firm->firm->custome_name }}</small> </label>
                      @endif 
                      <div class="demandChannels">
                        @foreach ($firm->demand as $demand)
                          <div class="demandType">
                            <label> Retail</label>
                            <span>{{ number_format($demand->demand_channel_01, 0) }}</span>
                          </div>
                          <div class="demandType">
                            <label>Direct</label>
                            <span>{{ number_format($demand->demand_channel_02, 0) }}</span>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  @endif
@endsection
@include('student.simulation.decisionsModal.forecasting')