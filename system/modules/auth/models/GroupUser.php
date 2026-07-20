<?php
class GroupUser extends DbObject
{
    /** @var int */
    public $group_id;

    /** @var int */
    public $user_id;

    /** @var string */
    public $role;

    /** @var boolean */
    public $is_active;

    public function getGroup()
    {
        return $this->getObject("User", $this->group_id);
    }

    public function getUser()
    {
        return $this->getObject("User", $this->user_id);
    }

    public function getGroupRoles()
    {
        return $this->getGroup()->getRoles();
    }
    /**
     * Store parents group_user obj into $_SESSION['parents'],
     * be sure to perform clean up actions after execute this function;
     **/
    public function getParents()
    {
        $_SESSION['parents'][] = $this->group_id;
        $parent = $this->getGroup();
        $groupUsers = $parent->isInGroups();

        if ($groupUsers) {
            foreach ($groupUsers as $groupUser) {
                $groupUser->getParents();
            }
        }
    }
}
