<?php

namespace App\Helpers;

use App\Models\Freelance\Currencies_exchange;
use App\Models\Freelance\Currency;
use App\Models\ShopGovernorate;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartHelper
{
    protected static $instance = null;

    public static function instance(): ?CartHelper
    {
        if (self::$instance === null) {
            self::$instance = new CartHelper();
        }
        return self::$instance;
    }

    public static function cart_session()
    {
        $cart = request()->cookie('cart_session');
        if (empty($cart)) {
            $o = Str::random(20);
            Cookie::queue('cart_session', $o, 24 * 60 * 60 * 356);
            $cart = $o;
        }
        return $cart;
    }

    public static function UserCart()
    {
        return \Cart::session(self::cart_session());
    }

    public static function getVendors()
    {
        $vendors = [];
        foreach (\App\Helpers\CartHelper::UserCart()->getContent() as $content) {
            if (isset($content['attributes']['vendor'])){
                $vendors[] = $content['attributes']['vendor'];
            }
        }
        return array_unique($vendors);
    }


    public static function GetDelivaryCost($selected_gov)
    {
        $costs = 0;
        $all_govs = ShopGovernorate::query()->where('governorate_name_ar', $selected_gov)->whereIn('vendor', CartHelper::getVendors())->get();
        foreach ($all_govs as $item) {
            $costs += $item['delivery'];
        }
        return $costs;
    }


    public static function GetGovernorates()
    {
        $govs = [];
        $all_govs = ShopGovernorate::query()->whereIn('vendor', CartHelper::getVendors())->where('unavailable', '0')->get();
        foreach ($all_govs as $item) {
            if (mb_strlen( $item['governorate_name_ar']) > 3){
                $govs[] = $item['governorate_name_ar'];
            }
        }
        return array_unique($govs);
    }

    public static function GetCities($selected_gov)
    {
        $cities = [];
        $all_govs = ShopGovernorate::query()->where('governorate_name_ar', $selected_gov)->whereIn('vendor', CartHelper::getVendors())->get();
        foreach ($all_govs as $item) {
            foreach ($item->cities as $city) {
                $cities[] = $city->city_name_ar;
            }
        }
        return array_unique($cities);
    }
}
