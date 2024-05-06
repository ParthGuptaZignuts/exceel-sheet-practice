<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CustomerImport implements ToCollection, WithHeadingRow
{
    /**
     * @param Collection $collection
     */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            $customer = Customer::where('email', $row['email'])->first();

            if ($customer) {
                $customer->update([
                    'first_name' => $row['firstname'],
                    'last_name' => $row['lastname'],
                    'email' => $row['email'],
                    'phone' => $row['phone']
                ]);
            } else {
                Customer::create([
                    'first_name' => $row['firstname'],
                    'last_name' => $row['lastname'],
                    'email' => $row['email'],
                    'phone' => $row['phone']
                ]);
            }
        }
    }
}
