<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Room;
use Illuminate\Http\Request;

class BookingController extends Controller
{

     /**
     * 1. Просмотр доступных номеров на период
     * GET /api/rooms/available?check_in=2025-07-15&check_out=2025-07-20
     */
    public function availableRooms(Request $request)
    {

        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in'
        ]);
        
        // Логика поиска свободных номеров
        $availableRooms = Room::whereDoesntHave('bookings', function($query) use ($request) {
            $query->where('check_out', '>', $request->check_in)
                  ->where('check_in', '<', $request->check_out)
                  ->whereIn('status', ['confirmed', 'checked_in']);
        })->get();
        
        return response()->json($availableRooms);
    }



    /**
     * 2. Просмотр списка бронирований
     * GET /api/bookings?status=confirmed
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $bookings = Booking::query();

        if($status){
            $bookings->where('status', $status);
        }
        $bookings->with(['client', 'room']);

        return response()->json($bookings->get());
    }

    /**
     * 3. Постановка брони
     * POST /api/bookings
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        //Проверка доступности номера
        $isAvailable = !Booking::where('room_id', $validated['room_id'])
        ->where('check_out', '>', $validated['check_in'])
        ->where('check_in', '<', $validated['check_out'])
        ->whereIn('status', ['confirmed', 'checked_in'])
        ->exists();

        if (!$isAvailable) {
            return response()->json(['error' => 'Номер занят на выбранные даты'], 400);
        }

        $bookingData = array_merge($validated, ['status' => 'confirmed']);
        $booking = Booking::create($bookingData);

        return response()->json($booking, 201);
    }

}
