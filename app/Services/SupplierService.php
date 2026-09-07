<?php

namespace App\Services;

use App\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SupplierService
{
    public function __construct(
        protected SupplierRepositoryInterface $supplierRepository
    ) {}

    /**
     * Get all suppliers.
     */
    public function getAllSuppliers(): Collection
    {
        return $this->supplierRepository->getAll();
    }

    /**
     * Get paginated suppliers.
     */
    public function getPaginatedSuppliers(int $perPage = 15)
    {
        return $this->supplierRepository->getPaginated($perPage);
    }

    /**
     * Search suppliers by name.
     */
    public function searchSuppliers(string $keyword): Collection
    {
        return $this->supplierRepository->searchByName($keyword);
    }

    /**
     * Find supplier by ID.
     */
    public function findSupplier(int $id): ?Model
    {
        return $this->supplierRepository->findById($id);
    }

    /**
     * Create a new supplier.
     */
    public function createSupplier(array $data): Model
    {
        return $this->supplierRepository->create($data);
    }

    /**
     * Update an existing supplier.
     */
    public function updateSupplier(int $id, array $data): Model
    {
        return $this->supplierRepository->update($id, $data);
    }

    /**
     * Delete a supplier.
     */
    public function deleteSupplier(int $id): bool
    {
        return $this->supplierRepository->delete($id);
    }

    /**
     * Count total suppliers.
     */
    public function countSuppliers(): int
    {
        return $this->supplierRepository->count();
    }
}
