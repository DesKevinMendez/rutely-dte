<?php

namespace App\Http\Controllers;

use App\Http\Requests\Mh\StoreMhCertificatesRequest;
use App\Http\Requests\Mh\UpdateMhCertificatesRequest;
use App\Models\MhCertificates;

class MhCertificatesController extends Controller
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
    public function store(StoreMhCertificatesRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MhCertificates $mhCertificates)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MhCertificates $mhCertificates)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMhCertificatesRequest $request, MhCertificates $mhCertificates)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MhCertificates $mhCertificates)
    {
        //
    }
}
