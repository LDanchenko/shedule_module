<?php


class Group {
    public $id;
    public $name;
    public $disciplines;

    /**
     * Groups constructor.
     * @param $id
     * @param $name
     * @param array $disciplines
     */
    public function __construct($id, $name, array $disciplines) {
        $this->id = $id;
        $this->name = $name;
        $this->disciplines = $disciplines;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getDisciplines() {
        return $this->disciplines;
    }

    /**
     * @param mixed $disciplines
     */
    public function setDisciplines($disciplines) {
        $this->disciplines = $disciplines;
    }

    public function hasDiscipline(Discipline $discipline) {
        return in_array($discipline, $this->disciplines);
        //true or false
    }

}
