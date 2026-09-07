<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records.
     */
    public function getAll(): Collection;

    /**
     * Get paginated records.
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a record by ID.
     */
    public function findById(int $id): ?Model;

    /**
     * Find a record by ID or throw exception.
     */
    public function findOrFail(int $id): Model;

    /**
     * Create a new record.
     */
    public function create(array $data): Model;

    /**
     * Update an existing record.
     */
    public function update(int $id, array $data): Model;

    /**
     * Delete a record by ID.
     */
    public function delete(int $id): bool;

    /**
     * Count all records.
     */
    public function count(): int;
}
