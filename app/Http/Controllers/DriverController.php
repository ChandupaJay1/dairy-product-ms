<?php

namespace App\Http\Controllers;

use App\Core\Database;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function getActiveDispatches(): JsonResponse
    {
        $dispatches = Database::query("
            SELECT d.*, 
                   v.vehicle_number, v.vehicle_type, v.capacity_cups, v.cooling_system_status,
                   dr.name AS driver_name, dr.phone AS driver_phone,
                   COALESCE(sr.name, u.name, 'Field Sales Rep') AS sales_rep_name,
                   r.route_name, r.region_sector
            FROM dispatches d
            LEFT JOIN vehicles v ON d.vehicle_id = v.id
            LEFT JOIN drivers dr ON d.driver_id = dr.id
            LEFT JOIN users u ON d.sales_rep_id = u.id
            LEFT JOIN sales_representatives sr ON (d.sales_rep_id = sr.id OR d.sales_rep_id = sr.user_id)
            LEFT JOIN routes r ON d.route_id = r.id
            ORDER BY d.id DESC
        ");

        $allProducts = Database::query('SELECT id, flavor_name, sku_code, unit_price FROM products');
        $productsById = [];
        foreach ($allProducts as $p) {
            $pObj = (object) $p;
            $productsById[$pObj->id] = $pObj;
        }

        // Attach computed total stock value and parsed items
        foreach ($dispatches as &$disp) {
            $dispObj = (object) $disp;
            $items = ! empty($dispObj->items_json) ? json_decode($dispObj->items_json, true) : null;
            if (! $items) {
                // Synthesize from legacy columns if items_json is empty
                $items = [
                    ['flavor_name' => 'Fresh Vanilla Yogurt (80g)', 'sku_code' => 'YOG-VAN-80', 'unit_price' => 80.00, 'loaded_cups' => (int) ($dispObj->vanilla_load ?? 0), 'subtotal' => (int) ($dispObj->vanilla_load ?? 0) * 80.00],
                    ['flavor_name' => 'Sweet Strawberry Yogurt (80g)', 'sku_code' => 'YOG-STR-80', 'unit_price' => 90.00, 'loaded_cups' => (int) ($dispObj->strawberry_load ?? 0), 'subtotal' => (int) ($dispObj->strawberry_load ?? 0) * 90.00],
                    ['flavor_name' => 'Creamy Chocolate Yogurt (80g)', 'sku_code' => 'YOG-CHO-80', 'unit_price' => 95.00, 'loaded_cups' => (int) ($dispObj->chocolate_load ?? 0), 'subtotal' => (int) ($dispObj->chocolate_load ?? 0) * 95.00],
                    ['flavor_name' => 'Tropical Mango Yogurt (80g)', 'sku_code' => 'YOG-MAN-80', 'unit_price' => 95.00, 'loaded_cups' => (int) ($dispObj->mango_load ?? 0), 'subtotal' => (int) ($dispObj->mango_load ?? 0) * 95.00],
                    ['flavor_name' => 'Traditional Curd & Kithul Treacle Pot (180g)', 'sku_code' => 'YOG-TRC-180', 'unit_price' => 160.00, 'loaded_cups' => (int) ($dispObj->treacle_pot_load ?? 0), 'subtotal' => (int) ($dispObj->treacle_pot_load ?? 0) * 160.00],
                ];
            }
            $totalValue = 0;
            foreach ($items as $it) {
                $totalValue += (float) ($it['subtotal'] ?? ((float) ($it['unit_price'] ?? 0) * (int) ($it['loaded_cups'] ?? $it['quantity'] ?? 0)));
            }

            $totalLoaded = (int) ($dispObj->total_loaded_cups ?? 0);
            $remaining = (int) ($dispObj->remaining_cups ?? 0);

            if (is_array($disp)) {
                $disp['items'] = $items;
                $disp['total_stock_value'] = round($totalValue, 2);
                $disp['remaining_stock_value'] = $totalLoaded > 0
                    ? round($totalValue * ($remaining / $totalLoaded), 2)
                    : 0.00;
            } else {
                $disp->items = $items;
                $disp->total_stock_value = round($totalValue, 2);
                $disp->remaining_stock_value = $totalLoaded > 0
                    ? round($totalValue * ($remaining / $totalLoaded), 2)
                    : 0.00;
            }
        }

        return response()->json(['success' => true, 'dispatches' => $dispatches]);
    }

    public function getDispatchDetails(int $id): JsonResponse
    {
        $dispatchRaw = Database::queryOne("
            SELECT d.*, 
                   v.vehicle_number, v.vehicle_type, v.capacity_cups, v.cooling_system_status,
                   dr.name AS driver_name, dr.phone AS driver_phone,
                   COALESCE(sr.name, u.name, 'Field Sales Rep') AS sales_rep_name,
                   r.route_name, r.region_sector
            FROM dispatches d
            LEFT JOIN vehicles v ON d.vehicle_id = v.id
            LEFT JOIN drivers dr ON d.driver_id = dr.id
            LEFT JOIN users u ON d.sales_rep_id = u.id
            LEFT JOIN sales_representatives sr ON (d.sales_rep_id = sr.id OR d.sales_rep_id = sr.user_id)
            LEFT JOIN routes r ON d.route_id = r.id
            WHERE d.id = ?
        ", [$id]);

        if (! $dispatchRaw) {
            return response()->json(['success' => false, 'error' => 'Dispatch manifest not found.'], 404);
        }

        $dispatch = (object) $dispatchRaw;
        $items = ! empty($dispatch->items_json) ? json_decode($dispatch->items_json, true) : null;
        if (! $items) {
            $items = [
                ['flavor_name' => 'Fresh Vanilla Yogurt (80g)', 'sku_code' => 'YOG-VAN-80', 'unit_price' => 80.00, 'loaded_cups' => (int) ($dispatch->vanilla_load ?? 0), 'subtotal' => (int) ($dispatch->vanilla_load ?? 0) * 80.00],
                ['flavor_name' => 'Sweet Strawberry Yogurt (80g)', 'sku_code' => 'YOG-STR-80', 'unit_price' => 90.00, 'loaded_cups' => (int) ($dispatch->strawberry_load ?? 0), 'subtotal' => (int) ($dispatch->strawberry_load ?? 0) * 90.00],
                ['flavor_name' => 'Creamy Chocolate Yogurt (80g)', 'sku_code' => 'YOG-CHO-80', 'unit_price' => 95.00, 'loaded_cups' => (int) ($dispatch->chocolate_load ?? 0), 'subtotal' => (int) ($dispatch->chocolate_load ?? 0) * 95.00],
                ['flavor_name' => 'Tropical Mango Yogurt (80g)', 'sku_code' => 'YOG-MAN-80', 'unit_price' => 95.00, 'loaded_cups' => (int) ($dispatch->mango_load ?? 0), 'subtotal' => (int) ($dispatch->mango_load ?? 0) * 95.00],
                ['flavor_name' => 'Traditional Curd & Kithul Treacle Pot (180g)', 'sku_code' => 'YOG-TRC-180', 'unit_price' => 160.00, 'loaded_cups' => (int) ($dispatch->treacle_pot_load ?? 0), 'subtotal' => (int) ($dispatch->treacle_pot_load ?? 0) * 160.00],
            ];
        }
        $totalValue = 0;
        foreach ($items as $it) {
            $totalValue += (float) ($it['subtotal'] ?? ((float) ($it['unit_price'] ?? 0) * (int) ($it['loaded_cups'] ?? $it['quantity'] ?? 0)));
        }

        $totalLoaded = (int) ($dispatch->total_loaded_cups ?? 0);
        $remaining = (int) ($dispatch->remaining_cups ?? 0);

        $dispatch->items = $items;
        $dispatch->total_stock_value = round($totalValue, 2);
        $dispatch->remaining_stock_value = $totalLoaded > 0
            ? round($totalValue * ($remaining / $totalLoaded), 2)
            : 0.00;

        return response()->json(['success' => true, 'dispatch' => $dispatch]);
    }

    public function createDispatch(Request $request): JsonResponse
    {
        $input = $request->all();
        $vehicleId = (int) ($input['vehicleId'] ?? 0);
        $driverId = (int) ($input['driverId'] ?? 0);
        $routeId = (int) ($input['routeId'] ?? 0);
        $salesRepId = ! empty($input['salesRepId']) ? (int) $input['salesRepId'] : 4;

        if (! $vehicleId || ! $driverId || ! $routeId) {
            return response()->json(['success' => false, 'error' => 'කරුණාකර වාහනය, රියදුරු සහ මාර්ගය තෝරන්න (Please select vehicle, driver, and route).'], 400);
        }

        // 1. Vehicle Capacity Verification
        $vehicleRaw = Database::queryOne('SELECT * FROM vehicles WHERE id = ?', [$vehicleId]);
        if (! $vehicleRaw) {
            return response()->json(['success' => false, 'error' => 'තෝරාගත් වාහනය හමු නොවීය (Vehicle record not found).'], 404);
        }
        $vehicle = (object) $vehicleRaw;
        $vehicleCapacity = (int) ($vehicle->capacity_cups ?? 2500);

        // 2. Fetch products and build items list
        $productsRaw = Database::query('SELECT * FROM products ORDER BY id ASC');
        $productsMap = [];
        foreach ($productsRaw as $p) {
            $pObj = (object) $p;
            $productsMap[$pObj->id] = $pObj;
        }

        $processedItems = [];
        $totalLoaded = 0;
        $totalStockValue = 0;

        // Check if dynamic items array was sent
        $rawItems = $input['items'] ?? [];
        if (! empty($rawItems) && is_array($rawItems)) {
            foreach ($rawItems as $raw) {
                $pId = (int) ($raw['productId'] ?? $raw['id'] ?? 0);
                $qty = (int) ($raw['quantity'] ?? $raw['qty'] ?? 0);
                if ($qty <= 0 || ! isset($productsMap[$pId])) {
                    continue;
                }

                $product = $productsMap[$pId];
                // Check warehouse stock
                $availableStock = isset($product->stock_warehouse) ? (int) $product->stock_warehouse : 999999;
                if ($qty > $availableStock) {
                    return response()->json([
                        'success' => false,
                        'error' => "⚠️ ගබඩාවේ ප්‍රමාණවත් තොග නොමැත! ({$product->flavor_name} සඳහා ගබඩාවේ ඇත්තේ {$availableStock} cups පමණි. ඉල්ලු ප්‍රමාණය: {$qty} cups).",
                    ], 400);
                }

                $unitPrice = (float) $product->unit_price;
                $subtotal = round($qty * $unitPrice, 2);
                $totalLoaded += $qty;
                $totalStockValue += $subtotal;

                $processedItems[] = [
                    'product_id' => $product->id,
                    'flavor_name' => $product->flavor_name,
                    'category' => $product->category ?? 'Fresh Cup Yogurt',
                    'sku_code' => $product->sku_code,
                    'unit_price' => $unitPrice,
                    'loaded_cups' => $qty,
                    'subtotal' => $subtotal,
                ];
            }
        } else {
            // Legacy inputs fallback (vanillaLoad, strawberryLoad, etc.)
            $loadMap = [
                'YOG-VAN-80' => (int) ($input['vanillaLoad'] ?? 0),
                'YOG-STR-80' => (int) ($input['strawberryLoad'] ?? 0),
                'YOG-CHO-80' => (int) ($input['chocolateLoad'] ?? 0),
                'YOG-MAN-80' => (int) ($input['mangoLoad'] ?? 0),
                'YOG-TRC-180' => (int) ($input['treaclePotLoad'] ?? 0),
            ];

            foreach ($productsMap as $p) {
                $qty = $loadMap[$p->sku_code] ?? 0;
                if ($qty <= 0) {
                    continue;
                }

                $availableStock = isset($p->stock_warehouse) ? (int) $p->stock_warehouse : 999999;
                if ($qty > $availableStock) {
                    return response()->json([
                        'success' => false,
                        'error' => "⚠️ ගබඩාවේ ප්‍රමාණවත් තොග නොමැත! ({$p->flavor_name} සඳහා ගබඩාවේ ඇත්තේ {$availableStock} cups පමණි. ඉල්ලු ප්‍රමාණය: {$qty} cups).",
                    ], 400);
                }

                $unitPrice = (float) $p->unit_price;
                $subtotal = round($qty * $unitPrice, 2);
                $totalLoaded += $qty;
                $totalStockValue += $subtotal;

                $processedItems[] = [
                    'product_id' => $p->id,
                    'flavor_name' => $p->flavor_name,
                    'category' => $p->category ?? 'Fresh Cup Yogurt',
                    'sku_code' => $p->sku_code,
                    'unit_price' => $unitPrice,
                    'loaded_cups' => $qty,
                    'subtotal' => $subtotal,
                ];
            }
        }

        // 3. Validation: Total loaded must be > 0
        if ($totalLoaded <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'කරුණාකර පටවනු ලබන යෝගට් තොග ප්‍රමාණයක් ඇතුළත් කරන්න (Total loaded quantity must be greater than zero).',
            ], 400);
        }

        // 4. Validation: Cannot exceed vehicle max capacity
        if ($totalLoaded > $vehicleCapacity) {
            return response()->json([
                'success' => false,
                'error' => "⚠️ වාහනයේ උපරිම ධාරිතාව ඉක්මවා ඇත! ({$vehicle->vehicle_number} සඳහා උපරිම ධාරිතාව: {$vehicleCapacity} cups. පටවන මුළු ප්‍රමාණය: {$totalLoaded} cups). කරුණාකර තොග ප්‍රමාණය අඩු කරන්න.",
            ], 400);
        }

        // 5. Decrement warehouse stock for each product
        foreach ($processedItems as $item) {
            Database::execute('
                UPDATE products 
                SET stock_warehouse = GREATEST(0, stock_warehouse - ?) 
                WHERE id = ?
            ', [$item['loaded_cups'], $item['product_id']]);
        }

        // 6. Map to backward compatible columns
        $vanillaLoad = 0;
        $strawberryLoad = 0;
        $chocolateLoad = 0;
        $mangoLoad = 0;
        $treaclePotLoad = 0;

        foreach ($processedItems as $it) {
            if (str_contains($it['sku_code'], 'VAN')) {
                $vanillaLoad += $it['loaded_cups'];
            } elseif (str_contains($it['sku_code'], 'STR')) {
                $strawberryLoad += $it['loaded_cups'];
            } elseif (str_contains($it['sku_code'], 'CHO')) {
                $chocolateLoad += $it['loaded_cups'];
            } elseif (str_contains($it['sku_code'], 'MAN')) {
                $mangoLoad += $it['loaded_cups'];
            } elseif (str_contains($it['sku_code'], 'TRC')) {
                $treaclePotLoad += $it['loaded_cups'];
            }
        }

        $today = date('Y-m-d');
        $manifestNo = 'MNF-'.date('Ymd').'-'.rand(100, 999);

        // 7. Insert Dispatch Record
        $id = Database::execute("
            INSERT INTO dispatches (
                manifest_number, dispatch_date, vehicle_id, driver_id, sales_rep_id, route_id,
                vanilla_load, strawberry_load, chocolate_load, mango_load, treacle_pot_load,
                items_json, total_loaded_cups, remaining_cups, sold_cups, damaged_cups, status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'IN_TRANSIT', ?)
        ", [
            $manifestNo, $today, $vehicleId, $driverId, $salesRepId, $routeId,
            $vanillaLoad, $strawberryLoad, $chocolateLoad, $mangoLoad, $treaclePotLoad,
            json_encode($processedItems), $totalLoaded, $totalLoaded, $input['notes'] ?? 'Morning Cold-Chain Load Dispatch',
        ]);

        Database::execute("UPDATE vehicles SET status = 'IN_TRANSIT' WHERE id = ?", [$vehicleId]);
        Database::execute("UPDATE drivers SET status = 'ON_DELIVERY' WHERE id = ?", [$driverId]);

        return response()->json([
            'success' => true,
            'message' => "✅ Morning dispatch manifest #{$manifestNo} සාර්ථකව නිර්මාණය කරන ලදී.",
            'manifestNumber' => $manifestNo,
            'dispatchId' => $id,
            'totalLoaded' => $totalLoaded,
            'totalStockValue' => round($totalStockValue, 2),
            'vehicleCapacity' => $vehicleCapacity,
            'remainingCapacity' => max(0, $vehicleCapacity - $totalLoaded),
            'items' => $processedItems,
        ]);
    }

    public function getVehicles(): JsonResponse
    {
        $vehicles = Database::query('
            SELECT v.*, d.name AS driver_name, r.route_name 
            FROM vehicles v
            LEFT JOIN drivers d ON v.current_driver_id = d.id
            LEFT JOIN routes r ON v.current_route_id = r.id
            ORDER BY v.id ASC
        ');

        return response()->json(['success' => true, 'vehicles' => $vehicles]);
    }

    public function getDrivers(): JsonResponse
    {
        $drivers = Database::query('
            SELECT d.*, v.vehicle_number 
            FROM drivers d
            LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id
            ORDER BY d.id ASC
        ');

        return response()->json(['success' => true, 'drivers' => $drivers]);
    }

    public function getRoutes(): JsonResponse
    {
        $routes = Database::query('
            SELECT r.*, v.vehicle_number, COUNT(s.id) AS shop_count
            FROM routes r
            LEFT JOIN vehicles v ON r.assigned_vehicle_id = v.id
            LEFT JOIN shops s ON s.route_id = r.id
            GROUP BY r.id
            ORDER BY r.id ASC
        ');

        return response()->json(['success' => true, 'routes' => $routes]);
    }

    public function getProducts(): JsonResponse
    {
        $products = Database::query('SELECT * FROM products ORDER BY id ASC');

        return response()->json(['success' => true, 'products' => $products]);
    }
}
