<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CSPReportController extends Controller
{
        public function report(Request $request)
    {
        // Log or handle the CSP violation report
        \Log::info('CSP Report:', $request->all());

        return response()->json(['status' => 'ok']);
    }
}
