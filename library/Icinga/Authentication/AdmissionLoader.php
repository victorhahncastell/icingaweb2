<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}}

namespace Icinga\Authentication;

use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Exception\NotReadableError;
use Icinga\Data\ConfigObject;
use Icinga\User;
use Icinga\Util\String;

/**
 * Retrieve restrictions and permissions for users
 */
class AdmissionLoader
{
    /**
     * @param   string          $username
     * @param   array           $userGroups
     * @param   ConfigObject    $section
     *
     * @return  bool
     */
    protected function match($username, $userGroups, ConfigObject $section)
    {
        $username = strtolower($username);
        if (! empty($section->users)) {
            $users = array_map('strtolower', String::trimSplit($section->users));
            if (in_array($username, $users)) {
                return true;
            }
        }
        if (! empty($section->groups)) {
            $groups = array_map('strtolower', String::trimSplit($section->groups));
            foreach ($userGroups as $userGroup) {
                if (in_array(strtolower($userGroup), $groups)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get user permissions and restrictions
     *
     * @param   User $user
     *
     * @return  array
     */
    public function getPermissionsAndRestrictions(User $user)
    {
        $permissions = array();
        $restrictions = array();
        $username = $user->getUsername();
        try {
            $roles = Config::app('roles');
        } catch (NotReadableError $e) {
            Logger::error(
                'Can\'t get permissions and restrictions for user \'%s\'. An exception was thrown:',
                $username,
                $e
            );
            return array($permissions, $restrictions);
        }
        $userGroups = $user->getGroups();
        foreach ($roles as $role) {
            if ($this->match($username, $userGroups, $role)) {
                $permissions = array_merge(
                    $permissions,
                    array_diff(String::trimSplit($role->permissions), $permissions)
                );
                $restrictionsFromRole = $role->toArray();
                unset($restrictionsFromRole['users']);
                unset($restrictionsFromRole['groups']);
                unset($restrictionsFromRole['permissions']);
                foreach ($restrictionsFromRole as $name => $restriction) {
                    if (! isset($restrictions[$name])) {
                        $restrictions[$name] = array();
                    }
                    $restrictions[$name][] = $restriction;
                }
            }
        }
        return array($permissions, $restrictions);
    }

    /**
     * Get user permissions
     *
     * @param   User  $user
     *
     * @return  array
     */
    public function getPermissions(User $user)
    {
        $permissions = array();
        try {
            $config = Config::app('permissions');
        } catch (NotReadableError $e) {
            Logger::error(
                'Can\'t get permissions for user \'%s\'. An exception was thrown:',
                $user->getUsername(),
                $e
            );
            return $permissions;
        }
        $username = $user->getUsername();
        $userGroups = $user->getGroups();
        foreach ($config as $section) {
            if (! empty($section->permissions)
                && $this->match($username, $userGroups, $section)
            ) {
                $permissions = array_merge(
                    $permissions,
                    array_diff(String::trimSplit($section->permissions), $permissions)
                );
            }
        }
        return $permissions;
    }

    /**
     * Get user restrictions
     *
     * @param   User  $user
     *
     * @return  array
     */
    public function getRestrictions(User $user)
    {
        $restrictions = array();
        try {
            $config = Config::app('restrictions');
        } catch (NotReadableError $e) {
            Logger::error(
                'Can\'t get restrictions for user \'%s\'. An exception was thrown:',
                $user->getUsername(),
                $e
            );
            return $restrictions;
        }
        $username = $user->getUsername();
        $userGroups = $user->getGroups();
        foreach ($config as $section) {
            if (! empty($section->restriction)
                && $this->match($username, $userGroups, $section)
            ) {
                $restrictions = array_merge(
                    $restrictions,
                    array_diff(String::trimSplit($section->restriction), $restrictions)
                );
            }
        }
        return $restrictions;
    }
}
