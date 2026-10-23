@php $pageTitle = 'Benchmark - Info Tech & Research Studies'; @endphp
@php $price = config('currency.symbol') . 1000; @endphp
@php $routeParams = ['simulation_id' => request('simulation_id'), 'quarter_id' => request('quarter_id') ]; @endphp

@extends('student.simulation.buy-reports.layout')
@section('title', $pageTitle)
@section('downloadlink', route('student.simulation.download.balance-sheet', $routeParams))
@section('reportTemplate')
  <div class="report-info">
    <h1>{{ $pageTitle }}</h1>
    <p>This research evaluates IT investments and their effectiveness across all firms. It provides comparative insights
      on
      system integration, automation, and data-driven decision-making. The report helps identify if your IT spending
      aligns
      with industry best practices.</p>
    <p>Cost: {{ $price }}</p>

    <div class="download">
      @if ($hasPurchased)
        <div>This report has already been purchased for Quarter {{ $quarter }}.</div>
      @else
        <form action="{{ route('student.simulation.buyreports.buyConsultingReport', $routeParams) }}" method="POST">
          @csrf
          <input type="hidden" name="simulation_id" value="{{ $simulation_id }}">
          <input type="hidden" name="quarter" value="{{ $quarter }}">
          <input type="hidden" name="consulting_fee_id" value="{{ $consulting_fee_id }}">
          <button class="btn btn-primary btn-sm">Buy Report</button>
        </form>
      @endif
    </div>
  </div>

  <div class="report-sample">
    <h5><img src="{{ asset('images/icon/excel.svg') }}" alt=""> Sample Output — Research Study (Benchmarking - Info Tech & Research Studies)</h5>
    <h6>Information Technology Synchronization and Research Reports</h6>
    
    <table class="table table-striped">
      <thead class="thead-light">
        <tr>
          <th>Report / Function</th>
          <th class="text-center">Firm 1</th>
          <th class="text-center">Firm 2</th>
          <th class="text-center">Firm 3</th>
          <th class="text-center">Firm 4</th>
          <th class="text-center">Firm 5</th>
          <th class="text-center">Firm 6</th>
          <th class="text-center">Firm 7</th>
          <th class="text-center">Firm 8</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>IT Synchronization With Carriers</td>
          <td class="text-center">No</td>
          <td class="text-center">No</td>
          <td class="text-center">No</td>
          <td class="text-center">Yes</td>
          <td class="text-center">No</td>
          <td class="text-center">Yes</td>
          <td class="text-center">No</td>
          <td class="text-center">No</td>
        </tr>
        <tr>
          <td>IT Synchronization With Suppliers</td>
          <td class="text-center">Yes</td>
          <td class="text-center">No</td>
          <td class="text-center">No</td>
          <td class="text-center">Yes</td>
          <td class="text-center">No</td>
          <td class="text-center">No</td>
          <td class="text-center">No</td>
          <td class="text-center">No</td>
        </tr>
        <tr>
          <td colspan="9" class="text-center">… (other firms omitted for brevity) …</td>
        </tr>
      </tbody>
    </table>
  </div>
@endsection
