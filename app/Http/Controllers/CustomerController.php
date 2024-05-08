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

    public function index(Request $request)
    {

        try {

            $validate = $request->validate([
                'ids' => 'sometimes|array',
            ]);

            if ($request->has('ids')) {
                $customers = Customer::whereIn('id', $validate['ids'])->get();

                foreach ($customers as $customer) {
                    $details = [
                        'first_name' => $customer->first_name,
                        'last_name' => $customer->last_name,
                        'email' => $customer->email,
                        'phone' => $customer->phone
                    ];
                    Mail::to($customer->email)->send(new Testing($details));
                }


                return response()->json([
                    "message" => "Retrieving all customers...",
                    "customers" => $customers,
                ], 200);
            } else {
                $customers = Customer::all();
                return response()->json([
                    "message" => "Retrieving all customers...",
                    "customers" => $customers,
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                "message" => "An error occurred while retrieving customers.",
                "error" => $e->getMessage(),
            ], 500);
        }
    }

    public function filterAndPaginataion(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'sometimes|string',
                'per_page' => 'sometimes|integer|min:1|max:10', 
            ]);

            $perPage = $validated['per_page'] ?? 10;

            if ($request->has('first_name')) {
                $customers = Customer::where('first_name', $validated['first_name'])->paginate($perPage);
            } else {
                $customers = Customer::paginate($perPage);
            }

            return response()->json([
                'message' => 'Retrieving customers with pagination...',
                'data' => $customers->items(), 
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                    'last_page' => $customers->lastPage(),
                    'next_page_url' => $customers->nextPageUrl(),
                    'prev_page_url' => $customers->previousPageUrl(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving customers with pagination.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
