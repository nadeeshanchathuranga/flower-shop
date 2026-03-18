<?php

namespace App\Http\Controllers;

use App\Models\Wastage;
use App\Models\Product;
use App\Models\StockTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WastageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager'])) {
            abort(403, 'Unauthorized');
        }

        $allwastages = Wastage::with(['product', 'user'])
            ->orderBy('wastage_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalWastages = $allwastages->count();

        $products = Product::with(['category', 'color', 'size'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Wastages/Index', [
            'allwastages' => $allwastages,
            'totalWastages' => $totalWastages,
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager'])) {
            abort(403, 'Unauthorized');
        }

        $products = Product::with(['category', 'color', 'size'])
            ->where('stock_quantity', '>', 0)
            ->orderBy('name')
            ->get();

        return Inertia::render('Wastages/Create', [
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:1000',
            'wastage_date' => 'required|date',
        ]);

        // Check if sufficient stock is available
        $product = Product::findOrFail($validated['product_id']);
        if ($product->stock_quantity < $validated['quantity']) {
            return back()->withErrors([
                'quantity' => 'Insufficient stock. Available: ' . $product->stock_quantity
            ]);
        }

        DB::transaction(function () use ($validated, $product) {
            $validated['user_id'] = Auth::id();
            $wastage = Wastage::create($validated);

            $product->decrement('stock_quantity', $validated['quantity']);

            $stockTransaction = StockTransaction::create([
                'product_id' => $product->id,
                'transaction_type' => 'Deducted',
                'quantity' => $validated['quantity'],
                'transaction_date' => $validated['wastage_date'],
                'supplier_id' => $product->supplier_id ?? null,
                'reason' => $validated['reason'] ?? 'Wastage',
            ]);

            $wastage->update([
                'stock_transaction_id' => $stockTransaction->id,
            ]);
        });

        return redirect()->route('wastages.index')->banner('Wastage recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Wastage $wastage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Wastage $wastage)
    {
        if (!Gate::allows('hasRole', ['Admin', 'Manager'])) {
            abort(403, 'Unauthorized');
        }

        $products = Product::with(['category', 'color', 'size'])
            ->orderBy('name')
            ->get();

        $wastage->load(['product', 'user']);

        return Inertia::render('Wastages/Edit', [
            'wastage' => $wastage,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Wastage $wastage)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:1000',
            'wastage_date' => 'required|date',
        ]);

        $oldProduct = Product::findOrFail($wastage->product_id);
        $newProduct = Product::findOrFail($validated['product_id']);

        $availableForNew = $newProduct->id === $oldProduct->id
            ? $newProduct->stock_quantity + $wastage->quantity
            : $newProduct->stock_quantity;

        if ($availableForNew < $validated['quantity']) {
            return back()->withErrors([
                'quantity' => 'Insufficient stock. Available: ' . $availableForNew,
            ]);
        }

        DB::transaction(function () use ($wastage, $validated, $oldProduct, $newProduct) {
            $oldProduct->increment('stock_quantity', $wastage->quantity);

            $newProduct->decrement('stock_quantity', $validated['quantity']);

            $wastage->update($validated);

            if ($wastage->stock_transaction_id) {
                $wastage->stockTransaction()->update([
                    'product_id' => $newProduct->id,
                    'transaction_type' => 'Deducted',
                    'quantity' => $validated['quantity'],
                    'transaction_date' => $validated['wastage_date'],
                    'supplier_id' => $newProduct->supplier_id ?? null,
                    'reason' => $validated['reason'] ?? 'Wastage',
                ]);
            } else {
                $stockTransaction = StockTransaction::create([
                    'product_id' => $newProduct->id,
                    'transaction_type' => 'Deducted',
                    'quantity' => $validated['quantity'],
                    'transaction_date' => $validated['wastage_date'],
                    'supplier_id' => $newProduct->supplier_id ?? null,
                    'reason' => $validated['reason'] ?? 'Wastage',
                ]);

                $wastage->update([
                    'stock_transaction_id' => $stockTransaction->id,
                ]);
            }
        });

        return redirect()->route('wastages.index')->banner('Wastage updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Wastage $wastage)
    {
        if (!Gate::allows('hasRole', ['Admin'])) {
            abort(403, 'Unauthorized');
        }

        DB::transaction(function () use ($wastage) {
            $product = Product::findOrFail($wastage->product_id);
            $product->increment('stock_quantity', $wastage->quantity);

            if ($wastage->stock_transaction_id) {
                $wastage->stockTransaction()->delete();
            }

            $wastage->delete();
        });

        return redirect()->route('wastages.index')->banner('Wastage deleted successfully.');
    }
}
