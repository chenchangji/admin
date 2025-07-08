<?php

namespace App\Http\Requests;

use Illuminate\Support\Arr;

class AdminTemplateRequest extends FormRequest
{
    public function rules()
    {
        $rules = [
            'title' => 'required',
            'product_id' => 'required',
            'product_format' => 'required',
            'product_tag' => 'int|nullable',
            'screen_type' => 'required',
            'class_rules' => 'required',
            'range' => 'int',
            'is_water_mark'=> 'required',
            'exclude_actor_ids' => 'array',
            'exclude_sub_class' => 'array',
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
            'title' => '模板名称',
            'product_id' => '关联商品',
            'product_format' => '关联商品规格',
            'screen_type' => '横竖屏',
            'class_rules' => '生成规则',
        ];
    }
}
