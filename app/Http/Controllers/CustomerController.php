<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index() { return view('coming-soon', ['module' => 'CustomerController']); }
    public function create() { return view('coming-soon', ['module' => 'CustomerController']); }
    public function store(Request $request) { return redirect()->back(); }
    public function show($id) { return view('coming-soon', ['module' => 'CustomerController']); }
    public function edit($id) { return view('coming-soon', ['module' => 'CustomerController']); }
    public function update(Request $request, $id) { return redirect()->back(); }
    public function destroy($id) { return redirect()->back(); }
}
