<?php

namespace App\Repositories\Contracts;

use Illuminate\Http\Request;

interface ExpenseRepositoryInterface
{
    public function list(Request $request);
    public function find($id);
    public function store(array $data, $user);
    public function update($id, array $data);
    public function delete($id);
}
