<?php

namespace App\Services;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    /**
     * Get all categories.
     */
    public function getAllCategories(): Collection
    {
        return $this->categoryRepository->getAll();
    }

    /**
     * Get paginated categories.
     */
    public function getPaginatedCategories(int $perPage = 15)
    {
        return $this->categoryRepository->getPaginated($perPage);
    }

    /**
     * Search categories by name.
     */
    public function searchCategories(string $keyword): Collection
    {
        return $this->categoryRepository->searchByName($keyword);
    }

    /**
     * Find category by ID.
     */
    public function findCategory(int $id): ?Model
    {
        return $this->categoryRepository->findById($id);
    }

    /**
     * Create a new category.
     */
    public function createCategory(array $data): Model
    {
        return $this->categoryRepository->create($data);
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(int $id, array $data): Model
    {
        return $this->categoryRepository->update($id, $data);
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(int $id): bool
    {
        return $this->categoryRepository->delete($id);
    }

    /**
     * Count total categories.
     */
    public function countCategories(): int
    {
        return $this->categoryRepository->count();
    }
}
