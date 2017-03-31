<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laraccess Models
    |--------------------------------------------------------------------------
    */
    'models' => [
        /*
        |--------------------------------------------------------------------------
        | Role Model
        |--------------------------------------------------------------------------
        */
        'role' => Mashy\Laraccess\Models\Role::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Laraccess Tables
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

        //Role wildcard inherits
        'role_wildcards' => 'role_wildcards'

    ],

];
