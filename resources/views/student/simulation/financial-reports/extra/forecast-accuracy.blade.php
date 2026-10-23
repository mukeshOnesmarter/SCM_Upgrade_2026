@php $pageTitle = 'Forecasting Accuracy Report'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Forecasting Accuracy Report')
@section('downloadlink', route('student.simulation.download.forecast-accuracy', $routeParams))
@section('reportTemplate')
  <div id="forecastAccuracyChart"></div>
  <div class="forecastingAccuracy" id="forecastingAccuracy">
    {{-- forecastingAccuracy --}}
    <div class="forecastingAccuracy">
      <label for="sectionTitle">FORECASTING ACCURACY</label>
      <div class="regionContainer">
        @foreach ($regions as $region)
          <div class="region">
            <div class="regionLable">Region ({{ $region->name }}) <small>{{ $region->custome_name }}</small></div>
            <div class="accuracyContainer">
              <div class="headSection">
                <div class="item">&nbsp;</div>
                <div class="item">
                  <label for="title">Forecast Demand</label>
                  <ul>
                    <li>Demand: Retail</li>
                    <li>Demand: Direct</li>
                  </ul>
                </div>
                <div class="item">
                  <label for="title">Actual Demand</label>
                  <ul>
                    <li>Demand: Retail</li>
                    <li>Demand: Direct</li>
                  </ul>
                </div>
                <div class="item">
                  <label for="title">Accuracy
                    <x-info-popover title="What Is Forecast Accuracy % ?" chapter="09"
                      route="{{ route('student.manual.chapter09') }}">
                     Forecast Accuracy measures how close your forecast was to the actual sales you were able to fulfill.
                     <br><br>
                      <strong>Important:</strong><br>
                      If actual fulfilled sales are very low, forecast accuracy is shown as
                      <strong>0%</strong>. In such cases, the result reflects a supply or
                      fulfillment issue rather than a forecasting problem.

                      <br><br>
                      Higher percentages indicate better forecasting performance.
                    </x-info-popover>
                  </label>
                  <ul>
                    <li>Demand: Retail</li>
                    <li>Demand: Direct</li>
                  </ul>
                </div>
              </div>
              <div class="productContainer">
                @foreach ($region->products as $product)
                  <div class="product">
                    <label for="productTitle">Product {{ str_pad($product->id, '2', '0', STR_PAD_LEFT) }}</label>
                    <div class="statistics">
                      @foreach ($product->demandData as $data)
                        <div class="item">
                          <ul>
                            <li>{{ $data->demandChannel_01 }}</li>
                            <li>{{ $data->demandChannel_02 }}</li>
                          </ul>
                        </div>
                      @endforeach
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- noteContainer --}}
    <div class="noteContainer">
      <p><strong>Note:</strong>Forecasts count within the calculation of forecasting accuracy only if the "actual" value
        being forecast is greater than 100 for sales volumes (to not penalize you for "small" forecasts). Otherwise, the
        relevant values of "forecast" and "actual" are only reported for reference purposes, but such forecasts are not
        counted for forecasting accuracy scoring. This is the reason why the number of forecasts referenced in "SUMMARY"
        may be less than the detailed line-by-line reporting of forecasts.</p>
    </div>
  </div>
  @if ($reportAnalysis && $reportAnalysis->response)
    <div class="reportAnalysis">
      <div class="headingSection">
        <img src="{{ asset('images/ChatGPT-Logo.png') }}" alt="">
        <h2>Quarter {{ request('quarter_id') }} - Forecast Accuracy Analysis & Insights</h2>
        <p>Measuring the accuracy of demand forecasts to identify gaps and improve planning strategies.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif
@endsection


@section('templateScript')
  <script src="https://code.highcharts.com/highcharts.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      Highcharts.chart('forecastAccuracyChart', {
        chart: {
          zoomType: 'xy'
        },
        title: {
          text: 'Forecasting Accuracy Report'
        },
        xAxis: [{
          categories: [
            @foreach ($regions as $region)
              @foreach ($region->products as $product)
                "Region {{ $region->name }} - Product {{ str_pad($product->id, 2, '0', STR_PAD_LEFT) }}",
              @endforeach
            @endforeach
          ],
          crosshair: true
        }],
        yAxis: [{
          title: {
            text: 'Demand'
          }
        }, {
          title: {
            text: 'Accuracy %'
          },
          labels: {
            format: '{value}%'
          },
          opposite: true,
          max: 100
        }],
        tooltip: {
          shared: true
        },
        legend: {
          layout: 'horizontal',
          align: 'center',
          verticalAlign: 'bottom'
        },
        series: [{
            name: 'Forecast Retail',
            type: 'column',
            data: [
              @foreach ($regions as $region)
                @foreach ($region->products as $product)
                  {{ str_replace(',', '', $product->demandData->forecastDemand->demandChannel_01) }},
                @endforeach
              @endforeach
            ]
          },
          {
            name: 'Forecast Direct',
            type: 'column',
            data: [
              @foreach ($regions as $region)
                @foreach ($region->products as $product)
                  {{ str_replace(',', '', $product->demandData->forecastDemand->demandChannel_02) }},
                @endforeach
              @endforeach
            ]
          },
          {
            name: 'Actual Retail',
            type: 'column',
            data: [
              @foreach ($regions as $region)
                @foreach ($region->products as $product)
                  {{ str_replace(',', '', $product->demandData->actualDemand->demandChannel_01) }},
                @endforeach
              @endforeach
            ]
          },
          {
            name: 'Actual Direct',
            type: 'column',
            data: [
              @foreach ($regions as $region)
                @foreach ($region->products as $product)
                  {{ str_replace(',', '', $product->demandData->actualDemand->demandChannel_02) }},
                @endforeach
              @endforeach
            ]
          },
          {
            name: 'Accuracy Retail',
            type: 'spline',
            yAxis: 1,
            tooltip: {
              valueSuffix: '%'
            },
            data: [
              @foreach ($regions as $region)
                @foreach ($region->products as $product)
                  {{ str_replace('%', '', $product->demandData->demandAccuracy->demandChannel_01) }},
                @endforeach
              @endforeach
            ]
          },
          {
            name: 'Accuracy Direct',
            type: 'spline',
            yAxis: 1,
            tooltip: {
              valueSuffix: '%'
            },
            data: [
              @foreach ($regions as $region)
                @foreach ($region->products as $product)
                  {{ str_replace('%', '', $product->demandData->demandAccuracy->demandChannel_02) }},
                @endforeach
              @endforeach
            ]
          }
        ]
      });
    });
  </script>
@endsection
