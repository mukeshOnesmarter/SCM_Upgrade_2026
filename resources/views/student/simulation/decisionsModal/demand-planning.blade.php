@push('modalcontentContainer')
  <ul>
    <li>Make pricing, channel, and market entry/exit decisions each quarter.</li>
    <li>Marketing budgets influence demand growth and spillover across channels.</li>
    <li>Price changes incur both fixed and variable costs.</li>
    <li>Retail and direct channels serve overlapping but distinct customer bases.</li>
    <li>New launches are limited each quarter to manage plant capacity.</li>
  </ul>
  <p><em>For complete details, refer to Chapter 8: Demand Management Decisions in the full FLEXEE Manual.</em></p>
  <p><a href="{{ route('student.manual.chapter08') }}" target="_blank">Read Chapter 8</a></p>
@endpush

@section('decisionsModalContent')
  @include('student.simulation.decisionsModal.decisionsModal', [
      'title' => 'Demand and Planning',
      'subTitle' => 'Mastering Demand Planning for Supply Chain Success',
      'tagline' =>
          'Learn how demand planning helps balance supply and demand, minimize waste, and improve forecasting accuracy.',
  ])
@endsection
