<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::with('category:id,name,slug')
            ->where('is_active', true)
            ->when($request->search, fn ($q, $s) =>
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")
                                       ->orWhere('description', 'like', "%{$s}%")))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->min_price, fn ($q, $p) => $q->where('price', '>=', $p))
            ->when($request->max_price, fn ($q, $p) => $q->where('price', '<=', $p))
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Services retrieved',
            'data'    => $services->items(),
            'meta'    => [
                'current_page' => $services->currentPage(),
                'last_page'    => $services->lastPage(),
                'total'        => $services->total(),
            ],
        ]);
    }

    public function show(int $id)
    {
        $service = Service::with('category:id,name,slug')
            ->where('is_active', true)
            ->find($id);

        if (! $service) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found',
                'errors'  => (object) [],
            ], 404);
        }

        return response()->json(['success' => true, 'message' => 'OK', 'data' => $service]);
    }
}