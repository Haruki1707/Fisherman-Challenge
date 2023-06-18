<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Customer;
use App\Models\Street;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_file_can_be_uploaded_and_customers_are_imported(): void
    {
        $response = $this->post('/', [
            'excel_file' => new UploadedFile(storage_path('Fisherman Challenge.xlsx'), 'Data.xlsx',
                'application/vnd.ms-excel', test: true)
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_not_excel_file_cannot_be_uploaded(): void
    {
        $response = $this->post('/', [
            'excel_file' => new UploadedFile(storage_path('Empty Example.csv'), 'Data.csv',
                'text/csv', test: true)
        ]);

        $response->assertSessionHasErrors();
    }

    public function test_customers_with_errors_are_not_imported(): void
    {
        $response = $this->post('/', [
            'excel_file' => new UploadedFile(storage_path('Fisherman Challenge With Errors.xlsx'), 'Data.xlsx',
                'application/vnd.ms-excel', test: true)
        ]);

        $response->assertSessionHasErrors();
    }
}
