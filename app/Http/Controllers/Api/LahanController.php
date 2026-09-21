<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lahan;
use Illuminate\Http\Request;

class LahanController extends Controller
{
    public function index()
    {
        return Lahan::with('petani')->paginate(15);
    }

    public function show(Lahan $lahan)
    {
        return $lahan->load('petani');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'petani_id' => 'required|integer|exists:petani,id',
            'nama_lahan' => 'required|string|max:255',
            'luas' => 'required|numeric|min:0',
            'lokasi' => 'required|string',
            'jenis_tanah' => 'nullable|in:sawah,ladang,kebun',
            'status' => 'nullable|in:aktif,tidak_aktif',
        ]);

        $lahan = Lahan::create($data);

        return response()->json($lahan, 201);
    }

    public function update(Request $request, Lahan $lahan)
    {
        $data = $request->validate([
            'petani_id' => 'sometimes|required|integer|exists:petani,id',
            'nama_lahan' => 'sometimes|required|string|max:255',
            'luas' => 'sometimes|required|numeric|min:0',
            'lokasi' => 'sometimes|required|string',
            'jenis_tanah' => 'nullable|in:sawah,ladang,kebun',
            'status' => 'nullable|in:aktif,tidak_aktif',
        ]);

        $lahan->update($data);

        return response()->json($lahan);
    }

    public function destroy(Lahan $lahan)
    {
        $lahan->delete();

        return response()->json(null, 204);
    }
}
