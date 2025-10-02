<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\LockdownEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractModelPolicy
{
    protected string $prefix;

    protected ?LockdownEnum $lockdownKey = null;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can("{$this->prefix}.viewAny");
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $model): bool
    {
        return $user->can("{$this->prefix}.view");
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isNotLocked() && $user->can("{$this->prefix}.create");
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $model): bool
    {
        return $this->isNotLocked() && $user->can("{$this->prefix}.update");
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $model): bool
    {
        return $this->isNotLocked() && $user->can("{$this->prefix}.delete");
    }

    public function deleteAny(User $user): bool
    {
        return $this->isNotLocked() && $user->can("{$this->prefix}.delete");
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Model $model): bool
    {
        return $this->isNotLocked() && $user->can("{$this->prefix}.restore");
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Model $model): bool
    {
        return $this->isNotLocked() && $user->can("{$this->prefix}.forceDelete");
    }

    /**
     * Determine whether the user can attach a many-to-many relation to the model
     *
     * @param  $relation  The name of the relation according to the $model
     */
    public function attach(User $user, ?Model $model = null, ?string $relation = null): bool
    {
        return $this->isNotLocked() && $user->can("{$this->prefix}.attach");
    }

    /**
     * Determine whether the user can detach a many-to-many relation to the model
     *
     * @param  $relation  The name of the relation according to the $model
     */
    public function detach(User $user, ?Model $model = null, ?string $relation = null): bool
    {
        return $this->isNotLocked() && $user->can("{$this->prefix}.detach");
    }

    public function history(User $user, Model $model): bool
    {
        return $user->can("{$this->prefix}.history");
    }

    /**
     * Audit is a special case, there's one audit policy for all models
     * It calls hasPermissionTo directly to avoid an infinite loop
     */
    public function audit(User $user, Model $model): bool
    {
        return $user->hasPermissionTo('audit');
    }

    /**
     * RestoreAudit is also a special case
     */
    public function restoreAudit(User $user, Model $model): bool
    {
        return $this->isNotLocked() && $user->hasPermissionTo('restoreAudit');
    }

    protected function isNotLocked(): bool
    {
        if ($this->lockdownKey) {
            return ! $this->lockdownKey->isLocked();
        }

        return true;
    }
}
