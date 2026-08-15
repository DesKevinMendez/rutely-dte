<?php

namespace App\Http\Controllers;

use App\Http\Requests\Location\StoreMunicipalitiesRequest;
use App\Http\Requests\Location\UpdateMunicipalitiesRequest;
use App\Models\Municipalities;

class MunicipalitiesController extends Controller
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
    public function store(StoreMunicipalitiesRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Municipalities $municipalities)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Municipalities $municipalities)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMunicipalitiesRequest $request, Municipalities $municipalities)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Municipalities $municipalities)
    {
        //
    }
}
