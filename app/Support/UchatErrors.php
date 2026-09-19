<?php

namespace App\Support;

class UchatErrors
{
    /** @var array<string, string> */
    public const REASONS = [
        'A guardian account is required for a minor profile.' => 'guardian_account_required',
        'Verify the guardian phone before using wallets.' => 'guardian_phone_unverified',
        'This minor profile is not allowed to place orders.' => 'minor_account_inactive',
        'This account is not awaiting activation.' => 'minor_activation_unavailable',
        'Minor accounts are currently disabled.' => 'minor_accounts_disabled',
        'The selected minor profile is not owned by this customer.' => 'minor_profile_unavailable',
        'The minor profile was not found.' => 'minor_profile_unavailable',
        'The selected family member was not found.' => 'family_member_unavailable',
        'This family member already has a minor account.' => 'minor_account_exists',
        'A minor account can only be created for someone under 18.' => 'minor_age_invalid',
        'Select a child account to view its wallet.' => 'minor_profile_required',
        'Select a child account before adding wallet funds.' => 'minor_profile_required',
        'The verification code is incorrect.' => 'verification_code_incorrect',
        'The verification code has expired.' => 'verification_code_expired',
        'Request a new verification code.' => 'verification_code_required',
        'This verification is no longer available.' => 'verification_unavailable',
        'Wait before requesting another verification code.' => 'verification_cooldown',
        'Verification delivery is not configured.' => 'verification_not_configured',
        'The wallet balance is insufficient.' => 'wallet_balance_insufficient',
        'The wallet is not available.' => 'wallet_unavailable',
        'Wallet spending is not enabled for this child account.' => 'wallet_spending_disabled',
        'Verify the guardian phone before using the wallet.' => 'guardian_phone_unverified',
        'Wallet payments are currently unavailable.' => 'wallet_unavailable',
        'This idempotency key belongs to another order.' => 'idempotency_conflict',
        'This operation key belongs to another top-up.' => 'idempotency_conflict',
        'This top-up has already been paid and is awaiting wallet credit.' => 'top_up_credit_pending',
        'This top-up is no longer available for payment.' => 'top_up_not_payable',
        'The wallet top-up amount is below the minimum.' => 'top_up_below_minimum',
        'The wallet top-up amount exceeds the maximum.' => 'top_up_above_maximum',
        'The order was not found for this phone number.' => 'order_not_found',
        'This order is not awaiting payment.' => 'order_not_payable',
        'The payment window for this order has expired.' => 'order_payment_expired',
        'The inventory reservation is no longer available.' => 'order_reservation_expired',
    ];

    public static function translate(string $message): string
    {
        return (string) __('uchat.'.$message) === 'uchat.'.$message ? $message : (string) __('uchat.'.$message);
    }
}
