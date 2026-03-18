<?php

namespace App\Http\Controllers;

use App\Models\Wastage;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
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

        // Create wastage record
        $validated['user_id'] = Auth::id();
        Wastage::create($validated);

        // Reduce product stock
        $product->decrement('stock_quantity', $validated['quantity']);

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

        // Calculate stock adjustment
        $oldProduct = Product::findOrFail($wastage->product_id);
        $newProduct = Product::findOrFail($validated['product_id']);

        // Restore old product stock
        $oldProduct->increment('stock_quantity', $wastage->quantity);

        // Check if new product has sufficient stock
        if ($newProduct->stock_quantity < $validated['quantity']) {
            // Revert the increment
            $oldProduct->decrement('stock_quantity', $wastage->quantity);
            return back()->withErrors([
                'quantity' => 'Insufficient stock. Available: ' . $newProduct->stock_quantity
            ]);
        }

        // Deduct from new product
        $newProduct->decrement('stock_quantity', $validated['quantity']);

        // Update wastage record
        $wastage->update($validated);

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

        // Restore product stock before deleting
        $product = Product::findOrFail($wastage->product_id);
        $product->increment('stock_quantity', $wastage->quantity);

        $wastage->delete();

        return redirect()->route('wastages.index')->banner('Wastage deleted successfully.');
    }
}
