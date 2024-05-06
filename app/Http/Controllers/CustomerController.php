<?php

namespace App\Http\Controllers;

use App\Imports\CustomerImport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

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
}
