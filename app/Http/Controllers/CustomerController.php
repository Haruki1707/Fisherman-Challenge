<?php

namespace App\Http\Controllers;

use App\Imports\CustomersImport;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\HeadingRowImport;

class CustomerController extends Controller
{
    public function index()
    {
        // Gets the search and search type from the request
        $search = \request('search');
        $search_type = str_replace(' ', '_', strtolower(\request('search_type') ?? 'name'));

        // Builds the query and gets the customers, those are paginated by 15 in each page
        // Also in case search is not null the query is limited to where is like the search
        $customers = Customer::when($search ?? false, function ($query, $search) use ($search_type) {
            $search = preg_replace("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i", "", $search);

            // In case the search type is phone number the dashes are removed
            if ($search_type == 'phone_number') {
                $search = str_replace(['-', '_'], '', $search);
            }

            $query->where($search_type, 'LIKE', "%$search%");
        })->with(['street', 'city'])->paginate(15)->withQueryString();

        // Renders the vue page with the data
        return Inertia::render('Customers', [
            'customers' => $customers->items(),
            'links' => $customers->toArray()['links'],
            'filters' => \request()->only(['search', 'search_type']),
            'search_types' => ['Name', 'Email', 'Phone Number'],
        ]);
    }

    public function import(Request $request)
    {
        // Validates the file comes and is .xlsx or .xls type, with a maximum size of 10MB
        $request->validate([
            'excel_file' => 'required|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet|max:10240'
        ]);

        // Checks if the customers table on the DB contains the same columns of the Excel
        // This helps to prevent if the table has no column headers or if the Excel file
        // Does not have the same data structure as the table
        $schemaColumns = collect(Schema::getColumnListing('customers'));
        foreach ((new HeadingRowImport)->toArray($request->file('excel_file'))[0][0] as $column) {
            if (!$schemaColumns->contains($column)) {
                throw ValidationException::withMessages([
                    'common' => "'$column' not found on the DB customers table",
                ]);
            }
        }

        // Creates a new importer and imports the customer
        $import = new CustomersImport;
        $import->import($request->file('excel_file'));

        // If while importing there where any failures we throw an error, so they can be seen on the frontend
        if ($import->collectedFailures->count() > 0) {
            throw ValidationException::withMessages($import->collectedFailures->toArray());
        }

        // If everything fine, 202
        return back()->with(202);
    }
}
