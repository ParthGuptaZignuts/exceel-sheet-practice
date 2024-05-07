<?php

namespace App\Http\Controllers;

use App\Imports\CustomerImport;
use App\Mail\Testing;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Customer;
use Illuminate\Support\Facades\Mail;

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

    public function sendMail(Request $request)
    {
        try {

            $validate = $request->validate([
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                'phone' => 'required'
            ]);

            $existing = Customer::where('email', $validate['email'])->first();
            
            $details = [
                'first_name' => $validate['first_name'],
                'last_name' => $validate['last_name'],
                'email' => $validate['email'],
                'phone' => $validate['phone']
            ];


            if ($existing) {
                $existing->update($validate);
                Mail::to($request['email'])->send(new Testing($details));
                return response()->json([
                    "message" => "Customer already exists updating it ...",
                    "customer" => $existing
                ], 409);
            }

            $newCustomer = Customer::create($validate);

            Mail::to($request['email'])->send(new Testing($details));

            return response()->json([
                'message' => 'Customer created successfully',
                'customer' => $newCustomer,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
