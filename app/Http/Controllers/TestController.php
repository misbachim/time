<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Class for handling announcement process
 */
class TestController extends Controller
{
    /**
     * Always return 200, for check purpose only.
     */
    public function live()
    {
        return response()->json(['status'=>200]);
    }

}
