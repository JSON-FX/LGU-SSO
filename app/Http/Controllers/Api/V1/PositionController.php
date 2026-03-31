<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Position;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::where('is_active', true)->orderBy('title')->get();

        return response()->json(['data' => $positions]);
    }
}
