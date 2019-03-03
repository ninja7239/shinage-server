<?php
declare(strict_types=1);

/*
 * Licensed under MIT. See file /LICENSE.
 */

namespace App\Security;

use App\Entity\User;

interface LoggedInUserRepositoryInterface
{
    public function getLoggedInUserOrDenyAccess(): User;
}
