<?php

namespace App\Entity;

enum Role: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_STUDENT = 'ROLE_STUDENT';
    case ROLE_TEACHER = 'ROLE_TEACHER';
    case ROLE_ADMIN = 'ROLE_ADMIN';

    public static function getAvailableRoles()
    {
        return [
            self::ROLE_USER->value => "Utilisateur",
            self::ROLE_STUDENT->value => "Étudiant",
            self::ROLE_TEACHER->value => "Enseignant",
            self::ROLE_ADMIN->value => "Administrateur",
        ];
    }
}