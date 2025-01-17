<?php

return [
    'ability' => [
        'fields' => [
            'created_at'        => 'Yaradıldı',
            'created_at_helper' => '',
            'deleted_at'        => 'Deleted at',
            'deleted_at_helper' => '',
            'id'                => 'ID',
            'id_helper'         => '',
            'name'              => 'Name',
            'name_helper'       => '',
            'updated_at'        => 'Updated at',
            'updated_at_helper' => '',
        ],
        'title'          => 'Abilities',
        'title_singular' => 'Ability',
    ],
    'role' => [
        'fields' => [
            'abilities'         => 'Abilities',
            'abilities_helper'  => '',
            'created_at'        => 'Created at',
            'created_at_helper' => '',
            'deleted_at'        => 'Deleted at',
            'deleted_at_helper' => '',
            'id'                => 'ID',
            'id_helper'         => '',
            'name'              => 'Name',
            'name_helper'       => '',
            'updated_at'        => 'Updated at',
            'updated_at_helper' => '',
        ],
        'title'          => 'Roles',
        'title_singular' => 'Role',
    ],
    'user' => [
        'fields' => [
            'created_at'               => 'Created at',
            'created_at_helper'        => '',
            'deleted_at'               => 'Deleted at',
            'deleted_at_helper'        => '',
            'email'                    => 'Email',
            'email_helper'             => '',
            'email_verified_at'        => 'Email verified at',
            'email_verified_at_helper' => '',
            'id'                       => 'ID',
            'id_helper'                => '',
            'name'                     => 'Name',
            'name_helper'              => '',
            'password'                 => 'Password',
            'password_helper'          => '',
            'remember_token'           => 'Remember Token',
            'remember_token_helper'    => '',
            'roles'                    => 'Roles',
            'roles_helper'             => '',
            'updated_at'               => 'Updated at',
            'updated_at_helper'        => '',
        ],
        'title'          => 'Users',
        'title_singular' => 'User',
    ],
    'userManagement' => [
        'title'          => 'User management',
        'title_singular' => 'User management',
    ],
];
