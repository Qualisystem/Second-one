<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Str;

// class ApplicationPolicy
// {
//     protected string $model = Application::class;

//     public function viewAny(User $user): bool
//     {
//         return $user->can('List '.Str::plural(class_basename($this->model)));
//     }

//     public function view(User $user): bool
//     {
//         return $user->can('Read '.Str::plural(class_basename($this->model)));
//     }

//     public function create(User $user): bool
//     {
//         return $user->can('Create '.Str::plural(class_basename($this->model)));
//     }

//     public function update(User $user): bool
//     {
//         return $user->can('Update '.Str::plural(class_basename($this->model)));
//     }

//     public function delete(User $user): bool
//     {
//         return $user->can('Delete '.Str::plural(class_basename($this->model)));
//     }
// }

class ApplicationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Application $application): bool
    {
        // Super admin can view all
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Regular users can only view assets from their institution
        return $user->institution_id === $application->institution_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Application $application): bool
    {
        // Super admin can update all
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Regular users can only update assets from their institution
        return $user->institution_id === $application->institution_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Application $application): bool
    {
        // Super admin can delete all
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Regular users can only delete assets from their institution
        return $user->institution_id === $application->institution_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Application $application): bool
    {
        return $this->delete($user, $application);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Application $application): bool
    {
        return $this->delete($user, $application);
    }
}