<?php

namespace App\Repositories;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    /**
     * Search categories by name.
     */
    public function searchByName(string $keyword): Collection
    {
        return $this->model->where('name', 'like', "%{$keyword}%")->get();
    }
}
