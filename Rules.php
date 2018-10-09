<?php


abstract class Rule {
    public abstract function calculate(ScheduleEntry $entry);
}

//правило - читают ли в группе дисциплину
class GroupDisciplineAvailable extends Rule { //група дисциплина
    public function calculate(ScheduleEntry $entry) {
        $group = $entry->getGroup();//нашли группу обьекта
        $discipline = $entry->getDiscipline();//нашли дисциплину обьекта

        return (float)$group->hasDiscipline($discipline);//ведут ли в этой группе дисциплину
	    //true or false

    }
}

//правило - ведет ли лектор дисциплину
class DisciplineLectureAvailableToEducationDiscipline extends Rule {

    public function calculate(ScheduleEntry $entry) {
        $discipline = $entry->getDiscipline(); //нашли дисциплину обьекта
        $lecturer = $entry->getLecturer(); //нашли кто лектор

        return (float)$lecturer->hasDiscipline($discipline); //ведет ли лектор дисциплину - true/false
    }
}
