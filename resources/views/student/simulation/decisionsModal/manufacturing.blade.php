@push('modalcontentContainer')
  <ul>
    <li>Manufacturing includes planned production, postponed production, and emergency production.</li>
    <li>Postponed production creates a semi-finished product that is finalized at the distribution center.</li>
    <li>Production volume flexibility can be purchased to handle demand variability.</li>
    <li>Emergency production ensures availability but incurs high costs.</li>
    <li>Capacity constraints and cost management are key to success.</li>
  </ul>
  <p><em>For complete details, refer to Chapter 4: Manufacturing Decisions in the full FLEXEE Manual.</em></p>
  <p><a href="{{ route('student.manual.chapter04') }}" target="_blank">Read Chapter 4</a></p>
@endpush

@section('decisionsModalContent')
  @include('student.simulation.decisionsModal.decisionsModal', [
      'title' => 'Manufacturing',
      'subTitle' => 'Efficient Manufacturing in Supply Chain Operations',
      'tagline' =>
          'Explore how manufacturing strategies impact production efficiency, cost reduction, and supply chain agility.',
  ])
@endsection
