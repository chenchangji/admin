<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;

class AdminMaterialRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'title' => 'required',
            'class' => 'int',
            'sub_class' => 'int',
            'actor_ids' => 'array',
            'url' => 'string',
            'desc' => 'string|nullable',
            'screen_type' => 'required|int',
            'product_id'=> 'required|int',
            'product_format'=> 'int|nullable',
            'tag'=> 'int|nullable',

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
        ];
    }
}
