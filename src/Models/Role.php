<?php

namespace Mashy\Laraccess\Models;

use DB;
use Illuminate\Support\Collection;
use Mashy\Laraccess\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Mashy\Laraccess\Exceptions\RoleDoesNotExist;

class Role extends Model
{

    //Set the correct database connection (see more at Config\Database.php)
    protected $connection = 'user';

    //disable timestamps
    public $timestamps = false;

    
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    public $guarded = ['id'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('laraccess.table_names.roles'));
    }

    /**
     * A role may be assigned to various users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            config('auth.providers.users.model'),
            config('laraccess.table_names.user_roles')
        );
    }

    /**
     * Get all the roles which the parent inherits
     *
     * @return \Illuminate\Support\Collection
     **/
    public function getInheritedRoles()
    {
        return $this->belongsToMany(
            config('laraccess.models.role'),
            config('laraccess.table_names.role_inherits'),
            'parent_role_id',
            'child_role_id'
        )->get();
    }

    /**
     * Get's the roles parents
     **/
    public function getParentRoles()
    {
        $slugs = explode('.', $this->slug);
        $roles = collect();
        foreach ($slugs as $slug){
            $roles = $roles->push(static::where('slug', $slug)->first());
        }
        return $roles;
    }

    /**
     * Get all the roles wildcard roles
     *
     * @param Role
     **/
    public function getRoleWildcardRoles()
    {
        $config = config('laraccess.table_names');

        $wildcards = DB::connection($config['connection'])
            ->table($config['role_wildcards'])
            ->where('parent_role_id', $this->id)
            ->get();

        $roles = collect();
        foreach($wildcards as $wildcard){
            $wildcardString = $wildcard->wildcard;
            if(substr($wildcardString, -1) == "*"){
                $roles = $roles->merge(static::where('slug', 'like', substr($wildcardString, 0, -1)."%")->get());
            }
        }

        return $roles;
    }

    /**
     * Assign a child role to the role
     *
     * @param string|Role $role
     **/
    public function assignRole($role)
    {
        $config = config('laraccess.table_names');

        if (is_string($role)) {
            if (substr($role, -1) == "*"){
                DB::connection($config['connection'])->table($config['role_wildcards'])->insert([
                    'parent_role_id' => $this->id,
                    'wildcard' => $role
                ]);
            }else{
                $role = static::findBySlug($role);
            }
        }else{
            DB::connection($config['connection'])->table($config['role_inherits'])->insert([
                'parent_role_id' => $this->id,
                'child_role_id' => $role->id
            ]);
        }
    }

    /**
     * Assign a child role to the role
     *
     * @param string|Role $role
     **/
    public function detachRole($role)
    {
        $config = config('laraccess.table_names');

        if (is_string($role)) {
            if (substr($role, -1) == "*"){
                DB::connection($config['connection'])
                    ->table($config['role_wildcards'])
                    ->where('parent_role_id', $this->id)
                    ->where('wildcard', $role)
                    ->delete();
            }
            $role = static::findBySlug($role);
        }

        DB::connection($config['connection'])
            ->table($config['role_inherits'])
            ->where('parent_role_id', $this->id)
            ->where('child_role_id', $role->id)
            ->delete();
    }


    /**
     * Find a role by its slug.
     *
     * @param string $slug
     * @return Role
     * @throws RoleDoesNotExist
     */
    public static function findBySlug($slug)
    {
        $role = static::where('slug', $slug)->first();

        if (! $role) {
            throw new RoleDoesNotExist();
        }

        return $role;
    }

    /**
     * @param $role
     *
     * @return Role
     */
    protected static function getStoredRole($role)
    {
        if (is_string($role)) {
            return static::findBySlug($role);
        }

        return $role;
    }

}
