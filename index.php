<?php

require_once('Utils.php');
require_once('KeyValueMapStorage.php');
require_once('Rules.php');
require_once('Time.php');
require_once('Lecturer.php');
require_once('Group.php');
require_once('Discipline.php');
require_once('ScheduleEntry.php');


const PRIORITY_GROUP_WEIGHT = 0.25; //приоритет группы
const PRIORITY_DISCIPLINE_WEIGHT = 0.25; //приоритет дисциплины
const PRIORITY_LECTURER_WEIGHT = 0.5; //преподы


const INTERSECT_CLS_GROUP_TIME = "INTERSECT_CLS_GROUP_TIME";
const INTERSECT_CLS_GROUP_DISCIPLINE = "INTERSECT_CLS_GROUP_DISCIPLINE";
const INTERSECT_CLS_GROUP_LECTURER = "INTERSECT_CLS_GROUP_LECTURER";
const INTERSECT_CLS_DISCIPLINE_TIME = "INTERSECT_CLS_DISCIPLINE_TIME";
const INTERSECT_CLS_DISCIPLINE_LECTURER = "INTERSECT_CLS_DISCIPLINE_LECTURER";
const INTERSECT_CLS_LECTURER_TIME = "INTERSECT_CLS_LECTURER_TIME";

const ADDITIONAL_RETRY_TIMES_COUNT = 3;

/**
 * Дни недели в которые учаться студенты, для которых необходимо составить расписания.
 * 0=Sunday, 1=Monday, etc.
 * INFO: Разные групи могут учиться на разных сменах в разные дни, соответственно они попадают в одно расписания,
 *       им необходимо прописать соответствующее правило(Rule). Данный же параметр является глобальным для всех груп
 *       одновременно.
 */
const EDUCATION_WEEKDAYS = array(false, true, false, false, false, false, false); // тут не поняла
//$weekDaysFilter[$currentWeekDay] !== true берем дни которые false:????

const NUM_OF_EVENTS_IN_PERIOD = 20; //пар в неделю - посчитать из бд
const NUM_OF_EVENTS_PER_DAY = 4; //пар в день посчитать из пар в неделю
// к-во учебніх дней = тоже бд
const SCHEDULE_PERIOD_EDUCATION_DAYS_LENGTH = NUM_OF_EVENTS_IN_PERIOD / NUM_OF_EVENTS_PER_DAY;

//вызов соответствующих функций для разных обьектов rules
//ruleIn - массив из restricts - обьект класса из правищ, entry - сборный оьект
function callRuleCalculations($ruleInstance, $entry) {
    //вызо метода обьекта унаследованного от абс клас
    $method = array($ruleInstance, 'calculate');
    $arguments = array($entry);
    //вызов функции method - примером GroupDisciplineAvailable calculate() верно???
	//передали сборный обьект
    $value = call_user_func_array($method, $arguments);
//получили коефициент
    return $value;
}

//rules - части restrict - Обьекты Rules
//считать коефициенты по правилам
function getClassRuleValues($rules, $entry) {
    $rulesCount = count($rules);// колличество обьектов rules
    if ($rulesCount === 0) {
        return 1;
    }

    $values = array();
    foreach ($rules as $rule) {
	    //вызываем функцию для разных обьектов правил чтобы получить коефициенты
        $value = callRuleCalculations($rule, $entry);

        if ($value === 0) {
            return 0;
        }

        $values[] = $value; //собравли все коеф. в один массив
    }

    return array_sum($values) / $rulesCount; //сумма коеф. на колличество обьектов rules - получили средний коеф.
}

//
//передали сборный обьект, вес, и классы - из массива, чтобы было понятно, какие правила применяем
function calculateLocalClassCoefficient($entry, $weight, $classes) {
    $values = array();
    foreach ($classes as $cls) {
        //вызываем правила на обьекты класса
        $value = getClassRuleValues($cls, $entry);// получили средний коеф. по правилам что прописали

        if ($value === 0) {
            return 0;
        }

        // в массив все полученный значения коефициентов
        $values[] = $value;
    }

    $valuesCount = count($values); //кол-во
    if ($valuesCount === 0) {
        return 0;
    }

    //середали средний коефициент
    $localClassCoefficient = array_sum($values) / $valuesCount;

    //вес это приоритет
	//0.5 получили от правил, 0.35 определили = 0.06
    return Utils::applyWeightImpact($localClassCoefficient, $weight);
}

//TODO: разставить класи в порядке следоватльности от первого наиболее вероятного 0 - дольше всего будет считать препода
//высчитать коефициент групп - нам большой массив и сборный обьект (формаируется  в цикле)
function calculateLocalGroupCoefficient($restricts, $entry) {
    $weight = PRIORITY_GROUP_WEIGHT; //приоритет определили сами
    $classes = array(
	    $restricts[INTERSECT_CLS_GROUP_LECTURER], //правило група препод
	    $restricts[INTERSECT_CLS_GROUP_DISCIPLINE], //правило группа дисциплина
	    $restricts[INTERSECT_CLS_GROUP_TIME], //правила група время
    );
//получили коефициент с применением приоритета, правил, веса
    return calculateLocalClassCoefficient($entry, $weight, $classes);
}

//коефициент локал
function calculateLocalDisciplineCoefficient($restricts, $entry) {
    $weight = PRIORITY_DISCIPLINE_WEIGHT;
    $classes = array(
        $restricts[INTERSECT_CLS_DISCIPLINE_TIME],
        $restricts[INTERSECT_CLS_DISCIPLINE_LECTURER],
	    $restricts[INTERSECT_CLS_GROUP_DISCIPLINE],

    );

    return calculateLocalClassCoefficient($entry, $weight, $classes);
}

//по преподам
function calculateLocalLecturerCoefficient($restricts, $entry) {
    $weight = PRIORITY_LECTURER_WEIGHT;
    $classes = array(
        $restricts[INTERSECT_CLS_LECTURER_TIME],
        $restricts[INTERSECT_CLS_DISCIPLINE_LECTURER],
	    $restricts[INTERSECT_CLS_GROUP_LECTURER],

    );

    return calculateLocalClassCoefficient($entry, $weight, $classes);
}

//определяем колличество значений для итерации
//пар в неделю - пар в день
function calculateBatchSize($numOfEvents, $minNumOfEvents) {
    return ceil(max(array( //берет максимальное и округляет к большему
        sqrt($numOfEvents), //квадрат из пар в неделю
        $minNumOfEvents,// пар день
    )));
}

//это поиск конфликтов?
function clsHasTimeIntersect($collection, ScheduleEntry $value) {
    $time = $value->getTime(); //взял время из обьекта
    $lecturer = $value->getLecturer(); //взяли препода
    $group = $value->getGroup(); //взяли группу
//почему  $entry = $entry[0];?
    foreach ($collection as $entry) {
        if ($collection instanceof KeyValueMapStorage) {
            $entry = $entry[0];
        }

        if (($entry->getTime() === $time && $entry->getLecturer() === $lecturer) || //если время совпадет и препод
            ($entry->getTime() === $time && $entry->getGroup() == $group)) {  //или время и группа?
            return true;
        }
    }

    return false;
}

//считаем коефициенты для большой формулы  по правилам
function calculateCoefficients($restricts, $time, $groups, $disciplines, $lecturers) {
    $coefficients = new KeyValueMapStorage(); //делаем обьект

    foreach ($groups as $group) { //перебираем группы
        foreach ($disciplines as $discipline) { //дисциплины
            foreach ($lecturers as $lecturer) { //преподов
                $entry = new ScheduleEntry($time, $discipline, $lecturer, $group); //создаем сборный оьтект
				//считаем коефициент для групп
                $localGroupCoefficient = calculateLocalGroupCoefficient($restricts, $entry);
                if ($localGroupCoefficient === 0) {
                    continue;
                }

                $localDisciplineCoefficient = calculateLocalDisciplineCoefficient($restricts, $entry);//для дисциплни
                if ($localDisciplineCoefficient === 0) {
                    continue;
                }

                $localLecturerCoefficient = calculateLocalLecturerCoefficient($restricts, $entry); //преподов
                if ($localLecturerCoefficient === 0) {
                    continue;
                }

                $classesCoefficients = [$localGroupCoefficient, $localDisciplineCoefficient, $localLecturerCoefficient]; //все коеф. в массив
                // TODO: In fact extra. When data from Rule.calculate corrected don`t needed..
	            //????
	            // вернеет $classesCoef низменным
                $classesCoefficients = Utils::normalizeCollection($classesCoefficients, 0, 1);
				//среднее из всех коеф. по классам
                $entryCoefficient = array_sum($classesCoefficients) / count($classesCoefficients);
                $coefficients->append($entry, $entryCoefficient); //в массив
            }
        }
    }
//получаем тот странный массив из обьектов и коефициентов к ним - обьекты я так понимаю перебор всего
    return $coefficients;
}

//функция создания рассписания - принимает расписание дней/пар которое сгенерили исходя из кол-пар и дней
//$restricts - массив правил
function distributeEvents($eventsTimes, $restricts, $groups, $disciplines, $lecturers) {
    $distributedSchedule = new KeyValueMapStorage(); //обьект принимает два массива:?
    $scheduleMaxSize = count($eventsTimes); // размер массива с элементами типа Time
    $batchSize = calculateBatchSize( // к-во итераций - по факту учебных дней?
        NUM_OF_EVENTS_IN_PERIOD,
        NUM_OF_EVENTS_PER_DAY
    );
    $additionalRetryTimesCounter = ADDITIONAL_RETRY_TIMES_COUNT; //вот это кол-во попыток растановки - почему 3??

    while (count($eventsTimes) > 0 && $additionalRetryTimesCounter > 0) { //пары и попытки
        $availableToDistribute = count($eventsTimes);// сколько свободных
        $entriesToDistribute = new KeyValueMapStorage(); //делаем массив
        $conflicts = array(); //конфликты

        foreach ($eventsTimes as $time) {//перебираем обьекты типа time в массиве
	        //перебрали все варианты - получиили массив из обьектов сборных и коефициентов
            $coefficients = calculateCoefficients($restricts, $time, $groups, $disciplines, $lecturers);
            //где то с этого момента до кнца функции чет не пойму
            $coefficients = $coefficients->topByValue($batchSize);  // перемешали все?
            $entriesToDistribute->extend($coefficients); //добавлили в массив
        }

        $batchToDistributeCount = count($entriesToDistribute);
        $entriesToDistribute = $entriesToDistribute->topByValue($batchToDistributeCount, true);//опять мешаем?

        foreach ($entriesToDistribute as $item) {
            if (count($distributedSchedule) >= $scheduleMaxSize) {
                break;
            }

            list($entry, $coefficient) = $item;
//поиск конфликтов $distributedSchedule - уже заданное  $conflicts - ранее найденные конфликты
            if (clsHasTimeIntersect($distributedSchedule, $entry) || clsHasTimeIntersect($conflicts, $entry)) {
                $conflicts[] = $entry;
                continue;
            }

            Utils::removeFromArrayByValue($eventsTimes, $entry->getTime());//

            $distributedSchedule[$entry] = $coefficient; //добавили коефициенты
        }

        $currentDistributedCount = count($eventsTimes);
        if ($availableToDistribute <= $currentDistributedCount) {
            $additionalRetryTimesCounter--;
        }

    }

    return $distributedSchedule;
}


// TODO: Fixed time from DB with dynamic duration & break between them.
//??? - todo имеешь ввиду у кого какая смена и тд из бд?
//
function buildEventsTimes(DateTime $startDate, $length, $weekDaysFilter, $numOfEventsPerDay) {
    $eventsTimes = array();
    $numOfEventsPerDay = ceil($numOfEventsPerDay);//округлили в большую сторону полученное кол пар в день
    $nextDay = clone $startDate; //склонили дату начала составления расписания

    while ($length > 0) { //к-во дней
        $currentDay = clone $nextDay;//склонили снова дату начала
        $nextDay = (clone $currentDay)->modify('+1 day'); //следующий денm
        $currentWeekDay = $currentDay->format('w'); //день недели

        if ($weekDaysFilter[$currentWeekDay] !== true) { //день недели - по номеру из масива  - true или false
            continue;
        }

        for ($i = 0; $i < $numOfEventsPerDay; $i++) { //цикл - кол пар в день
            $eventTime = clone $currentDay->setTime(14 + (2 * $i), 0); //почему 14? пары по два часа? тут поправить?
            $eventsTimes[] = new Time($eventTime, 80);//записали в массив расписания пар - обьект типа time - начало пары и продолжительность
        }

        $length--; //одну пару сделали, уменьшили кол
    }

    return $eventsTimes;
}


//начало
//екземпляры обьектов правил
$restricts = array(
    INTERSECT_CLS_GROUP_TIME => array(),//TODO:
    INTERSECT_CLS_GROUP_DISCIPLINE => array(new GroupDisciplineAvailable()), //ведут ли в группе дисцилину
    INTERSECT_CLS_GROUP_LECTURER => array(), //TODO:
    INTERSECT_CLS_DISCIPLINE_TIME => array(), //TODO:
    INTERSECT_CLS_DISCIPLINE_LECTURER => array(new DisciplineLectureAvailableToEducationDiscipline()), //ведет лилектор дисциплину
    INTERSECT_CLS_LECTURER_TIME => array(), //TODO:
);
//с какого момента делаем расписание
$schedulePeriodStartDate = new DateTime(
    gmdate('d.m.Y H:i', strtotime('2018-10-08')), //почему день назад?
    new DateTimeZone('GMT')
);


$eventsTimes = buildEventsTimes(
    $schedulePeriodStartDate,
    SCHEDULE_PERIOD_EDUCATION_DAYS_LENGTH,
    EDUCATION_WEEKDAYS,
    NUM_OF_EVENTS_PER_DAY
);


// Задали дисциплины
$disciplines = array(
    new Discipline(0, 'Європейські інформаційно-аналітичні сисмети'),
    new Discipline(1, 'Сучасні технології в зовнішній торгівлі'),
    new Discipline(2, 'Міжнародні та регіональні фінансові структури'),
    new Discipline(3, 'Креативно-інноваційний менеджмент'),
    new Discipline(4, 'Управління фінансовими ризиками'),
    new Discipline(5, 'Управління конкурентоспроможністю підприємства'),
    new Discipline(6, 'Дослідницькі семінари та підготовка дипломної роботи'),
    new Discipline(7, 'Іноземна мова мова спеціальності'),
    new Discipline(8, 'Податкові системи ЄС'),
    new Discipline(9, 'Пропагандистські технології у міжнародних відносинах'),
    new Discipline(10, 'Проблеми адаптації укр. компаній до Європейського бізнес-середовища'),
    new Discipline(11, 'Економіка нематеріальних активів'),
    new Discipline(12, 'Етика і культура маркетингової діяльності'),
    new Discipline(13, 'Інформаційні війни'),
    new Discipline(14, 'Моделювання в міжнародному менеджменті'),
);

//задали групы и дисциплины взяли из массива обьектов
$groups = array(
    new Group(0, 'МЕВ/ЄС-17м', array(
        $disciplines[2],
        $disciplines[3],
        $disciplines[6],
        $disciplines[7],
        $disciplines[8],
        $disciplines[12],
        $disciplines[13],
        $disciplines[14],
    )),
    new Group(1, 'Е/Креатив-17м', array(
        $disciplines[1],
        $disciplines[4],
        $disciplines[5],
        $disciplines[6],
        $disciplines[10],
        $disciplines[11],
    )),
    new Group(2, 'МЕВ/ЄС-18м', array(
        $disciplines[0],
        $disciplines[8],
        $disciplines[9],
        $disciplines[10],
        $disciplines[11],
        $disciplines[12],
        $disciplines[13],
        $disciplines[14],
    )),
);
//задали преподов - задали дисциплины из массива обьектов
$lecturers = array(
    new Lecturer(0, 'Мельничук Д.П.', array(
        $disciplines[2],
        $disciplines[3],
        $disciplines[6],
        $disciplines[7],
        $disciplines[8],
        $disciplines[12],
        $disciplines[13],
        $disciplines[14],
    )),
    new Lecturer(1, 'Чумаченко О.Г.', array(
        $disciplines[1],
        $disciplines[4],
        $disciplines[5],
        $disciplines[6],
        $disciplines[10],
        $disciplines[11],
    )),
    new Lecturer(2, 'Сова О.Ю.', array(
        $disciplines[0],
        $disciplines[8],
        $disciplines[9],
        $disciplines[10],
        $disciplines[11],
        $disciplines[12],
        $disciplines[13],
        $disciplines[14],
    )),
);

$schedule = distributeEvents($eventsTimes, $restricts, $groups, $disciplines, $lecturers);
prettySchedulePrint($schedule);

function prettySchedulePrint($schedule) {
    $index = 1;
    foreach ($schedule as $item) {
        list($entry, $kf) = $item;
        echo '#' . $index . ' [' . $kf . '][' .
            $entry->getTime()->getStartDate()->format('Y-m-d H:i:s') . '] ' .
            $entry->getGroup()->getName() . ' - ' .
            $entry->getDiscipline()->getName() . ' - ' .
            $entry->getLecturer()->getName() .
            PHP_EOL;

        $index++;
    }
}
