<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;

class WaterImageRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'title' => 'required',
            'url' => 'required',
        ];

        if ($this->isMethod('put')) {
            $rules = Arr::only($rules, $this->keys());
        }

        return $rules;
    }

    public function messages()
    {
        return [
            //
        ];
    }

    public function attributes()
    {
        return [
            'title' => '标题',
            'url'   => '图片',
        ];
    }
}
