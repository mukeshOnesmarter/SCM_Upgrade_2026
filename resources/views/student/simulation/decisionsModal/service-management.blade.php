@push('modalcontentContainer')
  <ul>
    <li>Service is outsourced to third-party call centers with defined quality guarantees.</li>
    <li>Service cost and quality vary by region and outsourcing level.</li>
    <li>Service decisions impact customer satisfaction metrics and warranty support.</li>
    <li>Outsourcing reduces overhead and supports scale with defined SLAs.</li>
    <li>Service costs appear in P&L and affect performance scores.</li>
  </ul>
  <p><em>For complete details, refer to Chapter 7: Service Decision in the full FLEXEE Manual.</em></p>
  <p><a href="{{ route('student.manual.chapter07') }}" target="_blank">Read Chapter 7</a></p>
@endpush

@section('decisionsModalContent')
  @include('student.simulation.decisionsModal.decisionsModal', [
      'title' => 'Service Management',
      'subTitle' => 'Optimizing Service Management for Supply Chain Excellence',
      'tagline' => 'Learn how effective service management ensures seamless operations, customer satisfaction, and continuous
                process improvement.',
  ])
@endsection
