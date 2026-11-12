<?php

namespace Modules\AppBuilder\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The customer's details for buying the feasibility study: the same as for the price, with no share to pick. */
class StudyPayRequest extends FormRequest
{
    public function rules(): array
    {
        return array_diff_key((new PayRequest)->rules(), ['share' => true]);
    }
}
