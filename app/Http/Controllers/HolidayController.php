<?php

namespace App\Http\Controllers;

use App\Models\PublicHoliday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = PublicHoliday::orderBy('holiday_date', 'desc')->get();

        return view('holidays.index', compact('holidays'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'holiday_date' => 'required|date',
        ]);

        PublicHoliday::create($request->only('name', 'holiday_date', 'is_recurring'));

        return redirect()->route('holidays.index')->with('success', 'Holiday added.');
    }

    public function destroy(PublicHoliday $holiday)
    {
        $holiday->delete();

        return redirect()->route('holidays.index')->with('success', 'Holiday deleted.');
    }
}
