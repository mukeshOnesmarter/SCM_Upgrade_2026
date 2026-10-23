<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSimulationQuarterRequest;
use App\Http\Requests\UpdateSimulationQuarterRequest;
use App\Models\SimulationQuarter;
use Illuminate\Http\Response;

class SimulationQuarterController extends Controller
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
    public function store(StoreSimulationQuarterRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(SimulationQuarter $simulationQuarter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(SimulationQuarter $simulationQuarter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateSimulationQuarterRequest $request, SimulationQuarter $simulationQuarter)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(SimulationQuarter $simulationQuarter)
    {
        //
    }
}
