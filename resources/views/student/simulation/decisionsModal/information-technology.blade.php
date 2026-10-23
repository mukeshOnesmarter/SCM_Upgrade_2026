@push('modalcontentContainer')
  <ul>
    <li>Synchronize IT systems with suppliers and carriers for improved performance.</li>
    <li>IT sync improves delivery reliability and reduces component failure rates.</li>
    <li>Setup and maintenance costs apply for each synchronization relationship.</li>
    <li>Integration enhances forecasting, inventory visibility, and responsiveness.</li>
    <li>IT decisions contribute to performance metrics and customer satisfaction.</li>
  </ul>
  <p><em>For complete details, refer to Chapter 10: Enterprise Integration in the full FLEXEE Manual.</em></p>
  <p><a href="{{ route('student.manual.chapter10') }}" target="_blank">Read Chapter 10</a></p>
@endpush
@section('decisionsModalContent')
  @include('student.simulation.decisionsModal.decisionsModal', [
      'title' => 'Enterprise Integration',
      'subTitle' => 'Seamless IT Synchronization in Supply Chain',
      'tagline' =>
          'Discover how integrating IT systems enhances data accuracy, real-time tracking, and overall supply chain efficiency.',
  ])
@endsection
