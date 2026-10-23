<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplyChannelRequest;
use App\Http\Requests\UpdateSupplyChannelRequest;
use App\Models\SupplyChannel;
use Illuminate\Http\Response;

class SupplyChannelController extends Controller
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
    public function store(StoreSupplyChannelRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(SupplyChannel $supplyChannel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(SupplyChannel $supplyChannel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateSupplyChannelRequest $request, SupplyChannel $supplyChannel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(SupplyChannel $supplyChannel)
    {
        //
    }
}
