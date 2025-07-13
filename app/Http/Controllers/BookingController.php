<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
    * GET /api/rooms/available?check_in=2025-07-15&check_out=2025-07-20
    */
    public function availableRooms(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $customResponse = [];

            foreach ($errors->messages() as $field => $messages) {
                $code = 'validation_error';
                
                if ($field === 'check_in') $code = 'invalid_check_in_date';
                if ($field === 'check_out') $code = 'invalid_check_out_date';
                
                $customResponse['errors'][] = [
                    'field' => $field,
                    'code' => $code,
                    'message' => $messages[0]
                ];
            }

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $customResponse['errors']
            ], 400);
        }

        $validated = $validator->validated();
        $datesSwapped = false;

        // Проверяем и корректируем даты при необходимости
        if (strtotime($validated['check_in']) > strtotime($validated['check_out'])) {
            $temp = $validated['check_in'];
            $validated['check_in'] = $validated['check_out'];
            $validated['check_out'] = $temp;
            $datesSwapped = true;
        }


        // Логика поиска свободных номеров
        $availableRooms = Room::whereDoesntHave('bookings', function($query) use ($request) {
            $query->where('check_out', '>', $request->check_in)
                  ->where('check_in', '<', $request->check_out)
                  ->whereIn('status', ['confirmed', 'checked_in']);
        })->get();
        
        // Добавляем флаг замены дат в ответ
        if ($datesSwapped) {
            $availableRooms['dates_swapped'] = true;
            $availableRooms['message'] = 'Даты check_in и check_out были автоматически заменены местами';
        }
        
        return response()->json($availableRooms);
    }

    /**
    * GET /api/bookings?status=confirmed
    */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $allowedStatuses = ['confirmed', 'checked_in', 'checked_out', 'canceled'];
        
        if ($status && !in_array($status, $allowedStatuses)) {
            return response()->json([
                'error' => 'Invalid status parameter',
                'allowed_values' => $allowedStatuses
            ], 400);
        }

        $status = $request->query('status');
        $bookings = Booking::query();

        if($status){
            $bookings->where('status', $status);
        }
        $bookings->with(['client', 'room']);

        return response()->json($bookings->get());
    }

    /**
    * POST /api/bookings
    */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $customResponse = [];

            foreach ($errors->messages() as $field => $messages) {
                $code = 'validation_error';
                
                if ($field === 'client_id') $code = 'bad_client_id';
                if ($field === 'room_id') $code = 'bad_room_id';
                if ($field === 'check_in') $code = 'invalid_check_in_date';
                if ($field === 'check_out') $code = 'invalid_check_out_date';
                
                $customResponse['errors'][] = [
                    'field' => $field,
                    'code' => $code,
                    'message' => $messages[0]
                ];
            }

            return response()->json([
                'message' => 'Validation failed',
                'errors' => $customResponse['errors']
            ], 400);
        }

        $validated = $validator->validated();
        $datesSwapped = false;

        // Проверяем и корректируем даты при необходимости
        if (strtotime($validated['check_in']) > strtotime($validated['check_out'])) {
            $temp = $validated['check_in'];
            $validated['check_in'] = $validated['check_out'];
            $validated['check_out'] = $temp;
            $datesSwapped = true;
        }

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

        // Добавляем флаг замены дат в ответ
        $response = $booking->toArray();
        if ($datesSwapped) {
            $response['dates_swapped'] = true;
            $response['message'] = 'Даты check_in и check_out были автоматически заменены местами';
        }

    return response()->json($response, 201);
    }

}
