<?php

namespace Toh\Statistical\Http\Controllers;

use App\Http\Controllers\Controller;
use Toh\Statistical\Models\Statistical;

class StatisticalController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex()
    {
        dd(101010101);
        Statistical::create(['name' => 'Statistical ' . time()]);
        return view('toh-statistical::index');
    }
}