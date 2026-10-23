<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRawMaterialRequest;
use App\Http\Requests\UpdateProductRawMaterialRequest;
use App\Models\ProductRawMaterial;
use Illuminate\Http\Response;

class ProductRawMaterialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(StoreProductRawMaterialRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(ProductRawMaterial $productRawMaterial)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(ProductRawMaterial $productRawMaterial)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateProductRawMaterialRequest $request, ProductRawMaterial $productRawMaterial)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(ProductRawMaterial $productRawMaterial)
    {
        //
    }
}
