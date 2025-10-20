<?php

namespace App\Repositories\Contracts;

interface ServiceRepositoryInterface
{
    public function paginate(array $filters = []);
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
