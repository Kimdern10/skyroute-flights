<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

/**
 * Major international airports for the search boxes. Safe to run again.
 */
class AirportSeeder extends Seeder
{
    /**
     * @var list<array{0: string, 1: string, 2: string, 3: string}> [code, airport, city, country]
     */
    public const array AIRPORTS = [
        ['ATL', 'Hartsfield-Jackson', 'Atlanta', 'United States'],
        ['BOS', 'Logan', 'Boston', 'United States'],
        ['ORD', "O'Hare", 'Chicago', 'United States'],
        ['DFW', 'Dallas/Fort Worth', 'Dallas', 'United States'],
        ['DEN', 'Denver International', 'Denver', 'United States'],
        ['IAH', 'George Bush Intercontinental', 'Houston', 'United States'],
        ['LAS', 'Harry Reid', 'Las Vegas', 'United States'],
        ['LAX', 'Los Angeles International', 'Los Angeles', 'United States'],
        ['MIA', 'Miami International', 'Miami', 'United States'],
        ['JFK', 'John F. Kennedy', 'New York', 'United States'],
        ['EWR', 'Newark Liberty', 'Newark', 'United States'],
        ['MCO', 'Orlando International', 'Orlando', 'United States'],
        ['SFO', 'San Francisco International', 'San Francisco', 'United States'],
        ['SEA', 'Seattle-Tacoma', 'Seattle', 'United States'],
        ['IAD', 'Washington Dulles', 'Washington', 'United States'],
        ['YYZ', 'Toronto Pearson', 'Toronto', 'Canada'],
        ['YVR', 'Vancouver International', 'Vancouver', 'Canada'],
        ['MEX', 'Benito Juárez', 'Mexico City', 'Mexico'],
        ['CUN', 'Cancún International', 'Cancún', 'Mexico'],
        ['GRU', 'Guarulhos', 'São Paulo', 'Brazil'],
        ['EZE', 'Ministro Pistarini', 'Buenos Aires', 'Argentina'],
        ['BOG', 'El Dorado', 'Bogotá', 'Colombia'],
        ['LHR', 'Heathrow', 'London', 'United Kingdom'],
        ['LGW', 'Gatwick', 'London', 'United Kingdom'],
        ['MAN', 'Manchester', 'Manchester', 'United Kingdom'],
        ['DUB', 'Dublin', 'Dublin', 'Ireland'],
        ['CDG', 'Charles de Gaulle', 'Paris', 'France'],
        ['AMS', 'Schiphol', 'Amsterdam', 'Netherlands'],
        ['FRA', 'Frankfurt', 'Frankfurt', 'Germany'],
        ['MUC', 'Munich', 'Munich', 'Germany'],
        ['MAD', 'Barajas', 'Madrid', 'Spain'],
        ['BCN', 'El Prat', 'Barcelona', 'Spain'],
        ['FCO', 'Fiumicino', 'Rome', 'Italy'],
        ['ZRH', 'Zurich', 'Zurich', 'Switzerland'],
        ['LIS', 'Humberto Delgado', 'Lisbon', 'Portugal'],
        ['IST', 'Istanbul', 'Istanbul', 'Turkey'],
        ['ATH', 'Athens International', 'Athens', 'Greece'],
        ['DXB', 'Dubai International', 'Dubai', 'United Arab Emirates'],
        ['AUH', 'Zayed International', 'Abu Dhabi', 'United Arab Emirates'],
        ['DOH', 'Hamad International', 'Doha', 'Qatar'],
        ['CAI', 'Cairo International', 'Cairo', 'Egypt'],
        ['CMN', 'Mohammed V', 'Casablanca', 'Morocco'],
        ['LOS', 'Murtala Muhammed', 'Lagos', 'Nigeria'],
        ['ABV', 'Nnamdi Azikiwe', 'Abuja', 'Nigeria'],
        ['ACC', 'Kotoka', 'Accra', 'Ghana'],
        ['NBO', 'Jomo Kenyatta', 'Nairobi', 'Kenya'],
        ['ADD', 'Bole', 'Addis Ababa', 'Ethiopia'],
        ['JNB', 'O. R. Tambo', 'Johannesburg', 'South Africa'],
        ['CPT', 'Cape Town International', 'Cape Town', 'South Africa'],
        ['DEL', 'Indira Gandhi', 'Delhi', 'India'],
        ['BOM', 'Chhatrapati Shivaji Maharaj', 'Mumbai', 'India'],
        ['SIN', 'Changi', 'Singapore', 'Singapore'],
        ['BKK', 'Suvarnabhumi', 'Bangkok', 'Thailand'],
        ['KUL', 'Kuala Lumpur International', 'Kuala Lumpur', 'Malaysia'],
        ['HKG', 'Hong Kong International', 'Hong Kong', 'Hong Kong'],
        ['PEK', 'Beijing Capital', 'Beijing', 'China'],
        ['PVG', 'Pudong', 'Shanghai', 'China'],
        ['ICN', 'Incheon', 'Seoul', 'South Korea'],
        ['NRT', 'Narita', 'Tokyo', 'Japan'],
        ['HND', 'Haneda', 'Tokyo', 'Japan'],
        ['SYD', 'Kingsford Smith', 'Sydney', 'Australia'],
        ['MEL', 'Melbourne', 'Melbourne', 'Australia'],
        ['AKL', 'Auckland', 'Auckland', 'New Zealand'],
    ];

    public function run(): void
    {
        foreach (self::AIRPORTS as [$code, $name, $city, $country]) {
            Airport::query()->updateOrCreate(['iata_code' => $code], ['name' => $name, 'city' => $city, 'country' => $country]);
        }
    }
}
