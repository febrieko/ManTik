<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IpController extends Controller
{
    public function index() { return view('coming-soon', ['module' => 'IpController']); }
    public function create() { return view('coming-soon', ['module' => 'IpController']); }
    public function store(Request $request) { return redirect()->back(); }
    public function show($id) { return view('coming-soon', ['module' => 'IpController']); }
    public function edit($id) { return view('coming-soon', ['module' => 'IpController']); }
    public function update(Request $request, $id) { return redirect()->back(); }
    public function destroy($id) { return redirect()->back(); }
}
