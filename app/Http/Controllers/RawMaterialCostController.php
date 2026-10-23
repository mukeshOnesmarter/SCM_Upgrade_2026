<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRawMaterialCostRequest;
use App\Http\Requests\UpdateRawMaterialCostRequest;
use App\Models\RawMaterialCost;
use Illuminate\Http\Response;

class RawMaterialCostController extends Controller
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
    public function store(StoreRawMaterialCostRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(RawMaterialCost $rawMaterialCost)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(RawMaterialCost $rawMaterialCost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateRawMaterialCostRequest $request, RawMaterialCost $rawMaterialCost)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(RawMaterialCost $rawMaterialCost)
    {
        //
    }
}
