<?php

namespace App\Http\Controllers\Api\V1\Export;

use App\Http\Controllers\Controller;
use App\Services\Export\ExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        private readonly ExportService $exportService
    ) {}

    public function bookings(Request $request): StreamedResponse
    {
        return $this->exportService->exportBookings(
            $request->validate([
                'status' => ['nullable', 'string'],
                'search' => ['nullable', 'string', 'max:200'],
            ])
        );
    }

    public function guests(Request $request): StreamedResponse
    {
        return $this->exportService->exportGuests(
            $request->validate([
                'search' => ['nullable', 'string', 'max:200'],
            ])
        );
    }

    public function payments(Request $request): StreamedResponse
    {
        return $this->exportService->exportPayments(
            $request->validate([
                'status' => ['nullable', 'string'],
            ])
        );
    }
}
