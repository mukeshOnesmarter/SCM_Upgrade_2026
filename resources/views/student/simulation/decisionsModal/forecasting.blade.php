@push('modalcontentContainer')
  <ul>
    <li>Quarterly sales forecasts are required for each product, region, and channel.</li>
    <li>Forecasting accuracy directly affects administrative overhead: 1% inaccuracy = 1% cost increase.</li>
    <li>Maximum penalty: overhead can double with poor forecasting.</li>
    <li>Forecast error costs are recorded in the P&L as 'Forecast Inaccuracy'.</li>
    <li>Accurate forecasting improves operational efficiency and lowers inventory imbalance risk.</li>
    <li>Forecasting formula: Accuracy = 100 × (1 - |Forecast - Actual| / Actual)</li>
    <li>Forecasting affects both direct cost penalties and long-term performance evaluation scores.</li> 
  </ul>
  <p><em>For complete details, refer to Chapter 9: Forecasting Decisions in the full FLEXEE Manual.</em></p>
  <p><a href="{{ route('student.manual.chapter09') }}" target="_blank">Read Chapter 9</a></p>
@endpush

@section('decisionsModalContent')
  @include('student.simulation.decisionsModal.decisionsModal', [
      'title' => 'Forecasting',
      'subTitle' => 'Understanding Forecasting in Supply Chain Management',
      'tagline' => 'Learn how forecasting helps predict demand, optimize inventory, and improve decision-making in the supply
                chain.',
  ])
@endsection
