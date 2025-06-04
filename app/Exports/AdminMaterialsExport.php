<?php

namespace App\Exports;

use App\Models\AdminMaterial;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;

class AdminMaterialsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function query()
    {
        $query = AdminMaterial::query();
        
        // 应用搜索条件
        if (!empty($this->params['id'])) {
            $query->where('id', $this->params['id']);
        }
        
        if (!empty($this->params['title'])) {
            $query->where('title', 'like', '%'.$this->params['title'].'%');
        }
        
        if (!empty($this->params['class'])) {
            $query->where('class', $this->params['class']);
        }
        
        if (!empty($this->params['sub_class'])) {
            $query->where('sub_class', $this->params['sub_class']);
        }
        
        // 产品ID过滤
        if (!empty($this->params['product_id'])) {
            $query->where('product_id', $this->params['product_id']);
        }
        
        // 应用排序
        $sortField = $this->params['sort_field'] ?? 'created_at';
        $sortOrder = $this->params['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);
        
        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            '标题',
            '分类',
            '子分类',
            '视频链接',
            '创建人',
            '添加时间'
        ];
    }

    public function map($material): array
    {
        // 映射分类名称
        $classMap = [
            1 => '营销内容',
            2 => '痛点/症状',
            3 => '产品背书',
            4 => '引导购买'
        ];
        
        $subClassMap = [
            11 => 'A1-营销内容',
            12 => 'A2-价格营销',
            14 => 'A4-营销内容-合规',
            15 => 'A5-价格营销-合规',
            16 => 'A6-旧素材混剪',
            21 => 'B1-症状代入',
            22 => 'B2-疾病科普',
            23 => 'B3-病理',
            26 => 'B6-旧素材混剪',
            31 => 'C1-产品相关',
            36 => 'C6-旧素材混剪',
            41 => 'D1-价格优惠',
            42 => 'D2-厂家直发',
            43 => 'D3-厂家活动',
            44 => 'D6-旧素材混剪'
        ];
        
        return [
            $material->id,
            $material->title,
            $classMap[$material->class] ?? $material->class,
            $subClassMap[$material->sub_class] ?? $material->sub_class,
            $material->url,
            $material->name,
            $material->created_at->format('Y-m-d H:i:s')
        ];
    }
}