<?php

namespace App\Console\Commands;

use App\Imports\CustomersImport;
use App\Models\City;
use App\Models\Customer;
use App\Models\Street;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ImportCustomers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:customers {excelFileName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the customers from determined excel file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('excelFileName') . '.xlsx';
        if (!\Storage::disk('local')->exists($file)) {
            echo "$file Excel file not found on local storage drive";
            return;
        }

        // Gets current time before execution
        $start = microtime(true);

        // Gets the data in a collection from the file using CustomersImport
        $data = Excel::toCollection(new CustomersImport, $file, 'local')[0];

        // Subtracts current time with start execution time to see how much time it took
        $this->comment('Data retrieved in ' . (microtime(true) - $start) * 1000 . 'ms');

        if (count($data) < 1) {
            echo "No data to insert";
            return;
        }

        //TODO: Check if the table headers are the same as the table columns

        // Same as before for execution time
        $start = microtime(true);

        // Inserts each city found on the data into the DB, if repeated it doesn't insert it and retrieve it
        // Then after getting the city model we insert the street, and we repeat the same process as the city
        // With the difference that we attach the city ID to the street
        // Then finally we attach the city and street IDs to the customer and insert it to the DB
        $data->each(function ($customer) {
            // We separate the address into street and city with some cleanup
            $address = explode(',', $customer['address']);

            if (count($address) != 2) {
                return null;
            }

            $address[0] = trim(preg_replace('/^ |[0-9]/', '', $address[0]));
            $address[1] = trim($address[1]);

            // We remove the separated street and city from the address
            $customer['address'] = trim(str_replace(array_merge($address, [',']), '', $customer['address']));

            // Create or retrieve the city
            $city = City::firstOrCreate(['name' => $address[1]]);

            // Create or retrieve the street + attaching the city
            $street = Street::firstOrCreate(['name' => $address[0]]);
            $street->cities()->syncWithoutDetaching(
                $city->id,
            );

            // We set and clean some data to the customer
            $customer['city_id'] = $city->id;
            $customer['street_id'] = $street->id;
            $customer['phone_number'] = str_replace('-', '', $customer['phone_number']);

            // We insert the customer if the email is unique
            Customer::firstOrCreate(
                ['email' => $customer['email']],
                $customer->except('email')->toArray()
            );
        });

        // Same as before for execution time
        $this->comment('Data inserted in ' . (microtime(true) - $start) * 1000 . 'ms');
    }
}
