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
                'in:cash,card,bank_transfer,online,aba,wing,acleda',
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

    public function deposit(
        Request $request,
        Booking $booking
    ): JsonResponse {
        $isOwner = $booking->guest_id === $request->user()?->guest?->id;
        $isStaff = $request->user()?->can('payments.create') ?? false;

        abort_unless(
            $isOwner || $isStaff,
            403,
            'You are not authorized to pay this booking deposit.'
        );

        $data = $request->validate([
            'payment_method' => [
                'required',
                'string',
                'in:card,aba,wing,acleda',
            ],

            'transaction_id' => [
                'required',
                'string',
                'max:150',
            ],
        ]);

        $payment = $this->paymentService->create([
            'booking_id' => $booking->id,
            'amount' => (float) $booking->deposit_amount,
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'] ?? null,
        ]);

        $payment = $this->paymentService->markPaid($payment);

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }
}
