<?php

namespace App\Http\Controllers\Api\V1\Booking;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Booking;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    public function index(Booking $booking): JsonResponse
    {
        $payments = $booking->payments()->latest('created_at')->get();

        return response()->json([
            'data' => PaymentResource::collection($payments),
        ]);
    }

    public function store(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $data = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:9999999999.99',
            ],

            'payment_method' => [
                'required',
                'string',
                'in:cash,card,bank_transfer,online',
            ],

            'transaction_id' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $data['booking_id'] = $booking->id;

        $payment = $this->paymentService->create($data);

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }
}
