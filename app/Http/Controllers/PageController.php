<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function dmca()
    {
        return view('pages.dmca');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function sendContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:2000',
        ]);

        // Di sini bisa ditambahkan logic untuk:
        // 1. Kirim email ke admin
        // 2. Simpan ke database
        // Untuk sekarang, cukup redirect dengan success message

        return back()->with('success', 'Pesan berhasil dikirim! Kami akan merespons dalam 1-2 hari kerja.');
    }
}
