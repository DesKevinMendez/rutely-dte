<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContingencyEvents\StoreContingencyEventRequest;
use App\Http\Requests\ContingencyEvents\UpdateContingencyEventRequest;
use App\Models\ContingencyEvent;

class ContingencyEventController extends Controller
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
    public function store(StoreContingencyEventRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ContingencyEvent $contingencyEvent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ContingencyEvent $contingencyEvent)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateContingencyEventRequest $request, ContingencyEvent $contingencyEvent)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContingencyEvent $contingencyEvent)
    {
        //
    }
}
