<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TouristPlaceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TouristPlaceCategoryController extends Controller
{
    public function index()
    {
        return TouristPlaceCategory::all();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        return TouristPlaceCategory::create($request->all());
    }

    public function show($id)
    {
        return TouristPlaceCategory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $category = TouristPlaceCategory::findOrFail($id);
        $category->update($request->all());
        return $category;
    }

    public function destroy($id)
    {
        $category = TouristPlaceCategory::findOrFail($id);
        $category->delete();
        return response()->json(null, 204);
    }
}
