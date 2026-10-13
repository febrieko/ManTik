<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index() { return view('coming-soon', ['module' => 'SettingController']); }
    public function create() { return view('coming-soon', ['module' => 'SettingController']); }
    public function store(Request $request) { return redirect()->back(); }
    public function show($id) { return view('coming-soon', ['module' => 'SettingController']); }
    public function edit($id) { return view('coming-soon', ['module' => 'SettingController']); }
    public function update(Request $request, $id) { return redirect()->back(); }
    public function destroy($id) { return redirect()->back(); }
}
