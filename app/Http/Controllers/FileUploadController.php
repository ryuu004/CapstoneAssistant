<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,heic,heif,txt,pdf,docx|max:4096',
        ]);

        $file = $request->file('file');
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        
        // Store the file in a temporary directory
        $file->storeAs('tmp', $fileName);

        return response()->json(['fileId' => $fileName]);
    }
}