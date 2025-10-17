<?php

namespace App\Repositories\Contracts;

interface AppointmentRepositoryInterface
{
    /**
     * Get all appointments for a specific account
     */
    public function listByAccount(int $accountId, array $filters = []);

    /**
     * Find a single appointment belonging to a specific account
     */
    public function findByAccount(int $accountId, int $id);

    /**
     * Create a new appointment for a specific account
     */
    public function createForAccount(int $accountId, array $data);

    /**
     * Update an existing appointment for a specific account
     */
    public function updateForAccount(int $accountId, int $id, array $data);

    /**
     * Soft delete an appointment for a specific account
     */
    public function softDeleteByAccount(int $accountId, int $id);
}
