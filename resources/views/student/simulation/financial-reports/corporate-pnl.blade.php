@php $pageTitle = 'Corporate Profit And Loss Statement'; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp
@extends('student.simulation.financial-reports.layout')
@section('title', 'Corporate P&L Statement')
@section('downloadlink', route('student.simulation.download.corporate-pnl', $routeParams))
@section('reportTemplate')
  <div id="corporatePLDonut" style="height: 450px;"></div>
  <p style="font-size: 0.75rem; "><strong>Note:</strong> Weighted Avg Selling Price is calculated across regions and
    channels using fulfilled units.
    Total Revenue is computed region-wise using actual channel prices and may not equal Sales Volume × Weighted Avg Price
    when fulfillment is partial or prices vary.</p>
  <div class="corporateProfitLossStatement" id="corporateProfitLossStatement">
    <div class="productContainer">
      <div class="productReport">
        <div class="productTitle">&nbsp;</div>
        <div class="groupSection saleOverview">
          <h6>Market Demand</h6>
          <ul>
            <li>Demand Volume</li>
          </ul>
        </div>
        <div class="groupSection saleOverview">
          <h6>Sale Overview</h6>
          <ul>
            <li>Sales Volume</li>
            <li>Weighted Avg Price ({{ config('currency.symbol') }})
              <x-info-popover
                title="Weighted Average Selling Price"
                chapter="Pricing">
                Calculated using fulfilled units and actual prices across regions and channels.
                Due to price differences and partial fulfillment, total revenue may not equal sales volume × weighted average price.
              </x-info-popover>
            </li>
            <li>Unfilled Orders</li>
            <li>Total Revenue ({{ config('currency.symbol') }})</li>
          </ul>
        </div>
        <div class="groupSection expenses">
          <h6 class="addborder">Expenses</h6>
          <ul>
            <li>- Product Costs ({{ config('currency.symbol') }})</li>
            <li>- Production Shift Cost ({{ config('currency.symbol') }})</li>
            <li>- Fixed Order Cost ({{ config('currency.symbol') }})</li>
            <li>- Order Processing ({{ config('currency.symbol') }})</li>
            <li>- Replacement Parts ({{ config('currency.symbol') }})</li>
            <li>- RFID Costs ({{ config('currency.symbol') }})
              <x-info-popover title="What Is RFID Costs ?" chapter="05" route="{{ route('student.manual.chapter05') }}">
                RFID tagging is required for retail-channel sales. Choose between outsourced and insourced tagging
                strategies.
              </x-info-popover>

            </li>
            <li>- Transportation Costs ({{ config('currency.symbol') }})</li>
            <li>+ Transportation Rebates ({{ config('currency.symbol') }})</li>
            <li>+ Volume Discounts ({{ config('currency.symbol') }})</li>
            <li>- Duties & Tariffs ({{ config('currency.symbol') }})</li>
            <li class="total">= Total Expenses ({{ config('currency.symbol') }})</li>
            <li class="highlight">Gross Margin ({{ config('currency.symbol') }})
              <x-info-popover title="What Is Gross Margin ?" chapter="11"
                route="{{ route('student.manual.chapter11') }}">
                Gross Margin is calculated as Revenue minus all product-level costs. It reflects how efficiently a firm
                turns sales into profit.
              </x-info-popover>
            </li>
          </ul>
        </div>
        <div class="groupSection fixedCost">
          <h6>Fixed Costs</h6>
          <ul>
            <li>Administrative O/H ({{ config('currency.symbol') }})</li>
            <li>Corporate O/H ({{ config('currency.symbol') }})</li>
            <li>Consulting Fees ({{ config('currency.symbol') }})</li>
            <li>Cross-Docking ({{ config('currency.symbol') }})</li>
            <li>Distribution FC ({{ config('currency.symbol') }})</li>
            <li>Emergency Procurement ({{ config('currency.symbol') }})</li>
            <li>Emergency Production ({{ config('currency.symbol') }})</li>
            <li>Forecast Inaccuracy ({{ config('currency.symbol') }})
              <x-info-popover title="What Is Forecast Inaccuracy ?" chapter="09"
                route="{{ route('student.manual.chapter09') }}">
                Forecast errors increase overhead costs: a 10% error adds 10% to Admin O/H.
              </x-info-popover>
            </li>
            <li>Information Technology ({{ config('currency.symbol') }})</li>
            <li>Introductions ({{ config('currency.symbol') }})</li>
            <li>Inventory Charges ({{ config('currency.symbol') }})</li>
            <li>Marketing ({{ config('currency.symbol') }})</li>
            <li>Plant Capacity FC ({{ config('currency.symbol') }})
              <x-info-popover title="What Is Plant Capacity FC ?" chapter="04"
                route="{{ route('student.manual.chapter04') }}">
                Fixed costs to maintain your plant’s production ability, regardless of output level.
              </x-info-popover>
            </li>
            <li>Price Changes ({{ config('currency.symbol') }})</li>
            <li>Procurement FC ({{ config('currency.symbol') }})</li>
            <li>Production FC ({{ config('currency.symbol') }})</li>
            <li>Research Studies ({{ config('currency.symbol') }})</li>
            <li>Service Outsourcing ({{ config('currency.symbol') }})</li>
            <li>Unfilled Handling ({{ config('currency.symbol') }})</li>
            <li class="total">= Total Fixed & Other ({{ config('currency.symbol') }})</li>
            <li>Operating Income ({{ config('currency.symbol') }})</li>
            <li>Non-Operating Income ({{ config('currency.symbol') }})</li>
            <li>Taxes ({{ config('currency.symbol') }})</li>
            <li>Net Income ({{ config('currency.symbol') }})</li>
          </ul>
        </div>
      </div>
      <div class="productSummary">
        <div class="productStatement">
          <div class="productTitle">
            <small>&nbsp;</small>
            <label>All Products</label>
          </div>
          <div class="reportSection">
            <label for="">&nbsp;</label>
            <div class="contentItem">{{ number_format($productSummary->marketDemand, 0) }}</div>
          </div>
          <div class="reportSection">
            <label for="">&nbsp;</label>
            <div class="contentItem">{{ number_format($productSummary->fulfilledUnits, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->price, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->unfilledOrders, 0) }}</div>
            <div class="contentItem highlight">{{ number_format($productSummary->revenue, 0) }}</div>
          </div>

          <div class="reportSection">
            <label for="">&nbsp;</label>
            <div class="contentItem">{{ number_format($productSummary->productCost, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->productionShiftCost, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->fixedCost, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->orderProcessing, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->replacementParts, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->rfidCosts, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->transportationCost, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->transportationRebate, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->volumeDiscounts, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->dutiesTariffs, 0) }} </div>
            <div class="contentItem total">- {{ number_format($productSummary->totalExpenses, 0) }} </div>
            <div class="contentItem highlight">{{ number_format($productSummary->grossMargin, 0) }} </div>
          </div>
          <div class="reportSection">
            <label for="">&nbsp;</label>
            <div class="contentItem">{{ number_format($productSummary->administrativeOh, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->corporateOh, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->consultingFees, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->crossDocking, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->distributionFc, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->emergencyProcurement, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->emergencyProduction, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->forecastInaccuracy, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->informationTechnology, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->introductions, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->inventoryCharges, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->marketing, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->plantCapacityFc, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->priceChanges, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->procurementFc, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->productionFc, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->researchStudies, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->serviceOutsourcing, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->unfilledHandling, 0) }}</div>
            <div class="contentItem total">- {{ number_format($productSummary->totalFixedCosts, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->operatingIncome, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->nonOperatingIncome, 0) }}</div>
            <div class="contentItem">- {{ number_format($productSummary->taxes, 0) }}</div>
            <div class="contentItem">{{ number_format($productSummary->netIncome, 0) }}</div>
          </div>
        </div>

        @foreach ($productReport as $product)
          <div class="productStatement">
            <div class="productTitle">
              <small> Product {{ str_pad($product->id, '2', '0', STR_PAD_LEFT) }}</small>
              <label>{{ $product->sort_name . ' - ' . $product->name }}</label>
            </div>
            <div class="reportSection">
              <label for="">&nbsp;</label>
              <div class="contentItem">{{ number_format($product->marketDemand, 0) }}</div>
            </div>
            <div class="reportSection">
              <label for="">&nbsp;</label>
              <div class="contentItem">{{ number_format($product->fulfilledUnits, 0) }}</div>
              <div class="contentItem">{{ number_format($product->price, 0) }}</div>
              <div class="contentItem">{{ number_format($product->unfilledOrders, 0) }}</div>
              <div class="contentItem highlight">{{ number_format($product->revenue, 0) }}</div>
            </div>

            <div class="reportSection">
              <label for="">&nbsp;</label>
              <div class="contentItem">{{ number_format($product->productCost, 0) }}</div>
              <div class="contentItem">{{ number_format($product->productionShiftCost, 0) }}</div>
              <div class="contentItem">{{ number_format($product->fixedCost, 0) }}</div>
              <div class="contentItem">{{ number_format($product->orderProcessing, 0) }}</div>
              <div class="contentItem">{{ number_format($product->replacementParts, 0) }}</div>
              <div class="contentItem">{{ number_format($product->rfidCosts, 0) }}</div>
              {{-- <div class="contentItem">{{ number_format($product->transportationCost, 0) }}</div>  --}}
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">{{ number_format($product->dutiesTariffs, 0) }} </div>
              <div class="contentItem total">- {{ number_format($product->totalExpenses, 0) }}</div>
              <div class="contentItem highlight">{{ number_format($product->grossMargin, 0) }}</div>
            </div>
            <div class="reportSection">
              <label for="">&nbsp;</label>
              <div class="contentItem">{{ number_format($product->administrativeOH, 0) }}</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">{{ number_format($product->forecastInaccuracy, 0) }}</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">{{ number_format($product->marketing, 0) }}</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">{{ number_format($product->priceChanges, 0) }}</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">{{ number_format($product->serviceOutsourcing, 0) }}</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem total">- {{ number_format($product->totalFixedCosts, 0) }}</div>
              <div class="contentItem">{{ number_format($product->operatingIncome, 0) }}</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
              <div class="contentItem">&nbsp;</div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
    <div class="noteContainer">
      <p>Note: there may be rounding error sure to number of decimals.</p>
    </div>
  </div>
  @if ($reportAnalysis && $reportAnalysis->response)
    <div class="reportAnalysis">
      <div class="headingSection">
        <img src="{{ asset('images/ChatGPT-Logo.png') }}" alt="">
        <h2>Quarter {{ request('quarter_id') }} - Financial Analysis & Insights</h2>
        <p>Interpreting profitability, cost drivers, and strategic recommendations from the Corporate Profit & Loss
          Statement.</p>
      </div>
      {!! $reportAnalysis->response !!}
    </div>
  @endif
@endsection
@section('templateScript')
  <script src="https://code.highcharts.com/highcharts.js"></script>
  <script>
    Highcharts.chart('corporatePLDonut', {
      chart: {
        type: 'pie'
      },
      title: {
        text: 'Quarter {{ request('quarter_id') }} - Corporate P&L Overview'
      },
      exporting: {
        enabled: false
      },
      credits: {
        enabled: false
      },
      plotOptions: {
        pie: {
          innerSize: '50%',
          dataLabels: {
            enabled: true,
            format: '<b>{point.name}</b>: {point.y:,.0f}',
            style: {
              fontSize: '12px'
            }
          }
        }
      },
      tooltip: {
        pointFormatter: function() {
          const currencyFields = [
            'Revenue', 'Total Expenses', 'Gross Margin', 'Total Fixed Costs', 'Operating Income',
            'Non-Operating Income', 'Taxes', 'Net Income'
          ];
          if (currencyFields.includes(this.name)) {
            return `<span style="color:${this.color}">\u25CF</span> ${this.name}: <b>${this.y.toLocaleString()}</b><br/>`;
          } else {
            return `<span style="color:${this.color}">\u25CF</span> ${this.name}: <b>${this.y.toLocaleString()}</b><br/>`;
          }
        }
      },
      series: [{
        name: 'Amount',
        data: [{
            name: 'Revenue',
            y: {{ $productSummary->revenue }},
            color: '#4e79a7'
          },
          {
            name: 'Total Expenses',
            y: {{ $productSummary->totalExpenses }},
            color: '#f28e2b'
          },
          {
            name: 'Gross Margin',
            y: {{ $productSummary->grossMargin }},
            color: '#e15759'
          },
          {
            name: 'Total Fixed Costs',
            y: {{ $productSummary->totalFixedCosts }},
            color: '#76b7b2'
          },
          {
            name: 'Operating Income',
            y: {{ $productSummary->operatingIncome }},
            color: '#59a14f'
          },
          {
            name: 'Non-Operating Income',
            y: {{ $productSummary->nonOperatingIncome }},
            color: '#9b59b6'
          },
          {
            name: 'Taxes',
            y: {{ $productSummary->taxes }},
            color: '#f39c12'
          },
          {
            name: 'Net Income',
            y: {{ $productSummary->netIncome }},
            color: '#2ecc71'
          },
          {
            name: 'Sales Volume',
            y: {{ $productSummary->productSalesVolume }},
            color: '#f1c40f'
          }
        ]
      }]
    });
  </script>
@endsection
