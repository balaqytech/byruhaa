<?php

namespace App\Modules\Finance\Contracts;

use App\Modules\Finance\Data\Payments\PaymentCheckoutData;
use App\Modules\Finance\Data\Payments\PaymentCheckoutRequest;
use App\Modules\Finance\Data\Payments\PaymentRefundData;
use App\Modules\Finance\Data\Payments\PaymentVerificationData;

interface PaymentService
{
    public function initiate(PaymentCheckoutRequest $request): PaymentCheckoutData;

    public function verifyPayment(string $paymentReference): ?PaymentVerificationData;

    public function verifySubject(string $subjectType, string $subjectReference): ?PaymentVerificationData;

    public function cancelPayment(string $paymentReference): ?PaymentVerificationData;

    public function refundPayment(string $paymentReference, int $amountBaisa, string $reason): PaymentRefundData;
}
