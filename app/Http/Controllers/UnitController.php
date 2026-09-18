<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    protected UnitService $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $units = $this->unitService->getAllUnits();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $units,
            ]);
        }

        return view('pages.units.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.units.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUnitRequest $request)
    {
        $unit = $this->unitService->createUnit($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Master satuan berhasil ditambahkan.',
                'data' => $unit,
            ], 201);
        }

        return redirect()->route('units.index')->with('success', 'Master satuan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Unit $unit)
    {
        $unit->load('products');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $unit,
            ]);
        }

        return view('pages.units.show', compact('unit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        return view('pages.units.edit', compact('unit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        $updatedUnit = $this->unitService->updateUnit($unit, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Master satuan berhasil diperbarui.',
                'data' => $updatedUnit,
            ]);
        }

        return redirect()->route('units.index')->with('success', 'Master satuan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Unit $unit)
    {
        try {
            $this->unitService->deleteUnit($unit);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Master satuan berhasil dihapus.',
                ]);
            }

            return redirect()->route('units.index')->with('success', 'Master satuan berhasil dihapus.');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('units.index')->with('error', $e->getMessage());
        }
    }
}
