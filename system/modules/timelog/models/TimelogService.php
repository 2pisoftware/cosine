<?php

/**
 * This service class aids in the registration and usage of timelog objects
 *
 * @author Adam Buckley <adam@2pisoftware.com>
 */
class TimelogService extends DbService
{
    private mixed $_trackObject = null;

    /**
     * Returns all time logs for a given user
     *
     * @param User $user
     * @param boolean $includeDeleted
     * @return Timelog[]
     */
    public function getTimelogsForUser(User|null $user = null, $includeDeleted = false, $page = 1, $page_size = 20)
    {
        if ($user === null) {
            $user = AuthService::getInstance($this->w)->user();
        }

        $where = ['user_id' => $user->id];
        if (!$includeDeleted) {
            $where['is_deleted'] = 0;
        }

        return $this->getObjects("Timelog", $where, false, true, "dt_start DESC", ($page - 1) * $page_size, $page_size);
    }

    /**
     * Returns all time logs for a given user
     *
     * @param User $user
     * @param boolean $includeDeleted
     * @return Timelog[]
     */
    public function getTimelogsForUserAndClass(User $user, $className, $includeDeleted = false, $dt_start = null, $dt_end = null, $page = null, $page_size = null)
    {

        $where['user_id'] = $user->id;
        $where['object_class'] = $className;

        if (!$includeDeleted) {
            $where['is_deleted'] = 0;
        }

        if (!empty($dt_start)) {
            $where["dt_start >= ?"] = formatDateTime($dt_start, "Y-m-d H:i:s");
        }
        if (!empty($dt_end)) {
            $where["dt_end <= ?"] = formatDateTime($dt_end, "Y-m-d H:i:s");
        }

        return $this->getObjects("Timelog", $where, false, true, "dt_start DESC", ($page - 1) * $page_size, $page_size);
    }


    public function countTotalTimelogsForUser(User|null $user = null, $includeDeleted = false)
    {
        if ($user === null) {
            $user = AuthService::getInstance($this->w)->user();
        }

        $where = ['user_id' => $user->id];
        if (!$includeDeleted) {
            $where['is_deleted'] = 0;
        }

        return $this->_db->get("timelog")->where($where)->count();
    }

    public function getTimelogsForObject($object)
    {
        if (!empty($object->id)) {
            return $this->getObjects("Timelog", ["object_class" => get_class($object), "object_id" => $object->id, "is_deleted" => 0]);
        }
    }

    /**
     * Return a page of timelogs for a target object.
     * Pages are 0 indexed.
     *
     * @return object{count:int,totalTime:int,timelogs:Timelog[]}
     */
    public function paginateTimelogsForObject(
        DbObject $target,
        int $page = 0,
        int $pageSize = 50,
        string $orderBy = "dt_start ASC"
    ) {
        $count = $this->countTimelogsForObject($target);

        $stmt = $this->w->db->prepare(
            "SELECT SUM(TIMESTAMPDIFF(SECOND, timelog.dt_start, timelog.dt_end))
        FROM timelog
        WHERE timelog.object_class = ? AND timelog.object_id = ?"
        );
        $stmt->bindValue(1, get_class($target));
        $stmt->bindValue(2, $target->id);
        $stmt->execute();
        $totalTime = intval($stmt->fetchColumn(0));

        $timelogs = $this->getObjects(
            class: "Timelog",
            where: [
                "object_class" => get_class($target),
                "object_id" => $target->id,
            ],
            order_by: $orderBy,
            offset: $pageSize * $page,
            limit: $pageSize,
        );

        return [
            "count" => $count,
            "totalTime" => $totalTime,
            "timelogs" => $timelogs,
        ];
    }

    /**
     * Returns number of timelogs for a given object
     *
     * @param DbObject $object
     * @return int
     */
    public function countTimelogsForObject($object)
    {
        if (!empty($object->id)) {
            return $this->_db->get('timelog')->where("object_class", get_class($object))->where("object_id", $object->id)
                ->where('is_deleted', 0)->count();
        }
        return 0;
    }

    public function countTimelogsForUserAndObject($user, $object)
    {
        if (!empty($user) && !empty($object) && is_a($object, 'DbObject')) {
            return $this->_db->get('timelog')->where('user_id', $user->id)
                ->where("object_class", get_class($object))
                ->where("object_id", $object->id)
                ->where('is_deleted', 0)->count();
        }
        return 0;
    }

    /**
     * Returns all non-deleted timelogs
     *
     * @return Timelog[]
     */
    public function getTimelogs()
    {
        return $this->getObjects("Timelog", ["is_deleted" => 0]);
    }

    public function getTimelog($id)
    {
        return $this->getObject("Timelog", $id);
    }

    public function getActiveTimeLogForUser()
    {
        return $this->getObject("Timelog", ["is_deleted" => 0, "dt_end" => null, "user_id" => AuthService::getInstance($this->w)->user()->id]);
    }

    public function hasActiveLog()
    {
        $timelog = $this->getActiveTimeLogForUser();
        return !empty($timelog);
    }

    public function hasTrackingObject()
    {
        return !empty($this->_trackObject);
    }

    public function registerTrackingObject($object)
    {
        $this->_trackObject = $object;
    }

    public function getTrackingObject(): mixed
    {
        return $this->_trackObject;
    }

    #[Deprecated(
        reason: "Unused function. Use getTrackingObject and get_class() if needed",
        since: "7.0"
    )]
    public function getTrackingObjectClass(): string
    {
        if ($this->hasTrackingObject()) {
            return get_class($this->_trackObject);
        }
        return "";
    }

    public function getJSTrackingObject()
    {
        if ($this->hasTrackingObject()) {
            $class = new stdClass();
            $class->class = get_class($this->_trackObject);
            $class->id = $this->_trackObject->id;
            return json_encode($class);
        }
    }

    public function shouldShowTimer()
    {
        // Check if tracking object set or existing timelog is running
        return !empty(AuthService::getInstance($this->w)->user()) &&
            AuthService::getInstance($this->w)->user()->hasRole("timelog_user") &&
            ($this->hasTrackingObject() || $this->hasActiveLog());
    }

    /**
     * returns a list of objects to which you can attach timelogs
     * @return [] list of loggable objects
     */
    public function getLoggableObjects()
    {
        //get a list of all active modules
        $objects = [];
        $modules = array_filter(Config::keys() ?: [], function ($module) {
            return Config::get("$module.active") === true;
        });

        if (!empty($modules)) {
            foreach ($modules as $key => $module) {
                $timelog = Config::get("$module.timelog");
                //check module config for timelog enabled objects
                if ($timelog !== null && is_array($timelog)) {
                    foreach ($timelog as $value) {
                        $objects[$value] = $value;
                    }
                }
            }
        }
        return $objects;
    }

    public function navigation(Web $w, $title = null, $nav = null)
    {
        if ($title) {
            $w->ctx("title", $title);
        }

        $nav = $nav ?: [];

        $trackingObject = $this->getTrackingObject();

        if (AuthService::getInstance($w)->loggedIn()) {
            $w->menuLink("timelog/index", "Timelog Dashboard", $nav);
            $w->menuBox("timelog/edit" . (!empty($trackingObject) && !empty($trackingObject->id) ? "?class=" . get_class($trackingObject) . "&id=" . $trackingObject->id : ''), "Add Timelog", $nav);
        }

        $w->ctx("navigation", $nav);
        return $nav;
    }

    public function navList(): array
    {
        $trackingObject = $this->getTrackingObject();

        return [
            new MenuLinkStruct("Timelog Dashboard", "timelog/index"),
            new MenuLinkStruct(
                "Add Timelog",
                "timelog/edit" . (!empty($trackingObject) && !empty($trackingObject->id) ? "?class=" . get_class($trackingObject) . "&id=" . $trackingObject->id : ''),
                MenuLinkType::Modal
            ),
        ];
    }
}
