<?php

namespace App\Repositories\Contracts;

interface CategoryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search categories by name.
     */
    public function searchByName(string $keyword);
}
