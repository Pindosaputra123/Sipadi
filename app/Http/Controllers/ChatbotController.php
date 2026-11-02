<?php

namespace App\Http\Controllers;

use App\Models\KonfigurasiHarga;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    /**
     * POST /chatbot/prabowo
     * Langsung panggil Gemini API dengan konteks data dari database.
     * Tidak perlu n8n — lebih simpel dan lebih cepat.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $groqKey   = config('services.groq.api_key');
        $groqModel = config('services.groq.model', 'llama-3.3-70b-versatile');

        if (empty($groqKey)) {
            return response()->json([
                'reply' => 'Maaf Kak, HPSBBot belum dikonfigurasi. Hubungi admin.',
            ]);
        }

        // ── Ambil konteks data dari database ─────────────────────────────────
        $konteks = $this->buildKonteks();

        // ── Buat system prompt ────────────────────────────────────────────────
        $systemPrompt = <<<PROMPT
Kamu adalah HPSBBot, asisten AI untuk Sistem Informasi Pengelolaan Padi (SIPADI) Kelompok Goat Pensiun.
Tugasmu membantu admin dan petugas menjawab pertanyaan seputar stok beras dan harga pangan serta
berikan jawaban terkait pertanian.

Data terkini dari sistem:
{$konteks}

Aturan menjawab:
- Gunakan Bahasa Indonesia yang ramah dan sopan.
- Untuk pertanyaan tentang SIPADI, stok beras, harga pangan, dan hasil panen, prioritaskan data dari sistem.
- Untuk pertanyaan umum tentang pertanian atau semacamnya gunakan pengetahuan umum AI.
- Jika data tidak tersedia, katakan dengan jujur.
- Jangan membuat angka atau data fiktif.
- Panggil pengguna dengan "Bos".
- Hapus jawaban dengan penulisan HTML atau Markdown, cukup teks biasa.
PROMPT;

        // ── Kirim ke Groq API (Llama 3.3) ─────────────────────────────────────
        $url = "https://api.groq.com/openai/v1/chat/completions";

        try {
            $response = Http::withToken($groqKey)->timeout(30)->post($url, [
                'model' => $groqModel,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $data['message'],
                    ],
                ],
                'temperature' => 0.7,
                'max_completion_tokens' => 512,
            ]);

            if ($response->failed()) {
                $statusCode = $response->status();
                $body       = $response->body();
                Log::error("Groq API error [{$statusCode}]: {$body}");

                return response()->json(['reply' => 'Maaf Kak, HPSBBot sedang gangguan. Coba lagi ya.']);
            }

            $result = $response->json();
            $reply  = $result['choices'][0]['message']['content']
                      ?? 'Maaf Kak, tidak ada respons dari HPSBBot.';

            return response()->json(['reply' => trim($reply)]);

        } catch (\Exception $e) {
            Log::error('ChatbotController error: ' . $e->getMessage());
            return response()->json([
                'reply' => 'Debug Exception: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Bangun konteks data dari database untuk dikirim ke Gemini.
     */
    private function buildKonteks(): string
    {
        $lines = [];

        // ── Harga aktif ───────────────────────────────────────────────────────
        $harga = KonfigurasiHarga::where('is_active', true)
            ->latest('berlaku_mulai')
            ->first();

        if ($harga) {
            $lines[] = "=== HARGA AKTIF ===";
            $lines[] = "Harga Beli Gabah : Rp " . number_format($harga->harga_beli_gabah, 0, ',', '.');
            $lines[] = "Harga Jual Beras : Rp " . number_format($harga->harga_jual_beras, 0, ',', '.');
            if ($harga->ongkos_giling) {
                $lines[] = "Ongkos Giling    : Rp " . number_format($harga->ongkos_giling, 0, ',', '.');
            }
            if ($harga->rasio_konversi) {
                $lines[] = "Rasio Konversi   : " . $harga->rasio_konversi;
            }
            $lines[] = "Berlaku Mulai    : " . optional($harga->berlaku_mulai)->format('d M Y');
        } else {
            $lines[] = "=== HARGA === Belum ada konfigurasi harga aktif.";
        }

        // ── Total Stok Saat Ini ──────────────────────────────────────────────
        $stokBeras = Stok::where('komoditas', 'Beras')
            ->where(function($q) { $q->where('status', 'aktif')->orWhereNull('status'); })
            ->latest('tanggal_update')
            ->value('jumlah_stok') ?: 0;

        $stokGabah = Stok::where('komoditas', 'Gabah')
            ->where(function($q) { $q->where('status', 'aktif')->orWhereNull('status'); })
            ->latest('tanggal_update')
            ->value('jumlah_stok') ?: 0;

        $lines[] = "";
        $lines[] = "=== TOTAL STOK SAAT INI ===";
        $lines[] = "Stok Beras : " . number_format($stokBeras, 0, ',', '.') . " kg";
        $lines[] = "Stok Gabah : " . number_format($stokGabah, 0, ',', '.') . " kg";

        // ── Stok terbaru (5 transaksi terakhir) ──────────────────────────────
        $stoks = Stok::latest('tanggal_update')->take(5)->get();

        if ($stoks->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "=== 5 TRANSAKSI TERAKHIR ===";
            foreach ($stoks as $s) {
                $tgl    = optional($s->tanggal_update)->format('d M Y') ?? '-';
                $jumlah = number_format($s->jumlah, 0, ',', '.'); // gunakan jumlah transaksinya saja
                $jenis  = $s->jenis_transaksi ?? '-';
                $lines[] = "- [{$tgl}] {$s->komoditas}: {$jumlah} kg ({$jenis})";
            }
        } else {
            $lines[] = "";
            $lines[] = "=== TRANSAKSI === Belum ada data transaksi.";
        }

        return implode("\n", $lines);
    }
}
