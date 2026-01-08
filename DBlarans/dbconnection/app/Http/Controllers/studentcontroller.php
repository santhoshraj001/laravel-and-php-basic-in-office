<?php

namespace App\Http\Controllers;
use App\Models\Student;

use Illuminate\Http\Request;

class studentcontroller extends Controller
{
    //
    public function create()
    {
        return view('student_form');
    }
    public function store(Request $request)
    {
        Student::create([
            'name'  => $request->name,
            'email' => $request->email,
            'mobile'   => $request->mobile,
            'age'   => $request->age,
        ]);

        return back()->with('success', 'Student saved successfully');
    }
}
