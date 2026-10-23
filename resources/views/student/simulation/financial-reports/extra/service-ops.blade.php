@php $pageTitle = 'Service Center Operations Reports'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Service Center Operations Reports')
@section('downloadlink', route('student.simulation.download.service-ops', $routeParams))
@section('reportTemplate')
  <div id="serviceChart"></div>
  <div class="serviceCenterOpration">
    <div class="regionContainer">
      @foreach ($regions as $region)
        <div class="region">
          <div class="regionTitle">
            Region ({{ $region->name }}): {{ $region->custome_name }}
          </div>
          <div class="productContainer">
            @foreach ($region->products as $product)
              <div class="product">
                <div class="productTitle">
                  {{ $product->name }}
                </div>
                <div class="servicesCost">
                  <div class="item">Total Calls <strong>{{ number_format($product->calls, 0) }}</strong></div>
                  <div class="item">Csr Cost / Call&nbsp;
                    <x-info-popover chapter="07" route="{{ route('student.manual.chapter07') }}">
                      Cost incurred for each customer service call, determined by service level.
                    </x-info-popover>
                    <strong>{{ config('currency.symbol') . $product->csr_cost }}</strong>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
          <div class="totalCalls">
            <small>Overall Calls</small>
            {{ number_format($region->totalCall, 0) }}
            <small>Overall Calls Cost</small>
            {{ number_format($region->serviceOutsourcingTotal, 0) }}
          </div>
        </div>
      @endforeach
    </div>
  </div>
  @if ($reportAnalysis && $reportAnalysis->response)
    <div class="reportAnalysis">
      <div class="headingSection">
        <img src="{{ asset('images/ChatGPT-Logo.png') }}" alt="">
        <h2>Quarter {{ request('quarter_id') }} - Service Center Operations Analysis & Insights</h2>
        <p>Assessing service center efficiency, workload, and performance metrics to enhance customer satisfaction.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif
@endsection 
@section('templateScript')
  <script src="https://code.highcharts.com/highcharts.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    Highcharts.chart('serviceChart', {
      chart: {
        type: 'column'
      },
      title: {
        text: 'Service Center Operations'
      },
      xAxis: {
        categories: ['Smart Home Assistant', 'Smart Thermostat'],
        crosshair: true
      },
      yAxis: {
        min: 0,
        title: {
          text: 'Total Calls'
        }
      },
      tooltip: {
        shared: true
      },
      plotOptions: {
        column: {
          grouping: true,
          pointPadding: 0.1,
          borderWidth: 0
        }
      },
      series: [
        {
          name: 'North America',
          data: [
            {{ $regions[0]->products[0]->calls ?? 0 }},
            {{ $regions[0]->products[1]->calls ?? 0 }}
          ]
        },
        {
          name: 'South America',
          data: [
            {{ $regions[1]->products[0]->calls ?? 0 }},
            {{ $regions[1]->products[1]->calls ?? 0 }}
          ]
        },
        {
          name: 'Central America',
          data: [
            {{ $regions[2]->products[0]->calls ?? 0 }},
            {{ $regions[2]->products[1]->calls ?? 0 }}
          ]
        }
      ]
    });
  });
</script>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    Highcharts.chart('serviceChart', {
      chart: {
        zoomType: 'xy'
      },
      title: {
        text: 'Service Center Operations Report'
      },
      xAxis: {
        categories: ['North America', 'South America', 'Central America'],
        crosshair: true
      },
      yAxis: [{ // Primary yAxis (Calls)
        labels: {
          format: '{value}',
        },
        title: {
          text: 'Total Calls'
        }
      }, { // Secondary yAxis (Cost)
        title: {
          text: 'Overall Cost ($)'
        },
        labels: {
          format: '${value}'
        },
        opposite: true
      }],
      tooltip: {
        shared: true
      },
      legend: {
        layout: 'horizontal',
        align: 'center',
        verticalAlign: 'bottom'
      },
      series: [
        {   // Bar for Calls
          name: 'Total Calls',
          type: 'column',
          yAxis: 0,
          data: [
            {{ $regions[0]->totalCall ?? 0 }},
            {{ $regions[1]->totalCall ?? 0 }},
            {{ $regions[2]->totalCall ?? 0 }}
          ],
          tooltip: {
            valueSuffix: ' calls'
          }
        },
        {   // Line for Cost
          name: 'Overall Calls Cost',
          type: 'spline',
          yAxis: 1,
          data: [
            {{ $regions[0]->serviceOutsourcingTotal ?? 0 }},
            {{ $regions[1]->serviceOutsourcingTotal ?? 0 }},
            {{ $regions[2]->serviceOutsourcingTotal ?? 0 }}
          ],
          tooltip: {
            valuePrefix: '$'
          }
        }
      ]
    });
  });
</script>
@endsection
