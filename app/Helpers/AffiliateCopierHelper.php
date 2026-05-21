<?php

namespace App\Helpers;

use App\Models\ShopGovernorate;
use App\Models\User;
use App\Models\Finance\Currency;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;
use Asantibanez\LivewireCharts\Models\LineChartModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AffiliateCopierHelper
{
    public static function shreiq()
    {
        return 'https://shreiq.com';
    }

    public static function Elkabier()
    {
        return 'https://elkabier.com';
    }

    public static function Sindbad()
    {
        return 'https://sindbad-aff.com';
    }
    public static function MiniFatora()
    {
        return 'https://minifatora.net';
    }

    public static function fetch_products($host)
    {
        $response = Http::get(rtrim($host, '/') . '/api/musoftware/products');
        return $response->json();
    }

    public static function make_order($vendor, $data)
    {
        $data['order_id'] = uniqid();
        if ($vendor == 'https://elkabier.com'){
            $data['api_key'] = '45c1f7ca4e95f1eaa332d179a2cd7d4bf61d1b30';
        }
        if ($vendor == 'https://shreiq.com'){
            $data['api_key'] = 'bfa67c5191cbcd248530470e1c6aed0a4ba5ec84';
        }
        if ($vendor == 'https://sindbad-aff.com'){
            $data['api_key'] = 'd2c515cba75852149f195db07cba6dfd8b5322af';
        }
        if ($vendor == 'https://minifatora.net'){
            $data['api_key'] = '1071ecc85dad105277a2c1a9c226f0f58b0738e6';
        }
        $response = Http::asJson()->post(rtrim($vendor, '/') . '/api/musoftware/orders/createX', $data);
        return $response->json();
    }

    public static function fetch_governorates($host)
    {
        $response = Http::get(rtrim($host, '/') . '/api/musoftware/governorates');
        return $response->json();
    }

    public static function fetch_cities($host)
    {
        $response = Http::get(rtrim($host, '/') . '/api/musoftware/cities');
        return $response->json();
    }


    public static function fix_product_name($product)
    {
        if ($product->product_name == '#NAME?') {
            $product->product_name = explode("\n", $product->product_description)[0];
            $product->product_name = str_replace('مميزات', '', $product->product_name);
            $product->product_name = strip_tags(trim($product->product_name));
        }
    }




}
