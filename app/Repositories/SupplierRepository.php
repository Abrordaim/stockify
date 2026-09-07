<?php

namespace App\Repositories;

use App\Models\Supplier;
use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository extends BaseRepository implements SupplierRepositoryInterface
{
    public function __construct(Supplier $model)
    {
        parent::__construct($model);
    }

    /**
     * Search suppliers by name.
     */
    public function searchByName(string $keyword): Collection
    {
        return $this->model->where('name', 'like', "%{$keyword}%")->get();
    }
}
