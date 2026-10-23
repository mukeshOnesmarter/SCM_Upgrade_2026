@push('modalcontentContainer')
  <ul>
    <li>Choose transportation modes (air vs. surface) and carriers for plant-to-DC shipments.</li>
    <li>Surface transport is cheaper but less reliable; air transport guarantees delivery.</li>
    <li>Emergency carriers handle urgent shipments to DCs.</li>
    <li>Carrier rebates apply when exclusively using a single carrier.</li>
    <li>Customer shipping costs vary by channel, region, and transport mode.</li>
  </ul>
  <p><em>For complete details, refer to Chapter 6: Transportation Decisions in the full FLEXEE Manual.</em></p>
  <p><a href="{{ route('student.manual.chapter06') }}" target="_blank">Read Chapter 6</a></p>
@endpush

@section('decisionsModalContent')
  @include('student.simulation.decisionsModal.decisionsModal', [
      'title' => 'Transportation',
      'subTitle' => 'The Role of Transportation in Supply Chain Efficiency',
      'tagline' =>
          'Learn how effective transportation management reduces delays, lowers costs, and ensures smooth logistics.',
  ])
@endsection
