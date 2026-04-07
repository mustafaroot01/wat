<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        return response()->json(Area::with('district')->paginate(15));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $area = Area::create($data);

        return response()->json($area->load('district'), 201);
    }

    public function update(Request $request, Area $area)
    {
        $data = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean'
        ]);

        $area->update($data);

        return response()->json($area->load('district'));
    }

    public function destroy(Area $area)
    {
        $area->delete();
        return response()->json(['success' => true]);
    }

    public function toggleActive(Area $area)
    {
        $area->update(['is_active' => !$area->is_active]);
        return response()->json(['success' => true, 'is_active' => $area->is_active]);
    }
}
