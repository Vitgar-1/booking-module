# Booking Module API (Laravel)

Этот проект предоставляет API для управления бронированием номеров в отеле. Ниже приведены инструкции по установке и настройке.

## Требования

- PHP 8.1+
- Composer
- MySQL 5.7+
- Laravel 10+

## Установка

1. Клонируйте репозиторий:
```bash
git clone https://github.com/Vitgar-1/booking-module.git
cd booking-module
```

2. Установите зависимости:
```bash
composer install
```

3. Создайте файл окружения:
```bash
cp .env.example .env
```

4. Сгенерируйте ключ приложения:
```bash
php artisan key:generate
```

## Настройка базы данных

1. Создайте базу данных в MySQL:
```bash
mysql -u root -p -e "CREATE DATABASE booking_module_new;"
```

2. Настройте подключение к БД в файле `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_module_new
DB_USERNAME=root
DB_PASSWORD=your_password
```

3. Выполните миграции и заполните тестовыми данными(автоматически):
```bash
php artisan migrate --seed
```

## Запуск сервера

```bash
php artisan serve
```

## API Endpoints

### 1. Получение доступных номеров
```
GET /api/rooms/available?check_in=YYYY-MM-DD&check_out=YYYY-MM-DD
```

### 2. Создание бронирования
```
POST /api/bookings
{
    "client_id": 1,
    "room_id": 1,
    "check_in": "YYYY-MM-DD",
    "check_out": "YYYY-MM-DD"
}
```

### 3. Получение списка бронирований
```
GET /api/bookings?status=confirmed
```

## Тестирование

Для запуска тестов:
```bash
php artisan test
```

Перед запуском тестов убедитесь, что:
1. Создана тестовая БД (по умолчанию `laravel_testing`)
2. В `phpunit.xml` указаны правильные параметры подключения

Пример `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="laravel_testing"/>
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="3306"/>
<env name="DB_USERNAME" value="root"/>
<env name="DB_PASSWORD" value="your_password"/>
```

## Структура базы данных

Основные таблицы:
- `clients` - информация о клиентах
- `rooms` - информация о номерах
- `bookings` - информация о бронированиях

Связи:
- Один клиент → Много бронирований
- Один номер → Много бронирований
