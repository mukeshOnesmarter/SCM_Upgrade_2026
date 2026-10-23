@extends('admin.layout.static')
@section('title', 'Regions')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Regional Insights</h4>
    <p>Access and analyze regional data related to simulations. View region-specific performance, faculty distribution,
      and simulation activity.</p>
  </div>
  <div class="regionalInsights">
    @foreach ($regions as $region)
      <div class="tableRow">
        <div class="item">
          {{ $region->custome_name }}
        </div>
      </div>
    @endforeach
  </div>
@endsection
