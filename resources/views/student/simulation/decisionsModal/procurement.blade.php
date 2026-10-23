@push('modalcontentContainer')
  <ul>
    <li>Procurement involves selecting suppliers for raw materials and sub-assemblies.</li>
    <li>Supplier decisions affect cost, delivery reliability, and failure rates.</li>
    <li>Emergency procurement is automatically triggered if inventory is insufficient.</li>
    <li>Volume discounts and transportation choices impact cost structure.</li>
    <li>Relationship management affects access to supply during constraints.</li>
  </ul>
  <p><em>For complete details, refer to Chapter 3: Procurement Decisions in the full FLEXEE Manual.</em></p>
  <p><a href="{{ route('student.manual.chapter03') }}" target="_blank">Read Chapter 3</a></p>
@endpush

@section('decisionsModalContent')
  @include('student.simulation.decisionsModal.decisionsModal', [
      'title' => 'Procurement',
      'subTitle' => 'Strategic Procurement for a Resilient Supply Chain',
      'tagline' =>
          'Understand how procurement decisions influence supplier relationships, cost savings, and supply continuity.',
  ])
@endsection
