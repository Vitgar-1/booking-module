<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
        $response = $this->getJson('/api/rooms/available?check_in=2025-07-15&check_out=2025-07-20');
        $response->assertStatus(200)->assertJsonStructure([
            '*' => [
                'id', 'number', 'capacity', 'price_per_night', 'description'
            ]
        ]);
    }

    public function test_new_booking()
    {
        $data = [
            'client_id' => $this->client->id,
            'room_id' => $this->room->id,
            'check_in' => '2025-07-15',
            'check_out' => '2025-07-20'
        ];

        $response = $this->postJson('/api/bookings', $data);

        $response->assertStatus(201)->assertJsonStructure([
            'id', 'client_id', 'room_id', 'check_in', 'check_out', 'status'
        ]);
    }

    public function test_room_not_available()
    {
        // Создаем первое бронирование
        $this->postJson('/api/bookings', [
            'client_id' => $this->client->id,
            'room_id' => $this->room->id,
            'check_in' => '2025-07-15',
            'check_out' => '2025-07-20'
        ]);

        // Пытаемся создать второе бронирование на те же даты
        $response = $this->postJson('/api/bookings', [
            'client_id' => Client::factory()->create()->id,
            'room_id' => $this->room->id,
            'check_in' => '2025-07-16',
            'check_out' => '2025-07-19'
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
            'check_in' => '2025-07-15',
            'check_out' => '2025-07-20'
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
            'check_in' => '2025-07-15',
            'check_out' => '2025-07-20'
        ]);

        $response = $this->getJson('/api/bookings');

        $response->assertStatus(200)->assertJsonCount(1); // Бронь теперь есть

    }


}
