<?php

namespace Mashy\Laraccess\Traits;

use Illuminate\Support\Collection;
use Mashy\Laraccess\Models\Role;
use Mashy\Laraccess\Exceptions\AlreadyAssigned;

trait HasRoles
{

    protected static $checkedRoles = [];

    /**
     * Return all user roles
     * 
     * @return belongsToMany 
     */
    public function trueRoles(){
        return $this->belongsToMany(
            config('laraccess.models.role'),
            config('laraccess.table_names.user_roles')
        );
    }

    /**
     * Return all user roles - Including inherited roles
     * 
     * @return Illuminate\Support\Collection 
     */
    public function roles(){
        $roles = $this->belongsToMany(
            config('laraccess.models.role'),
            config('laraccess.table_names.user_roles')
        )->get();
        $allRoles = static::loopRoles($roles);
        static::$checkedRoles = [];
        return $allRoles;
    }

    /**
     * Loop through Roles and find it's child roles
     * 
     * @var \Illuminate\Support\Collection $roles
     * @return \Illuminate\Support\Collection $roles
     */
    protected static function loopRoles($roles){
        foreach($roles as $role){
            if(! in_array($role->slug, static::$checkedRoles)){
                array_push(static::$checkedRoles, $role->slug);
                $roles = $roles->merge(static::loopRoles($role->getParentRoles()));
                $roles = $roles->merge(static::loopRoles($role->getRoleWildcardRoles()));
                $roles = $roles->merge(static::loopRoles($role->getInheritedRoles()));
            }
        }
        return $roles;
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

            return app(Role::class)->findBySlug($role);
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
     * @return \Mashy\Laraccess\Models\Role
     */
    public function assignRole(...$roles)
    {
        try{
            $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                return Role::getStoredRole($role);
            })
            ->all();

            $this->trueRoles()->saveMany($roles);

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
            return $this->roles()->contains('slug', $roles);
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
}
