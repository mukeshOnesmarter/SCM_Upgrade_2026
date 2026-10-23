@php $pageTitle = 'Product Profit And Loss Statements'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Product P&L Statement')
@section('downloadlink', route('student.simulation.download.product-pnl', $routeParams))
@section('reportTemplate')  
  <div id="productPLDonut" style="height: 450px;"></div>
  <div class="productProfitLossStatement" id="productProfitLossStatement"> 
  @foreach ($productReport as $product)
    <div class="productContainer">
      <div class="productReport">
        <div class="productTitle">
          <small> Product {{ str_pad($product->id, '2', '0', STR_PAD_LEFT) }}</small>
          <label>{{ $product->sort_name . ' - ' . $product->name }}</label>
        </div>
        <div class="groupSection saleOverview">
          <h6>Sale Overview</h6>
          <ul>
            <li>Product Status</li>
            <li>Sales Volume</li>
            <li>Price</li>
            <li class="highlight">Revenues</li>
          </ul>
        </div>
        <div class="groupSection expenses">
          <h6 class="addborder">Expenses</h6>
          <ul>
            <li>- Product Costs</li>
            <li>- Production Shift Cost</li>
            <li>- Fixed Cost Per Order</li>
            <li>- Order Processing</li>
            <li>- Replacement Parts</li>
            <li>- RFID Costs</li>
            <li>- Duties & Tariffs</li>
            <li class="total">= Total Expenses</li>
            <li class="highlight">Gross Margin</li>
          </ul>
        </div>
        <div class="groupSection fixedCost">
          <h6>Fixed Costs</h6>
          <ul>
            <li>- Administrative O/H</li>
            <li>- Forecast Inaccuracy</li>
            <li>- Marketing</li>
            <li>- Price Changes</li>
            <li>- Service Outsourcing</li>
            <li class="total">= Total Fixed Costs</li>
            <li class="highlight">Operating Income</li>
          </ul>
        </div>
      </div>
      <div class="regionContainer">
        @foreach ($product->marketRegion as $region)
          <div class="region">
            <div class="regionTitle">
              <small>Region {{ $region->id }}</small>
              <label>{{ $region->custome_name }}</label>
            </div>
            <div class="reportSection">
              <div class="salesOverview">
                <div class="headSection">
                  <span>Retail</span>
                  <span>Direct</span>
                </div>
                <div class="content">
                  <div class="demandContainer">
                    @foreach ($region->productActive as $item)
                      <div class="demandChannel">{{ ucfirst($item->productStatus) }}</div>
                    @endforeach
                  </div>
                </div>
                <div class="content">
                  <div class="demandContainer">
                    @foreach ($region->productSalesVolume as $item)
                      <div class="demandChannel">{{ number_format($item->salesVolume, 0) }}</div>
                    @endforeach
                  </div>
                </div>
                <div class="content">
                  <div class="demandContainer">
                    @foreach ($region->price as $item)
                      <div class="demandChannel">{{ config('currency.symbol') . number_format($item->price, 0) }}</div>
                    @endforeach
                  </div>
                </div> 
                <div class="content highlight">
                  <div class="demandContainer">
                    @foreach ($region->revenues as $item)
                      <div class="demandChannel">{{ number_format($item->totalRevenues, 0) }}</div>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
            <div class="reportSection">
              <label for="">&nbsp;</label>
              <div class="contentItem">{{ number_format($region->productCost, 0) }}</div>
              <div class="contentItem">{{ number_format($region->productionShiftCost, 0) }}</div>
              <div class="contentItem">{{ number_format($region->fixedCost, 0) }}</div>
              <div class="contentItem">{{ number_format($region->orderProcessing, 0) }}</div>
              <div class="contentItem">{{ number_format($region->replacementParts, 0) }}</div>
              <div class="contentItem">{{ number_format($region->rfidCosts, 0) }}</div>
              <div class="contentItem">{{ number_format($region->dutiesTariffs, 0) }}</div>
              <div class="contentItem total">- {{ number_format($region->totalExpenses, 0) }} </div>
              <div class="contentItem highlight">{{ number_format($region->grossMargin, 0) }}</div>
            </div>
            <div class="reportSection">
              <label for="">&nbsp;</label>
              <div class="contentItem">{{ number_format($region->administrativeOH, 0) }}</div>
              <div class="contentItem">{{ number_format($region->forecastInaccuracy, 0) }}</div>
              <div class="contentItem">{{ number_format($region->marketing, 0) }}</div>
              <div class="contentItem">{{ number_format($region->priceChanges, 0) }}</div>
              <div class="contentItem">{{ number_format($region->serviceOutsourcing, 0) }}</div>
              <div class="contentItem total"> - {{ number_format($region->totalFixedCosts, 0) }}</div>
              <div class="contentItem highlight">{{ number_format($region->operatingIncome, 0) }}</div>
            </div>

          </div>
        @endforeach
      </div>
    </div>
  @endforeach 
  @if ($reportAnalysis && $reportAnalysis->response)
    <div class="reportAnalysis">
      <div class="headingSection"> 
        <img src="{{ asset('images/ChatGPT-Logo.png')}}" alt="">
        <h2>Quarter {{ request('quarter_id') }} - Product P&L Analysis & Insights</h2> 
        <p>Examining profitability, cost structure, and performance trends for each product line.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif
  </div>
@endsection
 
@section('templateScript')
  <script src="https://code.highcharts.com/highcharts.js"></script>
  <script>
  Highcharts.chart('productPLDonut', {
      chart: { type: 'pie' },
      title: {
          text: 'Quarter {{ request('quarter_id') }} - Product P&L Overview'
      },
      exporting: { enabled: false },
      credits: { enabled: false },
      plotOptions: {
          pie: {
              innerSize: '50%',
              dataLabels: {
                  enabled: true,
                  format: '<b>{point.name}</b>: {point.y:,.0f}', // no $ for data labels
                  style: { fontSize: '12px' }
              }
          }
      },
      tooltip: {
          pointFormatter: function() {
              if(this.name === 'Sales Volume') {
                  return `<span style="color:${this.color}">\u25CF</span> ${this.name}: <b>${this.y.toLocaleString()}</b><br/>`;
              } else {
                  return `<span style="color:${this.color}">\u25CF</span> ${this.name}: <b>$${this.y.toLocaleString()}</b><br/>`;
              }
          }
      },
      series: [{
          name: 'Amount',
          data: [
              { name: 'Revenue', y: {{ $productReport->sum('totalRevenue') }}, color: '#4e79a7' },
              { name: 'Total Expenses', y: {{ $productReport->sum('totalExpenses') }}, color: '#f28e2b' },
              { name: 'Gross Margin', y: {{ $productReport->sum('grossMargin') }}, color: '#e15759' },
              { name: 'Total Fixed Costs', y: {{ $productReport->sum('totalFixedCosts') }}, color: '#76b7b2' },
              { name: 'Operating Income', y: {{ $productReport->sum('operatingIncome') }}, color: '#59a14f' },
              { name: 'Sales Volume', y: {{ $productReport->sum('totalSalesVolume') }}, color: '#f1c40f' } // additional slice
          ]
      }]
  });
  </script>
@endsection


