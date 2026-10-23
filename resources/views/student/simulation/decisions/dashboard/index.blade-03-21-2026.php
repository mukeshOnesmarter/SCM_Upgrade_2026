  @extends('student.simulation.layout')
  @section('title', 'Dashboard')

  @section('template')
    <div class="decisionTemplate">
      @include('student.simulation.decisions.partials.commonHeader')
      @php $infoPopovers = [
            'Net Profit Margin' => [
                'title' => 'What Is Net Profit Margin?',
                'text' =>
                    'Shows how much profit your firm makes after all costs, as a percentage of revenue. Higher is better.',
            ],
            'Operating Margin' => [
                'title' => 'What Is Operating Margin?',
                'text' =>
                    'Reflects profit from core operations before taxes and interest, relative to revenue. Measures business efficiency.',
            ],
            'Inventory Turns' => [
                'title' => 'What Are Inventory Turns?',
                'text' =>
                    "Ratio of cost of goods sold to average inventory value. Higher means you're efficiently using inventory.",
            ],
            'Sales Growth' => [
                'title' => 'What Is Sales Growth?',
                'text' =>
                    'Measures how quickly your total revenue is increasing from quarter to quarter. A sign of market traction.',
            ],

            'Inventory Turnover' => [
                'title' => 'What Is Inventory Turnover?',
                'text' =>
                    'Units sold divided by average units in inventory. Higher values show efficient inventory flow.',
            ],
            'Fill Rate' => [
                'title' => 'What Is Fill Rate?',
                'text' =>
                    'Percentage of customer orders that were fully delivered. A key indicator of supply chain performance.',
            ],
            'Production Deviation' => [
                'title' => 'What Is Production Deviation?',
                'text' => 'Share of your total production that was emergency production. Lower values are better.',
            ],
            'Defect Rate' => [
                'title' => 'What Is Defect Rate?',
                'text' =>
                    'Percentage of units that needed replacement parts. Lower rates reflect better product quality.',
            ],
            'Controllable Costs to Revenue' => [
                'title' => 'What Are Controllable Costs to Revenue?',
                'text' =>
                    'Compares your core controllable costs (like emergency orders and fixed costs) to your total revenue. Lower means better cost control.',
            ],
            'Transportation Expense Per Unit' => [
                'title' => 'What Is Transportation Expense Per Unit?',
                'text' => 'Total shipping cost divided by total units sold. Reveals efficiency in logistics planning.',
            ],
            'Forecast Accuracy' => [
                'title' => 'What Is Forecast Accuracy?',
                'text' =>
                    'Measures how close your forecasts were to actual demand. Inaccurate forecasts drive cost penalties.',
            ],

            'Market Penetration Rate' => [
                'title' => 'What Is Market Penetration Rate?',
                'text' =>
                    'Measures how widely your product is adopted in the market. Higher values mean greater reach.',
            ],
            'Customer Satisfaction Index' => [
                'title' => 'What Is Customer Satisfaction Index?',
                'text' =>
                    'Composite score based on perceived product quality, service quality, and availability. Drives brand loyalty and repeat demand.',
            ],

            'Revenue Growth' => [
                'title' => 'What Is Revenue Growth?',
                'text' =>
                    "Measures how much your firm's revenue has grown compared to last quarter. A sign of momentum.",
            ],
            'Earnings Growth' => [
                'title' => 'What Is Earnings Growth?',
                'text' =>
                    'Measures the growth in net income over the previous quarter. Investors value consistent earnings.',
            ],
            'Consistency Score' => [
                'title' => 'What Is Consistency Score?',
                'text' =>
                    'Shows your rank points (0 to N−1) based on Net Income consistency versus all firms. Higher is better.',
            ],
            'FI Index' => [
                'title' => 'What Is FI Index?',
                'text'  =>
                    'Sum of Revenue Growth Score + Earnings Growth Score + Consistency Raw. Industry Avg is the mean FI across firms; Best is the top firm.',
            ],
        ];
      @endphp

      @php
        function kpi($v, $u)
        {
            if (is_null($v)) {
                return '-';
            }

            // Drop decimals if whole number, else keep 2
            $fmt = fmod($v, 1) == 0 ? number_format($v, 0) : number_format($v, 2);

            if ($u === '%') {
                return $fmt . '%';
            }
            if ($u === '$') {
                return '$' . $fmt;
            }
            if ($u === 'x') {
                return $fmt . 'x';
            }

            if ($u === 'pts') {
              return $fmt.' pts';  
            }

            return $fmt; // plain number
        }
      @endphp

      <div class="dashboardTemplate">
        @if (request('quarter_id') > 2) 
          <div class="tabs">
            <ul>
              <li><i class="fa fa-table" aria-hidden="true"></i> SCM Performance Table</li>
              <li>|</li>
              <li><a
                  href="{{ route('student.simulation.performance-trends', [
                      'simulation_id' => request('simulation_id'),
                      'quarter_id' => request('quarter_id'),
                  ]) }}"><i
                    class="fa fa-bar-chart" aria-hidden="true"></i> SCM Performance Trends</a>
              </li>
            </ul>
          </div>
        @else
          <div class="tabs">
          <ul>
            <li><i class="fa fa-table" aria-hidden="true"></i> SCM Performance Table</li> 
          </ul>
        </div>
        @endif 

        <h1> <a href="{{ route('student.simulation.download.scorecard-raw-download', [
          'simulation_id' => request('simulation_id'),
          'quarter_id' => request('quarter_id'),
        ]) }}" target="_blank">Download</a></h1>

        @php
          $previous = null;
        @endphp
        <div class="performaceScore">
          <div class="item">Weighted Score</div>
            @foreach ($overallProgress as $progress)
              @if ($progress->quarter === 0)
                  @continue
              @endif

            <div class="item">
              @php
                  $score = (float) ($progress->your_firm ?? 0);
                  $direction = null;
                  $color = 'text-secondary';

                  if (!is_null($previous)) {
                      if ($score > $previous) {
                          $direction = '↑'; // growing
                          $color = 'text-success';
                      } elseif ($score < $previous) {
                          $direction = '↓'; // declining
                          $color = 'text-danger';
                      } else {
                          $direction = '→'; // unchanged
                      }
                  }

                  $previous = $score;
              @endphp
              Qtr {{ str_pad($progress->quarter, 2, '0', STR_PAD_LEFT)  }}: 
              <span class="{{ $color }}">
                  {{ number_format($score, 2) }}%
                  @if($direction)
                      <small>{{ $direction }}</small>
                  @endif
              </span>
            </div>
          @endforeach
        </div> 

        <div class="scorecardContainer">
          @foreach ([
            'financial' => 'Financial Metrics',
            'operational' => 'Operational Metrics',
            'customer' => 'Customer Metrics',
            'external' => 'External Metrics',
            'fi_index' => 'FI Index',
            // 'summary' => 'Summary',
          ] as $key => $label)
            <div class="scorecard">
              <h4>{{ $label }}</h4>
              <table width="100%">
                <thead>
                  <tr>
                    <th class="text-left">Sub-Measure</th>
                    <th class="score">Your Firm</th>
                    <th>Industry Worst</th>
                    <th>Industry Avg</th>
                    <th>Industry Best</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($scorecard[$key] as $subMeasure => $row)
                    @php $u = $row['unit'] ?? null; @endphp

                    @if ($subMeasure === 'Overall Performance')
                       <p style="margin: 0; padding: 0.5rem 0.25rem; font-size: 1rem">
                        <strong>Overall Performance:</strong> {{ kpi($row['value'], $u) }}
                      </p>
                    @else 
                      <tr>
                        <td class="text-left">
                          {{ $subMeasure }} 
                          @if (isset($infoPopovers[$subMeasure]))
                            <x-info-popover :title="$infoPopovers[$subMeasure]['title']" chapter="12" :route="route('student.manual.chapter12')">
                              {{ $infoPopovers[$subMeasure]['text'] }}
                            </x-info-popover>
                          @endif
                        </td>
                        <td class="score">{{ kpi($row['value'], $u) }}</td>
                        <td class="danger">{{ kpi($benchmarks[$key][$subMeasure]['worst'] ?? null, $u) }}</td>
                        <td>{{ kpi($benchmarks[$key][$subMeasure]['average'] ?? null, $u) }}</td>
                        <td style="color:#38e629">{{ kpi($benchmarks[$key][$subMeasure]['industry_best'] ?? null, $u) }}
                        </td>
                      </tr>
                    @endif
                  @endforeach
                </tbody>
              </table>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  @endsection
