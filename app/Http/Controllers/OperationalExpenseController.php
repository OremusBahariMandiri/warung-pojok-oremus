<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOperationalExpenseRequest;
use App\Http\Requests\UpdateOperationalExpenseRequest;
use App\Models\OperationalExpense;
use App\Services\OperationalExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OperationalExpenseController extends Controller
{
    protected OperationalExpenseService $expenseService;

    public function __construct(OperationalExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $expenses = $this->expenseService->getExpenses($startDate, $endDate);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $expenses,
            ]);
        }

        return view('pages.operational_expenses.index', compact('expenses', 'startDate', 'endDate'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.operational_expenses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOperationalExpenseRequest $request)
    {
        $userId = Auth::id() ?? 1;

        try {
            $expense = $this->expenseService->createExpense($request->validated(), $userId);
        } catch (\Exception $e) {
            return back()->withErrors(['name' => 'Gagal menyimpan: ' . $e->getMessage()])->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi operasional berhasil ditambahkan.',
                'data'    => $expense,
            ], 201);
        }

        return redirect()->route('operational-expenses.index')
            ->with('success', 'Transaksi operasional berhasil disimpan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, OperationalExpense $operationalExpense)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $operationalExpense,
            ]);
        }

        return redirect()->route('operational-expenses.edit', $operationalExpense);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OperationalExpense $operationalExpense)
    {
        return view('pages.operational_expenses.edit', compact('operationalExpense'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOperationalExpenseRequest $request, OperationalExpense $operationalExpense)
    {
        $userId = Auth::id() ?? 1;

        try {
            $updated = $this->expenseService->updateExpense($operationalExpense, $request->validated(), $userId);
        } catch (\Exception $e) {
            return back()->withErrors(['name' => 'Gagal memperbarui: ' . $e->getMessage()])->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi operasional berhasil diperbarui.',
                'data'    => $updated,
            ]);
        }

        return redirect()->route('operational-expenses.index')
            ->with('success', 'Transaksi operasional berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, OperationalExpense $operationalExpense)
    {
        $userId = Auth::id() ?? 1;

        $this->expenseService->deleteExpense($operationalExpense, $userId);

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Transaksi operasional berhasil dihapus.',
            ]);
        }

        return redirect()->route('operational-expenses.index')
            ->with('success', 'Transaksi operasional berhasil dihapus.');
    }
}
