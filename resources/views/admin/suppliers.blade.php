@extends('admin.layout.static')
@section('title', 'Suppliers')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Supplier Overview</h4>
    <p>Monitor and manage suppliers engaged in simulations. Track supplier contributions, linked firms, and their roles in
      various projects.</p>
  </div>
  <div class="supplierManagement">
    @foreach ($suppliers as $supplier)
      <div class="supplier">
        <small>SUPPLIER ({{ $supplier->name }})</small>
        <label for="heading">{{ $supplier->custome_name }}</label>
        <p><strong>Materials Supplied:</strong>
          @foreach ($supplier->supplierComponent as $index => $component)
            {{ ucwords($component->subAssemblyComponent->name) }}
            @if (!$loop->last)
              ,
            @endif
          @endforeach
        </p>
      </div>
    @endforeach
  </div>
@endsection
