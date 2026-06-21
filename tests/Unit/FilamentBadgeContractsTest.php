<?php

use App\Enums\BookingInstallmentState;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\LedgerAccountType;
use App\Enums\PaymentProvider;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\EventContract;
use App\States\Booking\Approved;
use App\States\Booking\BookingState;
use App\States\Booking\Cancelled;
use App\States\Booking\PendingReview;
use App\States\Booking\Rejected;
use App\States\Contract\AwaitingSignature;
use App\States\Contract\ContractState;
use App\States\Contract\Signed;
use App\States\Contract\Voided;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Tests\TestCase;

uses(TestCase::class);

test('badge backed enums implement filament label and color contracts', function () {
    $states = [
        [EventStatus::Draft, __('admin.statuses.draft'), 'gray'],
        [EventStatus::Published, __('admin.statuses.published'), 'success'],
        [EventStatus::Archived, __('admin.statuses.archived'), 'gray'],
        [EventType::Trip, __('admin.event_types.trip'), 'info'],
        [EventType::Camp, __('admin.event_types.camp'), 'success'],
        [EventType::Festival, __('admin.event_types.festival'), 'warning'],
        [BookingInstallmentState::Pending, __('admin.statuses.pending'), 'warning'],
        [BookingInstallmentState::Paid, __('admin.statuses.paid'), 'success'],
        [PaymentState::Pending, __('admin.statuses.pending'), 'warning'],
        [PaymentState::Paid, __('admin.statuses.paid'), 'success'],
        [PaymentState::Failed, __('admin.statuses.failed'), 'danger'],
        [PaymentState::Cancelled, __('admin.statuses.cancelled'), 'gray'],
        [PaymentState::PartiallyRefunded, __('admin.statuses.partially_refunded'), 'info'],
        [PaymentState::Refunded, __('admin.statuses.refunded'), 'gray'],
        [PaymentRefundState::Pending, __('admin.statuses.pending'), 'warning'],
        [PaymentRefundState::Succeeded, __('admin.statuses.succeeded'), 'success'],
        [PaymentRefundState::Failed, __('admin.statuses.failed'), 'danger'],
        [LedgerAccountType::Asset, __('admin.ledger_account_types.asset'), 'info'],
        [LedgerAccountType::Liability, __('admin.ledger_account_types.liability'), 'warning'],
        [LedgerAccountType::Equity, __('admin.ledger_account_types.equity'), 'gray'],
        [LedgerAccountType::Income, __('admin.ledger_account_types.income'), 'success'],
        [LedgerAccountType::Expense, __('admin.ledger_account_types.expense'), 'danger'],
        [UserRole::Staff, 'Staff', 'gray'],
        [UserRole::Admin, 'Admin', 'primary'],
        [PaymentProvider::Thawani, 'Thawani', 'info'],
        [PaymentProvider::Manual, 'Manual', 'gray'],
    ];

    foreach ($states as [$state, $label, $color]) {
        expect($state)
            ->toBeInstanceOf(HasLabel::class)
            ->toBeInstanceOf(HasColor::class)
            ->and($state->getLabel())->toBe($label)
            ->and($state->getColor())->toBe($color);
    }
});

test('booking and contract states implement filament label and color contracts', function () {
    $states = [
        [BookingState::make(PendingReview::$name, new Booking), __('admin.statuses.pending_review'), 'warning'],
        [BookingState::make(Approved::$name, new Booking), __('admin.statuses.approved'), 'success'],
        [BookingState::make(Rejected::$name, new Booking), __('admin.statuses.rejected'), 'danger'],
        [BookingState::make(Cancelled::$name, new Booking), __('admin.statuses.cancelled'), 'gray'],
        [ContractState::make(AwaitingSignature::$name, new EventContract), __('admin.statuses.awaiting_signature'), 'warning'],
        [ContractState::make(Signed::$name, new EventContract), __('admin.statuses.signed'), 'success'],
        [ContractState::make(Voided::$name, new EventContract), __('admin.statuses.voided'), 'gray'],
    ];

    foreach ($states as [$state, $label, $color]) {
        expect($state)
            ->toBeInstanceOf(HasLabel::class)
            ->toBeInstanceOf(HasColor::class)
            ->and($state->getLabel())->toBe($label)
            ->and($state->getColor())->toBe($color)
            ->and($state->label())->toBe($label);
    }
});
