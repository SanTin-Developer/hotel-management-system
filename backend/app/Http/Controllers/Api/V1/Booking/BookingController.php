<?php

namespace App\Http\Controllers\Api\V1\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Booking\IndexBookingRequest;
use App\Http\Requests\Api\V1\Booking\RoomAvailabilityRequest;
use App\Http\Requests\Api\V1\Booking\StoreBookingRequest;
use App\Http\Resources\Api\V1\BookingResource;
use App\Http\Resources\Api\V1\RoomResource;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {}

    public function index(
        IndexBookingRequest $request
    ): AnonymousResourceCollection {
        return BookingResource::collection(
            $this->bookingService->getAll(
                $request->validated()
            )
        );
    }

    public function availability(
        RoomAvailabilityRequest $request
    ): AnonymousResourceCollection {
        $rooms = $this->bookingService->getAvailableRooms(
            $request->validated('check_in'),
            $request->validated('check_out')
        );

        return RoomResource::collection($rooms);
    }

    public function availabilityCalendar(
        RoomAvailabilityRequest $request
    ): JsonResponse {
        $calendar = $this->bookingService->getAvailabilityCalendar(
            $request->validated('check_in'),
            $request->validated('check_out')
        );

        return response()->json([
            'check_in' => $request->validated('check_in'),
            'check_out' => $request->validated('check_out'),
            'rooms' => $calendar,
        ]);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->create(
            $request->validated()
        );

        return (new BookingResource($booking))
            ->response()
            ->setStatusCode(201);
    }

    public function confirm(
        Booking $booking
    ): BookingResource {
        request()->user()->can('confirm', $booking);

        $booking = $this->bookingService->confirm(
            $booking,
            request()->user()->id
        );

        return new BookingResource($booking);
    }

    public function cancel(
        Booking $booking
    ): BookingResource {
        request()->user()->can('cancel', $booking);

        $booking = $this->bookingService->cancel(
            $booking,
            request()->user()->id
        );

        return new BookingResource($booking);
    }

    public function complete(
        Booking $booking
    ): BookingResource {
        request()->user()->can('complete', $booking);

        $booking = $this->bookingService->complete(
            $booking,
            request()->user()->id
        );

        return new BookingResource($booking);
    }
}
