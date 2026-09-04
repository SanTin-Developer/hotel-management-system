<?php

namespace App\Http\Controllers\Api\V1\Payment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\StorePaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->create(
            $request->validated()
        );

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    public function paid(Request $request, Payment $payment): PaymentResource
    {
        $payment = $this->paymentService->markPaid(
            $payment,
            $request->input('transaction_id')
        );

        return new PaymentResource($payment);
    }

    public function failed(Payment $payment): PaymentResource
    {
        return new PaymentResource(
            $this->paymentService->markFailed($payment)
        );
    }

    public function refund(
        Request $request,
        Payment $payment
    ): PaymentResource {
        $payment = $this->paymentService->refund(
            $payment,
            $request->input('transaction_id')
        );

        return new PaymentResource($payment);
    }
}
