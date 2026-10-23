@push('modalcontentContainer')
  <ul>
    <li>Decide on presence and type (owned or outsourced) of regional distribution centers.</li>
    <li>RFID application is required for retail channel; choose between insourced or outsourced.</li>
    <li>Cross-docking reduces inventory and operating costs at DCs.</li>
    <li>Surface shipping methods impact cost and reliability of product delivery.</li>
    <li>Each DC decision affects replacement part logistics and postponed production feasibility.</li>
  </ul>
  <p><em>For complete details, refer to Chapter 5: Distribution Decisions in the full FLEXEE Manual.</em></p>
  <p><a href="{{ route('student.manual.chapter05') }}" target="_blank">Read Chapter 5</a></p>
@endpush

@section('decisionsModalContent')
  @include('student.simulation.decisionsModal.decisionsModal', [
      'title' => 'Distribution',
      'subTitle' => 'Optimizing Distribution in Supply Chain Management',
      'tagline' => 'Discover how efficient distribution ensures timely delivery, reduces costs, and enhances customer
                satisfaction.',
  ])
@endsection
