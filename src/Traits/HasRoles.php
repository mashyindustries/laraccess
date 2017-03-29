<?php

namespace Mashy\Laraccess\Traits;

use Illuminate\Support\Collection;
use Mashy\Laraccess\Contracts\Role;
use Mashy\Laraccess\Models\Role as R;
use Mashy\Laraccess\Exceptions\AlreadyAssigned;

trait HasRoles
{
    private $checkedRoles = [];

    /**
     * A user may have multiple roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function rolesWithoutCollection(){
        return $this->belongsToMany(
            config('laravel-permission.models.role'),
            config('laravel-permission.table_names.user_has_roles')
        );
    }

    public function roles(){
        $roles = $this->belongsToMany(
            config('laravel-permission.models.role'),
            config('laravel-permission.table_names.user_has_roles')
        )->get();

        return $this->loopRoles($roles);
    }

    /**
     * Loop through Roles and find it's child roles
     * 
     * @var \Illuminate\Support\Collection $roles
     * @return \Illuminate\Support\Collection $roles
     */
    private function loopRoles($roles){
        foreach($roles as $role){
            if(! in_array($role->name, $this->checkedRoles)){
                array_push($this->checkedRoles, $role->name);
                $newroles = $this->loopRoles($role->childRoles());
                $roles = $roles->merge($newroles);
            }
        }
        return $roles;
    }

    /**
     * A user may have multiple direct permissions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(
            config('laravel-permission.models.permission'),
            config('laravel-permission.table_names.user_has_permissions')
        );
    }

    public function allPermissions(){
        $roles = $this->roles();
        $permissions = $this->permissions()->get();
        foreach ($roles as $role){
            $newperms = $role->permissions()->get();
            $permissions = $permissions->merge($newperms);
        }
        return $permissions;
    }


    /**
     * Scope the user query to certain roles only.
     *
     * @param string|array|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function scopeRole($query, $roles)
    {
        if ($roles instanceof Collection) {
            $roles = $roles->toArray();
        }

        if (! is_array($roles)) {
            $roles = [$roles];
        }

        $roles = array_map(function ($role) {
            if ($role instanceof Role) {
                return $role;
            }

            return app(Role::class)->findByName($role);
        }, $roles);

        return $query->whereHas('roles', function ($query) use ($roles) {
            $query->where(function ($query) use ($roles) {
                foreach ($roles as $role) {
                    $query->orWhere('id', $role->id);
                }
            });
        });
    }

    /**
     * Assign the given role to the user.
     *
     * @param array|string|\Mashy\Permission\Models\Role ...$roles
     *
     * @return \Mashy\Permission\Contracts\Role
     */
    public function assignRole(...$roles)
    {
        try{
            $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                return $this->getStoredRole($role);
            })
            ->all();

            $this->rolesWithoutCollection()->saveMany($roles);

            $this->forgetCachedPermissions();

        }catch(\Exception $e){
            throw new AlreadyAssigned();
        }
        return $this;
    }

    /**
     * Revoke the given role from the user.
     *
     * @param string|Role $role
     */
    public function removeRole($role)
    {
        $this->roles()->detach($this->getStoredRole($role));
    }

    /**
     * Remove all current roles and set the given ones.
     *
     * @param array ...$roles
     *
     * @return $this
     */
    public function syncRoles(...$roles)
    {
        $this->roles()->detach();

        return $this->assignRole($roles);
    }

    /**
     * Determine if the user has (one of) the given role(s).
     *
     * @param string|array|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasRole($roles)
    {
        if (is_string($roles)) {
            return $this->roles()->contains('name', $roles);
        }

        if ($roles instanceof Role) {
            return $this->roles()->contains('id', $roles->id);
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        return (bool) $roles->intersect($this->roles())->count();
    }

    /**
     * Determine if the user has any of the given role(s).
     *
     * @param string|array|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasAnyRole($roles)
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the user has all of the given role(s).
     *
     * @param string|Role|\Illuminate\Support\Collection $roles
     *
     * @return bool
     */
    public function hasAllRoles($roles)
    {
        if (is_string($roles)) {
            return $this->roles()->contains('name', $roles);
        }

        if ($roles instanceof Role) {
            return $this->roles()->contains('id', $roles->id);
        }

        $roles = collect()->make($roles)->map(function ($role) {
            return $role instanceof Role ? $role->name : $role;
        });

        return $roles->intersect($this->roles()->pluck('name')) == $roles;
    }

    /**
     * @param $role
     *
     * @return Role
     */
    protected function getStoredRole($role)
    {
        if (is_string($role)) {
            return app(Role::class)->findByName($role);
        }

        return $role;
    }
}
