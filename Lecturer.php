<?php


class Lecturer {
    private $id;
    private $name;
    private $disciplines;

    /**
     * Lecturer constructor.
     * @param $id
     * @param $name
     * @param $disciplines
     */
    public function __construct($id, $name, $disciplines) {
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
    public function setId($id): void {
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
    public function setName($name): void {
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
    public function setDisciplines($disciplines): void {
        $this->disciplines = $disciplines;
    }
//ведет ли лектор дисциплтину
    public function hasDiscipline(Discipline $discipline) {
        return in_array($discipline, $this->disciplines);
    }

}
