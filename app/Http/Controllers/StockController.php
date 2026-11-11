<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockController extends Controller
{
    /**
     * Display the stocks inventory dashboard with inbound batches.
     */
    public function index(): View
    {
        $products = Product::with('category')->orderBy('name')->get();
        $stocks = Stock::with(['product.category', 'adjustments'])->latest('id')->get();
        $hasProducts = Product::exists();

        // Calculate summary statistics
        $totalUnits = (int) $stocks->sum('quantity');
        $totalBatches = $stocks->count();
        $lowStockThreshold = 50;

        $now = now()->startOfDay();
        $sevenDaysLater = now()->addDays(7)->endOfDay();

        // Count of batches expiring within next 7 days
        $expiringCount = $stocks->filter(function ($stock) use ($now, $sevenDaysLater) {
            if (! $stock->expiry_date) {
                return false;
            }
            $exp = Carbon::parse($stock->expiry_date);

            return $exp->greaterThanOrEqualTo($now) && $exp->lessThanOrEqualTo($sevenDaysLater);
        })->count();

        // Count of low stock batches (quantity > 0 and <= threshold)
        $lowStockCount = $stocks->filter(function ($stock) use ($lowStockThreshold) {
            return $stock->quantity > 0 && $stock->quantity <= $lowStockThreshold;
        })->count();

        // Out of stock batches (quantity <= 0)
        $outOfStockCount = $stocks->filter(function ($stock) {
            return $stock->quantity <= 0;
        })->count();

        // In stock batches (healthy buffer)
        $inStockCount = $stocks->filter(function ($stock) use ($lowStockThreshold, $now, $sevenDaysLater) {
            if ($stock->quantity <= $lowStockThreshold) {
                return false;
            }
            if ($stock->expiry_date) {
                $exp = Carbon::parse($stock->expiry_date);
                if ($exp->greaterThanOrEqualTo($now) && $exp->lessThanOrEqualTo($sevenDaysLater)) {
                    return false;
                }
            }

            return true;
        })->count();

        return view('stocks.index', compact(
            'products',
            'stocks',
            'hasProducts',
            'totalUnits',
            'totalBatches',
            'lowStockThreshold',
            'lowStockCount',
            'expiringCount',
            'inStockCount',
            'outOfStockCount'
        ));
    }

    /**
     * Store a newly received stock shipment in storage.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Support common aliases for flexible input payloads
        if ($request->has('batch') && ! $request->has('batch_number')) {
            $request->merge(['batch_number' => $request->input('batch')]);
        }
        if ($request->has('qty') && ! $request->has('received_quantity')) {
            $request->merge(['received_quantity' => $request->input('qty')]);
        }
        if ($request->has('quantity') && ! $request->has('received_quantity')) {
            $request->merge(['received_quantity' => $request->input('quantity')]);
        }
        if ($request->has('received_quantity') && ! $request->has('quantity')) {
            $request->merge(['quantity' => $request->input('received_quantity')]);
        }

        $validated = $request->validate([
            'product_id' => 'required|bail|exists:products,id',
            'batch_number' => 'required|bail|string|max:100',
            'received_quantity' => 'required|bail|numeric|min:1',
            'manufacture_date' => 'required|bail|date',
            'expiry_date' => 'required|bail|date|after:manufacture_date',
            'issued_date' => 'required|bail|date',
        ], [
            'expiry_date.after' => 'The expiry date must be a date after the manufacture date.',
            'product_id.required' => 'Please select a dairy product.',
            'product_id.exists' => 'The selected dairy product does not exist.',
            'received_quantity.required' => 'The received quantity field is required.',
            'received_quantity.min' => 'The received quantity must be at least 1.',
        ], [
            'product_id' => 'target dairy product',
            'batch_number' => 'batch number',
            'received_quantity' => 'received quantity',
            'expiry_date' => 'expiry date',
            'manufacture_date' => 'manufacture date',
            'issued_date' => 'issued date',
        ]);

        $stock = Stock::create([
            'product_id' => $validated['product_id'],
            'batch_number' => $validated['batch_number'],
            'received_quantity' => (int) $validated['received_quantity'],
            'quantity' => (int) $validated['received_quantity'],
            'expiry_date' => $validated['expiry_date'],
            'manufacture_date' => $validated['manufacture_date'],
            'issued_date' => $validated['issued_date'],
        ]);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            session()->flash('success', 'Stock received successfully');

            return response()->json([
                'success' => true,
                'message' => 'Stock received successfully',
                'stock' => $stock->load('product'),
                'redirect' => route('stocks.index'),
            ], 201);
        }

        return redirect()->route('stocks.index')
            ->with('success', 'Stock received successfully');
    }

    /**
     * Record a manual stock inventory adjustment and log audit trail.
     */
    public function adjust(Request $request, Stock $stock): JsonResponse
    {
        $validated = $request->validate([
            'action_type' => [
                'required',
                'bail',
                'string',
                Rule::in([
                    'add',
                    'deduct',
                    'spoilage',
                    'Audit Correction (+ Add Units)',
                    'Audit Correction (- Deduct Units)',
                    'Spoilage / Damaged Goods',
                ]),
            ],
            'quantity' => 'required|bail|integer|min:1',
            'authorized_by' => 'required|bail|string',
            'reason' => 'required|bail|string|max:255',
        ], [
            'action_type.required' => 'The action type field is required.',
            'action_type.in' => 'The selected action type is invalid.',
            'quantity.required' => 'The quantity field is required.',
            'quantity.integer' => 'The quantity must be an integer.',
            'quantity.min' => 'The quantity must be at least 1.',
            'authorized_by.required' => 'The authorized by field is required.',
            'reason.required' => 'The reason / memo notes field is required.',
            'reason.max' => 'The reason / memo notes may not be greater than 255 characters.',
        ], [
            'action_type' => 'action type',
            'quantity' => 'units quantity',
            'authorized_by' => 'authorized by',
            'reason' => 'reason / memo notes',
        ]);

        $rawAction = strtolower(trim($validated['action_type']));
        $qty = (int) $validated['quantity'];
        $previousQuantity = (int) $stock->quantity;

        // Determine if deduction or addition
        $isDeduction = str_contains($rawAction, 'deduct') || str_contains($rawAction, 'spoilage');

        if ($isDeduction && $qty > $previousQuantity) {
            throw ValidationException::withMessages([
                'quantity' => ["Deduction quantity ({$qty}) cannot exceed current available stock ({$previousQuantity} units)."],
            ]);
        }

        $newQuantity = $isDeduction
            ? max(0, $previousQuantity - $qty)
            : ($previousQuantity + $qty);

        $normalizedAction = match (true) {
            str_contains($rawAction, 'spoilage') => 'spoilage',
            str_contains($rawAction, 'deduct') => 'deduct',
            default => 'add',
        };

        $adjustment = DB::transaction(function () use ($stock, $normalizedAction, $qty, $previousQuantity, $newQuantity, $validated) {
            $stock->quantity = $newQuantity;
            if ($newQuantity > (int) $stock->received_quantity) {
                $stock->received_quantity = $newQuantity;
            }
            $stock->save();

            return StockAdjustment::create([
                'stock_id' => $stock->id,
                'action_type' => $normalizedAction,
                'quantity' => $qty,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'authorized_by' => $validated['authorized_by'],
                'reason' => $validated['reason'],
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock inventory adjusted successfully.',
            'stock' => $stock->fresh(['product.category', 'adjustments']),
            'adjustment' => $adjustment,
        ]);
    }

    /**
     * Remove the specified stock batch from storage.
     */
    public function destroy(Stock $stock): JsonResponse|RedirectResponse
    {
        $batchNumber = $stock->batch_number;
        $stock->delete();

        if (request()->expectsJson() || request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock deleted successfully',
                'batch_number' => $batchNumber,
            ]);
        }

        return redirect()->route('stocks.index')
            ->with('success', 'Stock deleted successfully');
    }
}
