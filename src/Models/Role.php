<?php

namespace Mashy\Laraccess\Models;

use DB;
use Illuminate\Support\Collection;
use Mashy\Laraccess\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Mashy\Laraccess\Exceptions\RoleDoesNotExist;
use Mashy\Laraccess\Contracts\Role as RoleContract;

class Role extends Model implements RoleContract
{

    //Set the correct database connection (see more at Config\Database.php)
    protected $connection = 'user';
    
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

        $this->setTable(config('laravel-permission.table_names.roles'));
    }

    /**
     * A role may be assigned to various users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(
            config('auth.model') ?: config('auth.providers.users.model'),
            config('laravel-permission.table_names.user_has_roles')
        );
    }

    /**
     * Get all the roles which the parent inherits
     *
     * @return \Illuminate\Support\Collection return a collection of roles
     **/
    public function childRoles()
    {
        return $this->belongsToMany(
            config('laravel-permission.models.role'),
            config('laravel-permission.table_names.role_inherits'),
            'parent_id',
            'child_id'
        )->get();
    }

    /**
     * Adds a child role to the parent.
     *
     * @param string|\Mashy\Permission\Models\Role $role
     * @return \Mashy\Permission\Contracts\Role
     */
    public function assignChild($role)
    {
        $config = config('laravel-permission.table_names');
        $childrole = static::findByName($role);
        $childid = $childrole->id;

        $parentid = $this->id;

        DB::connection('user')->table($config['role_inherits'])->insert([
            'parent_id' => $parentid,
            'child_id' => $childid
        ]);
    }

    /**
     * Find a role by its name.
     *
     * @param string $name
     * @return Role
     * @throws RoleDoesNotExist
     */
    public static function findByName($name)
    {
        $role = static::where('name', $name)->first();

        if (! $role) {
            throw new RoleDoesNotExist();
        }

        return $role;
    }
}
