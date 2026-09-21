<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertConfiguration;
use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function index()
    {
        $config = AlertConfiguration::first();
        return view('admin.pengaturan.index', compact('config'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'batas_min_beras'    => 'required|integer|min:0',
            'batas_min_gabah'    => 'required|integer|min:0',
            'kapasitas_max_beras'=> 'required|integer|min:1',
            'kapasitas_max_gabah'=> 'required|integer|min:1',
            'target_pasar'       => 'required|integer|min:0',
        ]);

        $config = AlertConfiguration::first();
        if ($config) {
            $config->update($validated);
        } else {
            AlertConfiguration::create($validated);
        }

        return redirect()->route('admin.pengaturan')->with('success', 'Pengaturan berhasil disimpan.');
    }
}