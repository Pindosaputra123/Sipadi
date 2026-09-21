<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distribusi;
use Illuminate\Http\Request;

class DistribusiController extends Controller
{
    public function index()
    {
        return Distribusi::with('gudang')->paginate(15);
    }

    public function show(Distribusi $distribusi)
    {
        return $distribusi->load('gudang');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'gudang_id' => 'required|integer|exists:gudang,id',
            'jumlah_distribusi' => 'required|numeric|min:0',
            'tujuan' => 'required|string|max:255',
            'tanggal_distribusi' => 'required|date',
            'catatan' => 'nullable|string',
        ]);

        $distribusi = Distribusi::create($data);

        return response()->json($distribusi, 201);
    }

    public function update(Request $request, Distribusi $distribusi)
    {
        $data = $request->validate([
            'gudang_id' => 'sometimes|required|integer|exists:gudang,id',
            'jumlah_distribusi' => 'sometimes|required|numeric|min:0',
            'tujuan' => 'sometimes|required|string|max:255',
            'tanggal_distribusi' => 'sometimes|required|date',
            'catatan' => 'nullable|string',
        ]);

        $distribusi->update($data);

        return response()->json($distribusi);
    }

    public function destroy(Distribusi $distribusi)
    {
        $distribusi->delete();

        return response()->json(null, 204);
    }
}
