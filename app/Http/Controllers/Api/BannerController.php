<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\MobileTrait;
use App\Models\Banner;

class BannerController extends Controller
{
    use MobileTrait;

    public function index()
    {
        $banners = Banner::whereDate('valid_to', '>=', now()->format('Y-m-d'))->orderBy('id', 'DESC')->get();

        return $this->data($banners);
    }

}
