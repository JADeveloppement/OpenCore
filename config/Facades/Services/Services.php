<?php

/**
 * Nom du Fichier : Controllers.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Services;

use Config\Facades\Models\Models;

class Services
{
    protected Models $model;

    public function __construct(Models $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->getAll();
    }

    public function findById(int $id)
    {
        return $this->model->findById($id);
    }

    public function where(string $col, string $val, string $groupBy = '', string $orderBy = '')
    {
        return $this->model->where($col, $val, $groupBy, $orderBy);
    }

    public function getSchemaColumns()
    {
        return $this->model->getSchemaColumns();
    }

    public function filter(array $filters, bool $orderBy = true, string $orderClause = ''): array
    {
        return $this->model->filter($filters, $orderBy);
    }

    public function insert(array $data): int|false
    {
        return $this->model->insert($data);
    }

    public function fetchWithJoins(string $selectClause, string $joinClause, array $whereConditions = [], string $orderClause = ''): array
    {
        return $this->model->fetchWithJoins($selectClause, $joinClause, $whereConditions, $orderClause);
    }

    public function update(array $data): string|int
    {
        return $this->model->update($data);
    }

    public function delete(int $id): int|false
    {
        return $this->model->delete($id);
    }

    public function deleteByConditions(array $conditions): int|false|string
    {
        return $this->model->deleteByConditions($conditions);
    }

    public function getTotalRowsCount(): int|false
    {
        return $this->model->getTotalRowsCount();
    }
}