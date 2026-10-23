@extends('student.simulation.decisions-completed.layout')
@section('title', 'Demand Planning')
@section('quarterClosureInfo')
  @if (isset($quarterClosureInfo['message']))
    <p>{{ $quarterClosureInfo['message'] }}</p>
  @else
    <div class="demandPlanningClosure">
      @foreach ($quarterClosureInfo as $region)
        <div class="regionContainer">
          <h4>Region: ({{ $region['regionInfo']->name }}) {{ $region['regionInfo']->custome_name }}</h4>
          <div class="productContainer">
            @foreach ($region['products'] as $product)
              <div class="product">
                <label for="heading">
                  <small>Product: {{ str_pad($product['product_id'], '2', '0', STR_PAD_LEFT) }}</small>
                  <label for="heading"> {{ $product['productInfo']->name }} </label>
                </label>
                <div class="demandChannels">
                  @foreach ($product['demand_channels'] as $channel)
                    <div class="channel">
                      <label for="">Demand (<span>{{ $channel['channelInfo']->title }}</span>)</label>
                      <ul>
                        <li><span>Status Active</span> {{ ucfirst($channel['data']['status']) }}</li>
                        <li><span>Price Per Unit</span> ${{ $channel['data']['price'] }}</li>
                        <li><span>Market Spending</span> {{ number_format($channel['data']['marketSpending'], 0) }}</li>
                      </ul>
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
@include('student.simulation.decisionsModal.demand-planning')
