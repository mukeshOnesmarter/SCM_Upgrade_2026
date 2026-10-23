<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndustryRequest;
use App\Http\Requests\UpdateIndustryRequest;
use App\Models\Industry;
use Illuminate\Http\Response;

class IndustryController extends Controller
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
    public function store(StoreIndustryRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return Response
     */
    public function show(Industry $industry)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit(Industry $industry)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @return Response
     */
    public function update(UpdateIndustryRequest $request, Industry $industry)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Industry $industry)
    {
        //
    }
}
