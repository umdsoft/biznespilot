<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'what_you_learn' => 'nullable|string',
            'requirements' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:store_categories,id',
            'duration_hours' => 'nullable|integer|min:1',
            'level' => 'nullable|string|in:beginner,intermediate,advanced,all_levels',
            'instructor' => 'nullable|string|max:255',
            'instructor_photo' => 'nullable|string|max:2048',
            'max_students' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'format' => 'nullable|string|in:online,offline,hybrid',
            'certificate_included' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'image_url' => 'nullable|string|max:2048',
            'sort_order' => 'nullable|integer|min:0',
            'metadata' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Kurs nomi kiritilishi shart',
            'name.max' => 'Kurs nomi 255 belgidan oshmasligi kerak',
            'price.required' => 'Narx kiritilishi shart',
            'price.numeric' => 'Narx raqam bo\'lishi kerak',
            'price.min' => 'Narx 0 dan kam bo\'lmasligi kerak',
            'compare_price.numeric' => 'Taqqoslash narxi raqam bo\'lishi kerak',
            'compare_price.min' => 'Taqqoslash narxi 0 dan kam bo\'lmasligi kerak',
            'category_id.exists' => 'Tanlangan kategoriya topilmadi',
            'duration_hours.integer' => 'Davomiyligi butun son bo\'lishi kerak',
            'duration_hours.min' => 'Davomiyligi kamida 1 soat bo\'lishi kerak',
            'level.in' => 'Daraja quyidagilardan biri bo\'lishi kerak: beginner, intermediate, advanced, all_levels',
            'max_students.integer' => 'Maksimal talabalar soni butun son bo\'lishi kerak',
            'max_students.min' => 'Maksimal talabalar soni kamida 1 bo\'lishi kerak',
            'end_date.after_or_equal' => 'Tugash sanasi boshlanish sanasidan oldin bo\'lmasligi kerak',
            'format.in' => 'Format quyidagilardan biri bo\'lishi kerak: online, offline, hybrid',
        ];
    }
}
