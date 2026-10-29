<?php

namespace App\Services;

use App\Core\Database;

/**
 * Smart Credit Tiering Engine (Business Logic Service)
 * 
 * Implements Section 3 of the proposal document:
 * Automatically manages shop credit limits based on payment history and risk tiering.
 */
class SmartCreditService
{
    /**
     * Evaluates whether a shop is eligible for a requested credit order amount.
     */
    public function evaluateCreditEligibility(int $shopId, float $requestedAmount): array
    {
        $shop = Database::queryOne("SELECT * FROM shops WHERE id = ?", [$shopId]);
        if (!$shop) {
            return [
                'allowed' => false,
                'reason' => 'Retail shop record not found in system.'
            ];
        }

        // 1. Check Cash Only policy
        if ($shop['credit_tier'] === 'Cash Only') {
            return [
                'allowed' => false,
                'reason' => 'Shop has a strict "Cash Only" policy. Credit orders are disabled.',
                'tier' => $shop['credit_tier'],
                'availableCredit' => 0.0
            ];
        }

        // 2. Check Overdue Days threshold
        if ((int)$shop['overdue_days'] > 14) {
            return [
                'allowed' => false,
                'reason' => "Credit frozen: Payments are overdue by {$shop['overdue_days']} days.",
                'tier' => $shop['credit_tier'],
                'availableCredit' => 0.0
            ];
        }

        // 3. Check Available Credit Ceiling
        $creditLimit = (float)$shop['credit_limit'];
        $currentBalance = (float)$shop['outstanding_balance'];
        $availableCredit = max(0.0, $creditLimit - $currentBalance);

        if ($requestedAmount > $availableCredit) {
            return [
                'allowed' => false,
                'reason' => "Credit limit exceeded! Order amount Rs. {$requestedAmount} exceeds available credit Rs. " . number_format($availableCredit, 2),
                'tier' => $shop['credit_tier'],
                'creditLimit' => $creditLimit,
                'currentBalance' => $currentBalance,
                'availableCredit' => $availableCredit
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'Credit approved within allowed tier limit.',
            'tier' => $shop['credit_tier'],
            'creditLimit' => $creditLimit,
            'currentBalance' => $currentBalance,
            'availableCredit' => $availableCredit
        ];
    }

    /**
     * Records a payment against outstanding credit balance and restores credit limit.
     */
    public function recordCustomerPayment(int $shopId, float $paymentAmount): array
    {
        $shop = Database::queryOne("SELECT * FROM shops WHERE id = ?", [$shopId]);
        if (!$shop) {
            throw new \Exception('Shop not found.');
        }

        $currentBalance = (float)$shop['outstanding_balance'];
        $newBalance = max(0.0, $currentBalance - $paymentAmount);
        $newOverdue = ($newBalance === 0.0) ? 0 : max(0, (int)$shop['overdue_days'] - 5);
        $newRating = min(100, (int)$shop['payment_rating_percent'] + 2);

        Database::execute(
            "UPDATE shops SET outstanding_balance = ?, overdue_days = ?, payment_rating_percent = ? WHERE id = ?",
            [$newBalance, $newOverdue, $newRating, $shopId]
        );

        return [
            'previousBalance' => $currentBalance,
            'paymentAmount' => $paymentAmount,
            'newBalance' => $newBalance
        ];
    }
}
