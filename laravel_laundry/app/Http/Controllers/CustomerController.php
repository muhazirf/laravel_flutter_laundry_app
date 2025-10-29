<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Models\Customer;
use App\Models\Outlet;

class CustomerController extends Controller
{
    public function index(Outlet $outlet, Request $request) : JsonResponse
    {
        $query = $outlet->customers();

        if($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search){
                $q->where('name', 'like', '%'. $search .'%')
                  ->orWhere('phone', 'like', '%'. $search .'%')
                  ->orWhere('email', 'like', '%'. $search .'%');
            });
        }

        if($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->orderBy('name');

        $customers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                ]
            ]
        ]); 
    }

    public function store(Outlet $outlet, Request $request) : JsonResponse
    {
        $rules = [
            'name' => 'required|string|max:50',
            'email' => 'nullable|email|max:50|unique:customers,email',
            'phone' => 'required|string|max:15|unique:customers,phone',
            'address' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ];

        $messages = [
            'name.required' => 'Nama harus diisi',
            'name.string' => 'Nama harus berupa string',
            'name.max' => 'Nama maksimal 50 karakter',
            'email.email' => 'Email tidak valid',
            'email.max' => 'Email maksimal 50 karakter',
            'email.unique' => 'Email sudah terdaftar',
            'phone.required' => 'Nomor telepon harus diisi',
            'phone.string' => 'Nomor telepon harus berupa string',
            'phone.max' => 'Nomor telepon maksimal 15 karakter',
            'phone.unique' => 'Nomor telepon sudah terdaftar',
            'address.string' => 'Alamat harus berupa string',
            'address.max' => 'Alamat maksimal 100 karakter',
            'is_active.boolean' => 'Status aktif harus berupa boolean'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        
        $customer = $outlet->customers()->create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'is_active' => $request->get('is_active', true),
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        if($customer) {
            return response()->json([
                'success' => true,
                'message' => 'Customer berhasil ditambahkan',
                'data' => $customer
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Customer gagal ditambahkan'
            ], 500);
        }
    }

    public function show(Outlet $outlet, Customer $customer) : JsonResponse
    {
        if($customer->outlet_id !== $outlet->id) {
            return response()->json([
                'success' => false,
                'message' => 'Customer tidak ditemukan di outlet ini'
            ], 404);
        }
        $customer->load(['orders' => function($query){
            $query->latest()->take(5);
        }]);

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }
}
