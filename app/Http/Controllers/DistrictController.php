<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index()
    {
        return response()->json(District::withCount('areas')->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $district = District::create($data);

        return response()->json($district, 201);
    }

    public function update(Request $request, District $district)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $district->update($data);

        return response()->json($district);
    }

    public function destroy(District $district)
    {
        $district->delete();
        return response()->json(['success' => true]);
    }

    public function toggleActive(District $district)
    {
        $district->update(['is_active' => !$district->is_active]);
        return response()->json(['success' => true, 'is_active' => $district->is_active]);
    }
}
