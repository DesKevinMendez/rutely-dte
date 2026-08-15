<?php

namespace App\Http\Controllers;

use App\Http\Requests\EconomicActivities\StoreEconomicActivityRequest;
use App\Http\Requests\EconomicActivities\UpdateEconomicActivityRequest;
use App\Models\EconomicActivity;

class EconomicActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEconomicActivityRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EconomicActivity $economicActivity)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EconomicActivity $economicActivity)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEconomicActivityRequest $request, EconomicActivity $economicActivity)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EconomicActivity $economicActivity)
    {
        //
    }
}
