<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerPaymentRequest;
use App\Http\Requests\Api\V1\UpdateCustomerPaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\BookingInstallment;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerPaymentController extends Controller
{
    public function index(Request $request, Customer $customer): AnonymousResourceCollection
    {
        $payments = $this->customerPayments($customer)
            ->with('bookingInstallment')
            ->latest()
            ->paginate($this->perPage($request));

        return PaymentResource::collection($payments);
    }

    public function store(StoreCustomerPaymentRequest $request, Customer $customer): JsonResponse
    {
        $validated = $request->validated();
        $installment = $this->customerInstallment($customer, (int) $validated['booking_installment_id']);
        unset($validated['booking_installment_id']);

        $payment = $installment->payments()->create($validated);

        return PaymentResource::make($payment->load('bookingInstallment'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Customer $customer, Payment $payment): PaymentResource
    {
        return PaymentResource::make($this->resolveCustomerPayment($customer, $payment));
    }

    public function update(UpdateCustomerPaymentRequest $request, Customer $customer, Payment $payment): PaymentResource
    {
        $payment = $this->resolveCustomerPayment($customer, $payment);
        $validated = $request->validated();

        if (array_key_exists('booking_installment_id', $validated)) {
            $validated['booking_installment_id'] = $this->customerInstallment($customer, (int) $validated['booking_installment_id'])->id;
        }

        $payment->update($validated);

        return PaymentResource::make($payment->refresh()->load('bookingInstallment'));
    }

    public function destroy(Customer $customer, Payment $payment): Response
    {
        $this->resolveCustomerPayment($customer, $payment)->delete();

        return response()->noContent();
    }

    /**
     * @return Builder<Payment>
     */
    private function customerPayments(Customer $customer): Builder
    {
        return Payment::query()
            ->whereHas(
                'bookingInstallment.paymentSchedule.booking',
                fn (Builder $query): Builder => $query->whereBelongsTo($customer),
            );
    }

    private function resolveCustomerPayment(Customer $customer, Payment $payment): Payment
    {
        return $this->customerPayments($customer)
            ->whereKey($payment->id)
            ->with('bookingInstallment')
            ->firstOrFail();
    }

    private function customerInstallment(Customer $customer, int $bookingInstallmentId): BookingInstallment
    {
        return BookingInstallment::query()
            ->whereKey($bookingInstallmentId)
            ->whereHas(
                'paymentSchedule.booking',
                fn (Builder $query): Builder => $query->whereBelongsTo($customer),
            )
            ->firstOrFail();
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
