<?php

namespace App\Services\Export;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    public function exportBookings(array $filters = []): StreamedResponse
    {
        return $this->stream('bookings-'.now()->format('Y-m-d-His').'.csv', function () use ($filters) {
            $this->writeRow([
                'Booking Code',
                'Guest',
                'Email',
                'Phone',
                'Check-in',
                'Check-out',
                'Adults',
                'Children',
                'Status',
                'Total Amount',
                'Source',
                'Created At',
            ]);

            $this->queryBookings($filters)->chunk(500, function (Collection $bookings) {
                foreach ($bookings as $booking) {
                    $this->writeRow([
                        $booking->booking_code,
                        $booking->guest->full_name ?? '',
                        $booking->guest->email ?? '',
                        $booking->guest->phone ?? '',
                        $booking->check_in?->toDateString(),
                        $booking->check_out?->toDateString(),
                        $booking->adults,
                        $booking->children,
                        $booking->status,
                        number_format((float) $booking->total_amount, 2),
                        $booking->booking_source,
                        $booking->created_at?->toDateTimeString(),
                    ]);
                }
            });
        });
    }

    public function exportGuests(array $filters = []): StreamedResponse
    {
        return $this->stream('guests-'.now()->format('Y-m-d-His').'.csv', function () use ($filters) {
            $this->writeRow([
                'ID',
                'Full Name',
                'Email',
                'Phone',
                'Address',
                'ID Type',
                'ID Number',
                'Nationality',
                'Created At',
            ]);

            $query = Guest::query()
                ->when(
                    ! empty($filters['search']),
                    function ($query) use ($filters) {
                        $search = $filters['search'];

                        $query->where(function ($query) use ($search) {
                            $query
                                ->where('full_name', 'ILIKE', "%{$search}%")
                                ->orWhere('email', 'ILIKE', "%{$search}%")
                                ->orWhere('phone', 'ILIKE', "%{$search}%");
                        });
                    }
                )
                ->latest('created_at');

            $query->chunk(500, function (Collection $guests) {
                foreach ($guests as $guest) {
                    $this->writeRow([
                        $guest->id,
                        $guest->full_name,
                        $guest->email,
                        $guest->phone,
                        $guest->address,
                        $guest->id_type,
                        $guest->id_number,
                        $guest->nationality,
                        $guest->created_at?->toDateTimeString(),
                    ]);
                }
            });
        });
    }

    public function exportPayments(array $filters = []): StreamedResponse
    {
        return $this->stream('payments-'.now()->format('Y-m-d-His').'.csv', function () use ($filters) {
            $this->writeRow([
                'Payment ID',
                'Booking Code',
                'Guest',
                'Amount',
                'Method',
                'Status',
                'Transaction ID',
                'Paid At',
                'Created At',
            ]);

            Payment::query()
                ->with(['booking.guest'])
                ->when(
                    ! empty($filters['status']),
                    fn ($query) => $query->where('status', $filters['status'])
                )
                ->latest('created_at')
                ->chunk(500, function (Collection $payments) {
                    foreach ($payments as $payment) {
                        $this->writeRow([
                            $payment->id,
                            $payment->booking?->booking_code ?? '',
                            $payment->booking?->guest?->full_name ?? '',
                            number_format((float) $payment->amount, 2),
                            $payment->payment_method,
                            $payment->status,
                            $payment->transaction_id,
                            $payment->paid_at?->toDateTimeString(),
                            $payment->created_at?->toDateTimeString(),
                        ]);
                    }
                });
        });
    }

    private function queryBookings(array $filters)
    {
        return Booking::query()
            ->with('guest')
            ->when(
                ! empty($filters['status']),
                fn ($query) => $query->where('status', $filters['status'])
            )
            ->when(
                ! empty($filters['search']),
                function ($query) use ($filters) {
                    $search = $filters['search'];

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('booking_code', 'ILIKE', "%{$search}%")
                            ->orWhereHas('guest', function ($query) use ($search) {
                                $query
                                    ->where('full_name', 'ILIKE', "%{$search}%")
                                    ->orWhere('email', 'ILIKE', "%{$search}%");
                            });
                    });
                }
            )
            ->latest('created_at');
    }

    private function stream(string $filename, callable $callback): StreamedResponse
    {
        return response()->streamDownload(function () use ($callback) {
            $handle = fopen('php://output', 'w');

            $this->handle = $handle;

            fwrite($handle, "\xEF\xBB\xBF");

            $callback();

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private $handle;

    private function writeRow(array $row): void
    {
        fputcsv($this->handle, $row);
    }
}
