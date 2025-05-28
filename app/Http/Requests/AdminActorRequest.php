<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;

class AdminActorRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'name' => 'required',
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
            'name' => '字段名',
        ];
    }
}
