<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authorization Models
    |--------------------------------------------------------------------------
    */
    'models' => [
        /*
        |--------------------------------------------------------------------------
        | Role Model
        |--------------------------------------------------------------------------
        |
        | When using the "HasRoles" trait from this package, we need to know which
        | Eloquent model should be used to retrieve your roles. Of course, it
        | is often just the "Role" model but you may use whatever you like.
        */
        'role' => Mashy\Laraccess\Models\Role::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization Tables
    |--------------------------------------------------------------------------
    */

    'table_names' => [

        //Which connection to use for the tables
        'connection' => 'user',

        //User Table Name
        'users' => 'users',

        //Roles Table Name
        'roles' => 'roles',

        //User Has Roles Many-to-Many Table Name
        'user_roles' => 'user_roles',

        //Role inherits Table Name
        'role_inherits' => 'role_inherits',

    ],

];
