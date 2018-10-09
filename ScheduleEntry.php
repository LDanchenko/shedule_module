<?php


class ScheduleEntry {

    public $time;
    public $discipline;
    public $lecturer;
    public $group;

    /**
     * ScheduleEntry constructor.
     * @param $time
     * @param $discipline
     * @param $lecturer
     * @param $group
     */
    public function __construct(Time $time, Discipline $discipline, Lecturer $lecturer, Group $group) {
        $this->time = $time;
        $this->discipline = $discipline;
        $this->lecturer = $lecturer;
        $this->group = $group;
    }

    /**
     * @return mixed
     */
    public function getTime() {
        return $this->time;
    }

    /**
     * @param mixed $time
     */
    public function setTime($time): void {
        $this->time = $time;
    }

    /**
     * @return mixed
     */
    public function getDiscipline() {
        return $this->discipline;
    }

    /**
     * @param mixed $discipline
     */
    public function setDiscipline($discipline): void {
        $this->discipline = $discipline;
    }

    /**
     * @return mixed
     */
    public function getLecturer() {
        return $this->lecturer;
    }

    /**
     * @param mixed $lecturer
     */
    public function setLecturer($lecturer): void {
        $this->lecturer = $lecturer;
    }

    /**
     * @return mixed
     */
    public function getGroup() {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group): void {
        $this->group = $group;
    }


}