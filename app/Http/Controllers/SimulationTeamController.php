<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSimulationTeamRequest;
use App\Http\Requests\UpdateSimulationTeamRequest;
use App\Models\SimulationTeam;
use Illuminate\Http\Response;

class SimulationTeamController extends Controller
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
    public function store(StoreSimulationTeamRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(SimulationTeam $simulationTeam)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(SimulationTeam $simulationTeam)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateSimulationTeamRequest $request, SimulationTeam $simulationTeam)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(SimulationTeam $simulationTeam)
    {
        //
    }
}
