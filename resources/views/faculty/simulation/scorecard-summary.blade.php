@extends('faculty.simulation.layout')
@section('title', 'Firm Performance')
@section('sectionTitle', 'Firm Performance')
@section('sectionDescription',
  'This graph shows a detailed comparison of all firms in the simulation, highlighting key
  metrics and performance trends across teams.')
@section('inheritTemplate')
  <div class="firmPerformances">
    @php
    function safe($v) {
        return is_null($v) ? '-' : max(0, $v);
    }
    @endphp

    @if (empty($quarterWiseData) || count($quarterWiseData) === 0)
      <div class="alert alert-info">
        No completed quarter data available yet.
      </div>
    @else
      @foreach ($quarterWiseData as $quarterData)
        @if ((int) $quarterData['quarter'] === 0)
          @continue
        @endif
        <div class="performancesTable">
          <h4>Firm Performance – Quarter {{ $quarterData['quarter'] }} </h4> 
          @php
            $firms = array_values($quarterData['firms']);
            $totalFirms = count($firms);
          @endphp
          <table class="table table-bordered text-center align-middle">
            <thead>
              <tr>
                <th style="width: 160px;">Firm(s)</th>
                <th>Financial</th>
                <th>Operational</th>
                <th>Customer</th>
                <th>External</th>
                <th>Weighted Score</th>
              </tr>
            </thead>

            <tbody>
              @foreach ($quarterData['firms'] as $firm)
                <tr>
                  <td>
                    <strong>{{ 'Firm ' . $firm['name'] }}</strong>
                  </td>

                  <td @if ($quarterData['winners']['financial'] === $firm['name']) class="fw-bold" @endif>
                    {{ $firm['financial'] }}
                  </td>

                  <td @if ($quarterData['winners']['operational'] === $firm['name']) class="fw-bold" @endif>
                    {{ $firm['operational'] }}
                  </td>

                  <td @if ($quarterData['winners']['customer'] === $firm['name']) class="fw-bold" @endif>
                    {{ $firm['customer'] }}
                  </td>

                  <td @if ($quarterData['winners']['external'] === $firm['name']) class="fw-bold" @endif>
                    {{ safe($firm['external']) }}
                  </td>

                  <td @if ($quarterData['winners']['weighted'] === $firm['name']) class="fw-bold" @endif>
                    <strong>{{ $firm['weighted'] }}</strong>
                  </td>
                </tr>
              @endforeach

              @php
                $firmNameMap = [];

                foreach ($firms as $index => $f) {
                    if ($index === $totalFirms - 2) {
                        $firmNameMap[$f['name']] = 'Firm ' . $f['name'];
                    } elseif ($index === $totalFirms - 1) {
                        $firmNameMap[$f['name']] = 'Firm ' . $f['name'];
                    } else {
                        $firmNameMap[$f['name']] = 'Firm ' . $f['name'];
                    }
                }
              @endphp

              {{-- Winner row --}}
              <tr class="winner">
                <td>Winner</td>
                <td>{{ $firmNameMap[$quarterData['winners']['financial']] ?? '-' }}</td>
                <td>{{ $firmNameMap[$quarterData['winners']['operational']] ?? '-' }}</td>
                <td>{{ $firmNameMap[$quarterData['winners']['customer']] ?? '-' }}</td>
                <td>{{ $firmNameMap[$quarterData['winners']['external']] ?? '-' }}</td>
                <td>{{ $firmNameMap[$quarterData['winners']['weighted']] ?? '-' }}</td>
              </tr>
              <tr>
                <td colspan="6">&nbsp;</td>
              </tr>
            </tbody>
          </table>
        </div>
      @endforeach
    @endif
  </div>
@endsection
