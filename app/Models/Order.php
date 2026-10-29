<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\Database;

/**
 * Order Model (Eloquent)
 */
class Order extends Model
{
    protected $table = 'orders';
    public $timestamps = false;
    protected $guarded = [];

    public static function allOrders(): array
    {
        return Database::query("
            SELECT o.*, s.name AS shop_name, s.address AS shop_address, d.manifest_number
            FROM orders o
            JOIN shops s ON o.shop_id = s.id
            JOIN dispatches d ON o.dispatch_id = d.id
            ORDER BY o.id DESC LIMIT 50
        ");
    }

    public static function findRecord(int $id): ?array
    {
        return Database::queryOne("SELECT * FROM orders WHERE id = ?", [$id]);
    }

    public static function createRecord(array $data): int
    {
        return Database::execute("
            INSERT INTO orders (
                invoice_number, dispatch_id, shop_id, sales_rep_id,
                payment_type, total_amount, items_json,
                digital_signature_hash, signature_svg_or_base64,
                otp_verified, otp_reference_code, security_tamper_seal,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['invoiceNumber'], $data['dispatchId'], $data['shopId'], $data['salesRepId'] ?? 4,
            $data['paymentType'], $data['totalAmount'], json_encode($data['items']),
            $data['signatureHash'] ?? null, $data['signatureImage'] ?? null,
            !empty($data['otpVerified']) ? 1 : 0, $data['otpReferenceCode'] ?? null, $data['securityTamperSeal'],
            $data['status'] ?? 'CONFIRMED'
        ]);
    }
}
