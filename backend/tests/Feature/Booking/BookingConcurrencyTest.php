<?php

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

it('locks a room row across separate database connections', function () {
    $roomType = RoomType::create([
        'name' => 'Concurrency Test Room Type',
        'capacity' => 2,
        'base_price' => 80,
        'status' => 'active',
    ]);

    $room = Room::create([
        'room_type_id' => $roomType->id,
        'room_number' => 'LOCK-101',
        'floor' => 1,
        'status' => 'available',
    ]);

    /*
     * Create a completely separate PostgreSQL connection.
     */
    Config::set(
        'database.connections.pgsql_concurrency',
        array_merge(
            config('database.connections.pgsql'),
            [
                'name' => 'pgsql_concurrency',
            ]
        )
    );

    $connectionA = DB::connection('pgsql');
    $connectionB = DB::connection('pgsql_concurrency');

    /*
     * Connection A acquires the row lock.
     */
    $connectionA->beginTransaction();

    $lockedRoom = $connectionA
        ->table('rooms')
        ->where('id', $room->id)
        ->lockForUpdate()
        ->first();

    expect($lockedRoom)->not->toBeNull();

    /*
     * Connection B must time out while trying to acquire
     * the same row lock.
     */
    $connectionB->statement(
        "SET lock_timeout = '500ms'"
    );

    expect(function () use ($connectionB, $room) {
        $connectionB
            ->table('rooms')
            ->where('id', $room->id)
            ->lockForUpdate()
            ->first();
    })->toThrow(QueryException::class);

    /*
     * Release the lock and clean up.
     */
    $connectionA->rollBack();

    $connectionB->disconnect();
    $connectionA->disconnect();

    Room::whereKey($room->id)->delete();
    RoomType::whereKey($roomType->id)->delete();

    DB::purge('pgsql');
    DB::purge('pgsql_concurrency');
});
