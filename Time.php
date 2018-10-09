<?php


class Time {
    private $startDate;//начало
    private $durationInMinutes; //продолжительность

    /**
     * Time constructor.
     * @param DateTime $startDate
     * @param $durationInMinutes
     */
    public function __construct(DateTime $startDate, $durationInMinutes) {
        $this->startDate = $startDate;
        $this->durationInMinutes = $durationInMinutes;
    }

    public function __toString() {
        return $this->startDate->getTimestamp() . '->' . $this->durationInMinutes;
    }

    /**
     * @return DateTime
     */
    public function getStartDate(): DateTime {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     */
    public function setStartDate(DateTime $startDate): void {
        $this->startDate = $startDate;
    }

    /**
     * @return mixed
     */
    public function getDurationInMinutes() {
        return $this->durationInMinutes;
    }

    /**
     * @param mixed $durationInMinutes
     */
    public function setDurationInMinutes($durationInMinutes): void {
        $this->durationInMinutes = $durationInMinutes;
    }

}
