@extends('student.simulation.decisions-completed.layout')
@section('title', 'Procurement')
@section('quarterClosureInfo')
  @php
    $subAssemblyNames = [
      1 => 'Control Interface',
      2 => 'Audio Module',
      3 => 'Motherboard',
    ];

    $supplierMap = [
      1 => ['code' => 'A', 'name' => 'Global Prime Suppliers'],
      2 => ['code' => 'B', 'name' => 'Federal Suppliers'],
      3 => ['code' => 'C', 'name' => 'Precision Line Suppliers'],
      4 => ['code' => 'D', 'name' => 'Elite Tech Suppliers'],
      5 => ['code' => 'E', 'name' => 'Summit Edge Suppliers'],
      6 => ['code' => 'F', 'name' => 'Reliable Trade Suppliers'],
      7 => ['code' => 'G', 'name' => 'Visionary Suppliers'],
    ];
  @endphp

  @if (is_array($quarterClosureInfo) && isset($quarterClosureInfo['message']))
    <p>{{ $quarterClosureInfo['message'] }}</p>
  @elseif (is_array($quarterClosureInfo))
    <div class="procurementClosure">
      @foreach ($quarterClosureInfo as $regionId => $regionData)
        <div class="regionContainer">
          <h4>Region {{ $regionId }}</h4>
          @if ($regionId === 1)
            <div class="rawMaterials">
              <label for="heading">Raw Materials</label>
              @foreach ($regionData['rawMaterial'] as $rawMaterialId => $quantity)
                <div class="materialType">
                  <strong>Raw Material: {{ $rawMaterialId }}</strong>
                  <span>{{ number_format($quantity , 0)  }}</span> 
                </div> 
              @endforeach
            </div>
          @endif
          <div class="heading">Sub Assemblies</div> 
          <div class="subAssemblieContainer"> 
            @foreach ($regionData['subAssembly'] as $subAssemblyId => $suppliers)
            <div class="subAssemblies"> 
              <label>{{ $subAssemblyNames[$subAssemblyId] ?? 'Sub Assembly' }}</label>
              <div class="supplierContainer">
                @foreach ($suppliers as $supplierId => $supplyChannels)
                  <div class="supplier">
                    <label for="">Supplier ({{ $supplierMap[$supplierId]['code'] ?? '' }}) :  
                      {{ $supplierMap[$supplierId]['name'] ?? 'Supplier' }} 
                    </label>
                    <div class="supplyChannel">
                      @foreach ($supplyChannels as $supplyChannelId => $quantity)
                        <div class="channel"> 
                          <span>Supply Channel (<strong>{{ $supplyChannelId == 1 ? 'Air' : 'Surface' }}</strong>): </span>
                          <strong>{{ $quantity }}</strong> 
                        </div> 
                      @endforeach
                    </div> 
                  </div>
                @endforeach
              </div>
            </div>  
            @endforeach

          </div>
        </div>
      @endforeach
    </div>
  @endif
@endsection
@include('student.simulation.decisionsModal.procurement')
