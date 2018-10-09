<?php

//
class KeyValueMapStorage implements Countable, Iterator, ArrayAccess {
    private $storageKeys;//єто массив
    private $storageValues;//и это масив

    public function __construct() {
        $this->storageKeys = new SplObjectStorage();//сюда обьекты entry
        $this->storageValues = array();// тут коефициенты верно?
    }

    function append($keyObject, $value) {
        $value = (string)$value;
        $this->storageKeys[$keyObject] = $value;

        if (!array_key_exists($value, $this->storageValues)) { //если нет ключа для  значения в массиве values
            $this->storageValues[$value] = array(); // добавлии его в values
        }
        $this->storageValues[$value][] = $keyObject;
    }

    function remove($keyObject) {
        $value = $this[$keyObject];//вытащили значение из массива по ключу
        if (!array_key_exists($value, $this->storageValues)) {//если нет ключа для значения в массиве значений
            throw new Exception('Data was not integrity.');
        }

        if (($key = array_search($keyObject, $this->storageValues[$value])) !== false) {
            unset($this->storageValues[$value][$key]); //удалит из масива
        }
    }

    function removeAllKeysByValue($value) {
        $value = (string)$value;
        if (!array_key_exists($value, $this->storageValues)) {
            return;
        }

        $keys = $this->storageValues[$value];
        foreach ($keys as $key) {
            unset($this->storageKeys[$key]);//убрать все ключи
        }

        $this->storageValues[$value] = array();
    }

    function getValueByKey($key) {//
        return $this->storageKeys->offsetGet($key);
    }

    function getKeysByValue($value) {
        $value = (string)$value;
        if (!array_key_exists($value, $this->storageValues)) {
            return array();
        }

        return $this->storageValues[$value];
    }

    //n - итерации?
//???
    public function topByValue($n, $randomize = false) {
        $result = new KeyValueMapStorage();
        $sortedValues = array_keys($this->storageValues);// получили значения коеф
        rsort($sortedValues); //сортируем массив от большего в меньшему

        $currentIndex = 0;
        $resultSize = 0;
        $possibleValuesVariants = count($sortedValues);// кол-во элементов массива

        while ($resultSize <= $n && $currentIndex < $possibleValuesVariants) {
            $value = $sortedValues[$currentIndex]; //выбрали коеф
            $keysObject = $this->getKeysByValue($value); //обьект entry

            if ($randomize === true) { //
                shuffle($keysObject); //перемешали обьекты в случайном порядке
            }

            foreach ($keysObject as $keyObject) {
                $result->append($keyObject, $value); // сделали новый массив из случайно обьекты и коефициенты??
                $resultSize++;
            }

            $currentIndex++;
        }

        return $result;
    }
//добавление в один
    public function extend(KeyValueMapStorage $storage) {
        foreach ($storage as $item) {
            list($key, $value) = $item; //делаем список из двух массивов
            $this->append($key, $value);//добавили в массив
        }
    }

    /**
     * Count elements of an object
     * @link https://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count() {
        return $this->storageKeys->count();
    }


    /**
     * Return the current element
     * @link https://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current() {
        $keyObject = $this->storageKeys->current();
        $value = $this->getValueByKey($keyObject);

        return array($keyObject, $value);
    }

    /**
     * Move forward to next element
     * @link https://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next() {
        $this->storageKeys->next();
    }

    /**
     * Return the key of the current element
     * @link https://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key() {
        return $this->storageKeys->key();
    }

    /**
     * Checks if current position is valid
     * @link https://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid() {
        return $this->storageKeys->valid();
    }

    /**
     * Rewind the Iterator to the first element
     * @link https://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind() {
        $this->storageKeys->rewind();
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset) {
        return $this->storageKeys->offsetExists($offset);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset) {
        return $this->storageKeys->offsetGet($offset);
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value) {
        $this->append($offset, $value);
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     * @throws Exception
     */
    public function offsetUnset($offset) {
        $this->remove($offset);
    }
}
