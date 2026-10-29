<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\Database;

/**
 * Shop Model (Eloquent)
 */
class Shop extends Model
{
    protected $table = 'shops';
    public $timestamps = false;
    protected $guarded = [];

    public static function allShops(): array
    {
        return Database::query("
            SELECT s.*, r.route_name, r.region_sector,
                   (s.credit_limit - s.outstanding_balance) AS available_credit
            FROM shops s
            LEFT JOIN routes r ON s.route_id = r.id
            ORDER BY s.id ASC
        ");
    }

    public static function findRecord(int $id): ?array
    {
        return Database::queryOne("SELECT * FROM shops WHERE id = ?", [$id]);
    }

    public static function getAvailableCredit(array $shop): float
    {
        return max(0.0, (float)$shop['credit_limit'] - (float)$shop['outstanding_balance']);
    }

    public static function isCashOnly(array $shop): bool
    {
        return ($shop['credit_tier'] === 'Cash Only');
    }

    public static function isOverdueBlocked(array $shop): bool
    {
        return ((int)$shop['overdue_days'] > 14);
    }

    public static function updateBalance(int $shopId, float $newBalance, int $newOverdue, int $newRating): void
    {
        Database::execute(
            "UPDATE shops SET outstanding_balance = ?, overdue_days = ?, payment_rating_percent = ? WHERE id = ?",
            [$newBalance, $newOverdue, $newRating, $shopId]
        );
    }

    public static function addCreditDebt(int $shopId, float $amount): void
    {
        Database::execute("UPDATE shops SET outstanding_balance = outstanding_balance + ? WHERE id = ?", [$amount, $shopId]);
    }
}
