@extends('student.simulation.decisions-completed.layout')
@section('title', 'Distribution')
@section('quarterClosureInfo')
  @if (isset($quarterClosureInfo['message']))
    <p>{{ $quarterClosureInfo['message'] }}</p>
  @else
    <div class="distributionClosure">
      @foreach ($quarterClosureInfo as $item)
        @if ($item->is_home_region === 'yes')
          <div class="homeRegion">
            <div class="sectionHeader">
              <small class="dclable"> Distribution Center: {{ str_pad($item->dc_number, '2', '0', STR_PAD_LEFT) }} |
                {{ $item->is_home_region === 'no' ? 'Non-Home Region' : 'Home Region' }}
              </small>
              <label for="heading"> Region: ({{ $item->region->name }}) {{ $item->region->custome_name }}</label>
              <small>DC Type: {{ $item->dc_type === 'insourced' ? 'In-Source' : 'Out-Source' }}</small>
            </div>
            <div class="listInformation">
              <ul>
                <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> <span>RFID Application
                  </span>{{ ucfirst($item->rfid_application ?? 'No') }} </li>
                <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> <span>SAC Shipping
                  </span>{{ ucfirst($item->sac_shipping ?? 'No') }} </li>
              </ul>
            </div>
          </div>
        @else
          <div class="distribution">
            <div class="sectionHeader">
              <small class="dclable"> Distribution Center: {{ str_pad($item->dc_number, '2', '0', STR_PAD_LEFT) }} |
                {{ $item->is_home_region === 'no' ? 'Non-Home Region' : 'Home Region' }}
              </small>
              <label for="heading"> Region: ({{ $item->region->name }}) {{ $item->region->custome_name }}</label>
              <small>DC Type: {{ $item->dc_type === 'insourced' ? 'In-Source' : 'Out-Source' }}</small>
            </div>
            <div class="listInformation">
              <label for="emergencyCarrier">Emergency Carrier: ({{ $item->emergencyCarrier->name }}) <small>Handles
                  urgent or unplanned transportation.
                </small> </label>
              <ul>
                <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> <span>RFID Application
                  </span>{{ ucfirst($item->rfid_application ?? 'No') }} </li>
                <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> <span>FFGI Shipping
                  </span>{{ ucfirst($item->ffgi_shipping ?? 'No') }} </li>
                <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> <span>SAC Shipping
                  </span>{{ ucfirst($item->sac_shipping ?? 'No') }} </li>
              </ul>

              <label for="crossDocking">Cross Docking <small>Directly transfers incoming goods to outgoing
                  shipments.</small></label>
              <ul>
                <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> <span>Cross-Docking Carrier (K)</span>
                  {{ ucfirst($item->cross_docking_k ?? 'No') }} </li>
                <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> <span>Cross-Docking Carrier (L)</span>
                  {{ ucfirst($item->cross_docking_l ?? 'No') }} </li>
                <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> <span>Cross-Docking Carrier (M)</span>
                  {{ ucfirst($item->cross_docking_m ?? 'No') }} </li>
                <li><i class="fa fa-angle-double-right" aria-hidden="true"></i> <span>Cross-Docking Carrier (N)</span>
                  {{ ucfirst($item->cross_docking_n ?? 'No') }} </li>
              </ul>
            </div>
          </div>
        @endif
      @endforeach
    </div>
  @endif
@endsection
@include('student.simulation.decisionsModal.distribution')
