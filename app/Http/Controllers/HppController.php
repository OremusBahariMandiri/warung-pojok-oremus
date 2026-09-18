<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHppRequest;
use App\Http\Requests\UpdateHppRequest;
use App\Models\Hpp;
use App\Models\Unit;
use App\Services\HppService;
use Illuminate\Http\Request;

class HppController extends Controller
{
    protected HppService $hppService;

    public function __construct(HppService $hppService)
    {
        $this->hppService = $hppService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $hppList = $this->hppService->getAllHpp();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $hppList,
            ]);
        }

        return view('pages.hpp.index', compact('hppList'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $units = Unit::orderBy('unit_name', 'asc')->get();
        return view('pages.hpp.create', compact('units'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHppRequest $request)
    {
        $hpp = $this->hppService->createHpp($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Komponen HPP berhasil ditambahkan.',
                'data' => $hpp,
            ], 201);
        }

        return redirect()->route('hpp.index')->with('success', 'Komponen HPP berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Hpp $hpp)
    {
        $hpp->load('products');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $hpp,
            ]);
        }

        return view('pages.hpp.show', compact('hpp'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Hpp $hpp)
    {
        return view('pages.hpp.edit', compact('hpp'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHppRequest $request, Hpp $hpp)
    {
        $updatedHpp = $this->hppService->updateHpp($hpp, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Komponen HPP berhasil diperbarui.',
                'data' => $updatedHpp,
            ]);
        }

        return redirect()->route('hpp.index')->with('success', 'Komponen HPP berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Hpp $hpp)
    {
        $this->hppService->deleteHpp($hpp);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Komponen HPP berhasil dihapus.',
            ]);
        }

        return redirect()->route('hpp.index')->with('success', 'Komponen HPP berhasil dihapus.');
    }
}