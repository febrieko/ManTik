<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index() { return view('coming-soon', ['module' => 'BillingController']); }
    public function create() { return view('coming-soon', ['module' => 'BillingController']); }
    public function store(Request $request) { return redirect()->back(); }
    public function show($id) { return view('coming-soon', ['module' => 'BillingController']); }
    public function edit($id) { return view('coming-soon', ['module' => 'BillingController']); }
    public function update(Request $request, $id) { return redirect()->back(); }
    public function destroy($id) { return redirect()->back(); }
}
