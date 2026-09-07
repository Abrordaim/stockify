<?php

namespace App\Repositories\Contracts;

interface SupplierRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search suppliers by name.
     */
    public function searchByName(string $keyword);
}
