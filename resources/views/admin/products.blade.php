@extends('admin.layout.static')
@section('title', 'Products')
@section('staticTemplate')
  <div class="pageTitle">
    <h4>Managed Products</h4>
    <p>Oversee products used in simulations. Manage product details, associated suppliers, and firm utilization for
      efficient tracking.</p>
  </div>
   
  <div class="productManagement">
    @foreach ($products as $product)
      <div class="product">
        <div class="productTitle">
          <small>PRODUCT - {{ str_pad($product->id, 2, '0', STR_PAD_LEFT) }}</small>
          <label for="heading">{{ strtoupper($product->name) }} </label>
          <span>Industry: {{ $product->industry->name }}</span>
          <p>Materials needed to assemble this product: </p>
        </div>
        @foreach ($product->materialtypes as $materialtype)
          <div class="materialtype">
            <label for="materialtype">{{ ucwords(str_replace('_', ' ', $materialtype->type)) }} </label>
            <div class="collection">
              <ul>
                @foreach ($materialtype->collections as $collection)
                  <li><i class="fa fa-angle-right" aria-hidden="true"></i> &nbsp; {{ $collection->component->name }}
                    <span>
                      {{ $materialtype->type == 'raw_material' ? str_pad($collection->quantity, 2, '0', STR_PAD_LEFT) . ' Kg' : '01 Unit' }}
                    </span>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        @endforeach
      </div>
    @endforeach
  </div>



@endsection
