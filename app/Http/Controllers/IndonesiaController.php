<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndonesiaController extends Controller
{
    public function getProvinces(Request $request)
    {
        $q = $request->query('q');
        $data = DB::table('indonesia_provinces')
            ->when($q, fn($query) => $query->where('name', 'like', "%$q%"))
            ->select('id', 'code', 'name')
            ->get();

        return response()->json($data);
    }

    public function getCities(Request $request)
    {
        $q = $request->query('q');
        $provinceCode = $request->query('province_code');

        $data = DB::table('indonesia_cities')
            ->when($provinceCode, fn($query) => $query->where('province_code', $provinceCode))
            ->when($q, fn($query) => $query->where('name', 'like', "%$q%"))
            ->select('id', 'code', 'province_code', 'name')
            ->get();

        return response()->json($data);
    }

    public function getDistricts(Request $request)
    {
        $q = $request->query('q');
        $cityCode = $request->query('city_code');

        $data = DB::table('indonesia_districts')
            ->when($cityCode, fn($query) => $query->where('city_code', $cityCode))
            ->when($q, fn($query) => $query->where('name', 'like', "%$q%"))
            ->select('id', 'code', 'city_code', 'name')
            ->get();

        return response()->json($data);
    }

    public function getVillages(Request $request)
    {
        $q = $request->query('q');
        $districtCode = $request->query('district_code');

        $data = DB::table('indonesia_villages')
            ->when($districtCode, fn($query) => $query->where('district_code', $districtCode))
            ->when($q, fn($query) => $query->where('name', 'like', "%$q%"))
            ->select('id', 'code', 'district_code', 'name')
            ->get();

        return response()->json($data);
    }
}
