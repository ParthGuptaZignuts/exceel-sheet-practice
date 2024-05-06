<?php

namespace App\Http\Controllers;

use App\Imports\CustomerImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Customer;

class CustomerController extends Controller
{
    public function importExcelData(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'file' => 'required|file|mimes:csv,xls,xlsx',
            ]);

            if ($request->hasFile('file')) {

                $filePath = $request->file('file')->store('public/files');

                Excel::import(new CustomerImport, $request->file('file'));

                return response()->json([
                    'message' => 'File uploaded and stored successfully',
                    'file_path' => $filePath,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No file was uploaded',
                ], 400);
            }
        } catch (ValidationException $e) {

            return response()->json([
                'errors' => $e->errors(),
                'message' => 'Validation failed. Please check the file and try again.'
            ], 422);
        }
    }

    public function importManualData(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
                'phone' => 'required',
            ]);

            $customer = Customer::create($validatedData);

            return response()->json([
                'message' => 'Customer created successfully',
                'customer' => $customer,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
