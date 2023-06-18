<?php

namespace App\Imports;

use App\Models\City;
use App\Models\Customer;
use App\Models\Street;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RemembersRowNumber;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

class CustomersImport implements ToModel, WithHeadingRow, WithValidation, WithUpserts, SkipsOnFailure
{
    use Importable, RemembersRowNumber;

    public ?collection $collectedFailures = null;

    public function __construct()
    {
        $this->collectedFailures = collect([]);
    }


    // Gets called when there is a failure on the rules that validates the data
    public function onFailure(Failure ...$failures): void
    {
        // Adds a failure message to the collection, which will be sent to the frontend later
        foreach ($failures as $failure) {
            $this->collectedFailures->push("Customer on row " . $failure->row() . " not imported: " . collect($failure->errors())->implode(", "));
        }
    }

    // This inserts and retrieves each city and street from the data into the database.
    // If a city is already present is just retrieved instead.
    // The street is follows a similar process as with the city.
    // The key difference is that the city ID is associated with the street.
    // Finally, the city and street IDs are linked to the customer and inserted into the database.
    public function model(array $row): ?Customer
    {
        // Separates the address into an array based on the coma
        $address = explode(',', $row['address']);

        // If the array does not equal to 2 we return null on this row
        if (count($address) != 2) {
            $this->collectedFailures->push("Customer on row " . $this->getRowNumber() . " not imported: Address format is not correct");
            return null;
        }

        // Removes the numbers from the street and any start and ending space
        $address[0] = trim(preg_replace('/[0-9]/', '', $address[0]));
        $address[1] = trim($address[1]);

        // Removes the separated street and city from the address
        $row['address'] = trim(str_replace(array_merge($address, [',']), '', $row['address']));

        // Create or retrieve the city
        $city = City::firstOrCreate(['name' => $address[1]]);

        // Create or retrieve the street + attaching the city
        $street = Street::firstOrCreate(['name' => $address[0]]);
        $street->cities()->syncWithoutDetaching(
            $city->id,
        );

        // Sets and cleans some data to the customer
        $row['city_id'] = $city->id;
        $row['street_id'] = $street->id;
        $row['phone_number'] = str_replace('-', '', $row['phone_number']);

        // Inserts the customer if the email is unique, if not we just updated it
        return new Customer($row);
    }

    // It defines a set of rules each row should follow
    // Example: the name is required and must be a maximum of 255 characters
    // If any of the rules is not followed that row is not inserted, and it throws an error to frontend
    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'email' => 'required|max:255|email',
            'address' => 'required|max:255',
            'phone_number' => 'required|size:12'
        ];
    }

    // Makes sure the user is unique by the email, helping to update the model instead of creating it
    public function uniqueBy(): string
    {
        return 'email';
    }
}
