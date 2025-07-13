<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase;
use App\Models\Client;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    protected Client $client;
    protected Room $room;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестовые данные
        $this->client = Client::factory()->create();
        $this->room = Room::factory()->create();
    }

    public function test_check_available_rooms()
    {
        $response = $this->getJson('/api/rooms/available?check_in='.date('Y-m-d').'&check_out='.date('Y-m-d', strtotime('+7 day')));
        $response->assertStatus(200)->assertJsonStructure([
            '*' => [
                'id', 'number', 'capacity', 'price_per_night', 'description'
            ]
        ]);
    }

    public function test_check_available_rooms_swaped_date()
    {
        $response = $this->getJson('/api/rooms/available?check_in='.date('Y-m-d', strtotime('+7 day')).'&check_out='.date('Y-m-d'));
        $response->assertStatus(200)
             ->assertJson([
                 'dates_swapped' => true,
                 'message' => 'Даты check_in и check_out были автоматически заменены местами'
             ]);
    }

    public function test_new_booking()
    {
        $data = [
            'client_id' => $this->client->id,
            'room_id' => $this->room->id,
            'check_in' => date('Y-m-d'),
            'check_out' => date('Y-m-d', strtotime('+7 day'))
        ];

        $response = $this->postJson('/api/bookings', $data);

        $response->assertStatus(201)->assertJsonStructure([
            'id', 'client_id', 'room_id', 'check_in', 'check_out', 'status'
        ]);
    }

    public function test_new_booking_swaped_date()
    {
        $data = [
            'client_id' => $this->client->id,
            'room_id' => $this->room->id,
            'check_in' => date('Y-m-d', strtotime('+7 day')),
            'check_out' => date('Y-m-d')
        ];

        $response = $this->postJson('/api/bookings', $data);

        $response->assertStatus(201)->assertJsonStructure([
            'id', 'client_id', 'room_id', 'check_in', 'check_out', 'status', 'message' ,'dates_swapped'
        ]);
    }

    public function test_room_not_available()
    {
        // Создаем первое бронирование
        $this->postJson('/api/bookings', [
            'client_id' => $this->client->id,
            'room_id' => $this->room->id,
            'check_in' => date('Y-m-d'),
            'check_out' => date('Y-m-d', strtotime('+7 day'))
        ]);

        // Пытаемся создать второе бронирование на те же даты
        $response = $this->postJson('/api/bookings', [
            'client_id' => Client::factory()->create()->id,
            'room_id' => $this->room->id,
            'check_in' => date('Y-m-d'),
            'check_out' => date('Y-m-d', strtotime('+7 day'))
        ]);

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Номер занят на выбранные даты']);
    }

    public function test_bookings_list()
    {
        // Создаем тестовое бронирование
        $this->postJson('/api/bookings', [
            'client_id' => $this->client->id,
            'room_id' => $this->room->id,
            'check_in' => date('Y-m-d'),
            'check_out' => date('Y-m-d', strtotime('+7 day'))
        ]);

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)->assertJsonStructure([
            '*' => [
                'id', 'client_id', 'room_id', 'check_in', 'check_out', 'status',
                'client', 'room'
            ]
        ]);

    }

    public function test_bookings_by_status()
    {
        $response = $this->getJson('/api/bookings?status=confirmed');

        $response->assertStatus(200)->assertJsonCount(0); // Брони ещё нет

        // Создаем тестовое бронирование
        $this->postJson('/api/bookings', [
            'client_id' => $this->client->id,
            'room_id' => $this->room->id,
            'check_in' => date('Y-m-d'),
            'check_out' => date('Y-m-d', strtotime('+7 day'))
        ]);

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)->assertJsonCount(1); // Бронь теперь есть

    }

    public function test_bookings_by_status_bad_param()
    {
        $response = $this->getJson('/api/bookings?status=PLS_DROP_DATEBASE');

        $response->assertStatus(400)->assertJsonStructure([
            'allowed_values',  'error'
        ]);

    }


}
