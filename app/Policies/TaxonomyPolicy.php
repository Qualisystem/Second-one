<?php

namespace App\Policies;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Models\User;

class TaxonomyPolicy
{
    protected string $model = Taxonomy::class;

    public function viewAny(User $user): bool
    {
        return $user->can('List Taxonomy');
    }

    public function view(User $user): bool
    {
        return $user->can('Read Taxonomy');
    }

    public function create(User $user): bool
    {
        return $user->can('Create Taxonomy');
    }

    public function update(User $user): bool
    {
        return $user->can('Update Taxonomy');
    }

    public function delete(User $user): bool
    {
        return $user->can('Delete Taxonomy');
    }
}
