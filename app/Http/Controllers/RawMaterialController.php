<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRawMaterialRequest;
use App\Http\Requests\UpdateRawMaterialRequest;
use App\Models\RawMaterial;
use Illuminate\Http\Response;

class RawMaterialController extends Controller
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
    public function store(StoreRawMaterialRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(RawMaterial $rawMaterial)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(RawMaterial $rawMaterial)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateRawMaterialRequest $request, RawMaterial $rawMaterial)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(RawMaterial $rawMaterial)
    {
        //
    }
}
