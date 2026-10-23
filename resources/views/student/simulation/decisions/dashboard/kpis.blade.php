  @extends('student.simulation.layout')
  @section('title', 'Key Performance Indicators')

  @section('template')
    <div class="decisionTemplate">
      @include('student.simulation.decisions.partials.commonHeader')
      <div class="dashboardTemplate"> 
        <div class="scorecardContainer">
          @foreach ([
            'financial' => 'Financial Metrics',
            'operational' => 'Operational Metrics',
            'customer' => 'Customer Metrics',
            'external' => 'External Metrics',
            'fi_index' => 'FI Index',
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
                  @foreach ($scorecard[$key] as $subMeasure => $score)
                    <tr>
                      <td class="text-left">{{ $subMeasure }}
                        @if (isset($infoPopovers[$subMeasure]))
                          <x-info-popover :title="$infoPopovers[$subMeasure]['title']" chapter="12" :route="route('student.manual.chapter12')">
                            {{ $infoPopovers[$subMeasure]['text'] }}
                          </x-info-popover>
                        @endif
                      </td>
                      <td class="score">{{ $score }}</td>
                      <td class="danger">
                        @php
                          $val = $benchmarks[$key][$subMeasure]['worst'] ?? '-';
                          echo is_numeric($val) ? $val : str_replace(['0$', '$0'], '$0', $val);
                        @endphp
                      </td>
                      <td>
                        @php
                          $val = $benchmarks[$key][$subMeasure]['average'] ?? '-';
                          echo is_numeric($val) ? $val : str_replace(['0$', '$0'], '$0', $val);
                        @endphp
                      </td>
                      <td style="color: #38e629 ">
                        @php
                          $val = $benchmarks[$key][$subMeasure]['industry_best'] ?? '-';
                          echo is_numeric($val) ? $val : str_replace(['0$', '$0'], '$0', $val);
                        @endphp
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endforeach
        </div>

      </div>
    </div>
  @endsection
