# Laraccess - User Roles & Role Inheritance

Works with Laravel 5.4

This package allows to save roles in a database.
Roles can inherit other roles too.

It includes blade directives & middleware

## Install

You can install the package via composer:
``` bash
$ composer require mashyindustries/laraccess
```

This service provider must be installed.
```php
// config/app.php
'providers' => [
    ...
    Mashy\Laraccess\LaraccessServiceProvider::class,
];
```

You can publish the migration with:
```bash
php artisan vendor:publish --provider="Mashy\Laraccess\LaraccessServiceProvider" --tag="migrations"
```

The package assumes that your users table name is called "users". If this is not the case
you should manually edit the published migration to use your custom table name.

After the migration has been published you can create the role tables with:

```bash
php artisan migrate
```

You can publish the config-file with:
```bash
php artisan vendor:publish --provider="Mashy\Laraccess\LaraccessServiceProvider" --tag="config"
```

## Usage
First add the `Mashy\Laraccess\Traits\HasRoles` trait to your User model.

```php
use Mashy\Laraccess\Traits\HasRoles;

class User
{
    use HasRoles;
    
    // ...
}
```

This package allows for users to be associated with roles. 

You can create roles with:

```php
use Mashy\Laraccess\Models\Role;

$role = Role::create([
    'name' => 'Writer', //optional
    'slug' => 'writer', //required
    'description' => '' //optional
]);
```

The `HasRoles` adds collections to your models.

```php
$roles = $user->roles(); // returns a collection
```

### Using Roles
A role can be assigned to a user:

```php
$user->assignRole('writer');

// you can also assign multiple roles at once
$user->assignRole('writer', 'admin');
$user->assignRole(['writer', 'admin']);
```

A role can be removed from a user:

```php
$user->removeRole('writer');
```

Roles can also be synced :

```php
//all current roles will be removed from the user and replace by the array given
$user->syncRoles(['writer', 'admin']);
```

You can determine if a user has a certain role:

```php
$user->hasRole('writer');
```

You can also determine if a user has any of a given list of roles:
```php
$user->hasAnyRole(['writer', 'admin']);
```
You can also determine if a user has all of a given list of roles:

```php
$user->hasAllRoles(['writer', 'admin']);
```


###Using blade directives
This package also adds Blade directives to verify whether the
currently logged in user has all or any of a given list of roles.

```php
@role('writer')
I'm a writer!
@else
I'm not a writer...
@endrole
```

```php
@hasanyrole(['writer', 'admin'])
I have one or more of these roles!
@else
I have none of these roles...
@endrole
```

```php
@hasallroles(['writer', 'admin'])
I have all of these roles!
@else
I don't have all of these roles...
@endrole
```

You can use Laravel's native `@can` directive to check if a user has a certain permission.

### Using a middleware

Information coming soon...

### Inheritance

Information coming soon...

## Credits

This package was based on [Spatie/Laravel-Permission](https://github.com/spatie/laravel-permission)

## Alternatives

- [JosephSilber/bouncer](https://github.com/JosephSilber/bouncer)
- [BeatSwitch/lock-laravel](https://github.com/BeatSwitch/lock-laravel)
- [Zizaco/entrust](https://github.com/Zizaco/entrust)
- [bican/roles](https://github.com/romanbican/roles)
- [spatie/laravel-permission](https://github.com/spatie/laravel-permission)

