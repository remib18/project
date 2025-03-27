<?php

namespace App\Entity;

enum Role: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_STUDENT = 'ROLE_STUDENT';
    case ROLE_TEACHER = 'ROLE_TEACHER';
    case ROLE_ADMIN = 'ROLE_ADMIN';
}