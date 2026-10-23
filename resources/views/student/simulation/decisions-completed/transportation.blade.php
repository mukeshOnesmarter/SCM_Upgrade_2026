@extends('student.simulation.decisions-completed.layout')
@section('title', 'Transportation')
@section('quarterClosureInfo')
  @if (isset($quarterClosureInfo['message']))
    <p>{{ $quarterClosureInfo['message'] }}</p>
  @else
    <div class="transportationClosure">
      <div class="distributionCenterContainer">
        @foreach ($quarterClosureInfo as $distribution)
          <div class="distributionCenter">
            <div class="dcCenterInfo">
              Distribution Center: {{ str_pad($distribution['distribution_center_id'], '2', '0', STR_PAD_LEFT) }}
              <span>Region:({{ $distribution['region']['name'] }} ) {{ $distribution['region']['custome_name'] }} </span>
            </div>
            <div class="productContainer">
              @foreach ($distribution['products'] as $product)
                <div class="product">
                  <div class="productTitle">
                    @if ($product['product_id'] !== 0)
                      <small>Product: {{ str_pad($product['product_id'], '2', '0', STR_PAD_LEFT) }}</small>
                      <label for="">{{ $product['product']['name'] ?? null }}</label>
                    @else
                      <small>Postponed Production</small>
                      <label for="">Product "SHELL"</label>
                    @endif
                  </div>
                  <div class="carrierContainer">
                    @foreach ($product['carriers'] as $carrier)
                      <div class="carrier">
                        <label for="carrierTitle"><i class="fa fa-sliders" aria-hidden="true"></i> Carrier:
                          <span>({{ $carrier['carrier']['name'] }})</span></label>
                        <div class="supplyChannel">
                          @foreach ($carrier['supply_channels'] as $supply)
                            <div class="channel">
                              <label for="">Supply Channel:
                                <span>({{ $supply['supply_channel_id'] === 1 ? 'Air' : 'Surface' }})</span> </label>
                              <strong>{{ number_format($supply['quantity'], 0) }}</strong>
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
    </div>
  @endif
@endsection
@include('student.simulation.decisionsModal.transportation')
