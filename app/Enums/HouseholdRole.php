<?php

namespace App\Enums;

enum HouseholdRole: string
{
    case Owner = 'owner';
    case Administrator = 'administrator';
    case Contributor = 'contributor';
    case Viewer = 'viewer';
    case Restricted = 'restricted';
}
