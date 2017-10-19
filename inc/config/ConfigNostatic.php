<?php

/**
* Не статичный класс использования файла конфигурации
*
* @param $default Возвращаемое значение по умолчанию, если параметра в конфиге нет
*
*/

class ConfigNostatic {
	/** @var array */
    protected $data;
	/** @var array */
    protected $cache = array();
	/** @var */
    protected $default = null;
	
	/**
	* Инициализация конфиг-файла
	*
	* @return void
	*/
	public function init($path) {
		$this->data = require_once $path;
	}
	
	/**
	* Получить значение параметра
	*
	* @param string $key Параметр, значение которого необходимо получить
	* @param $default Возвращаемое значение, если такой параметр отсутствует
	*
	* @return Значение конфига
	*/
    public function get($key, $default = null) {
		$this->default = $default;
		if ($this->exists($key)) {
			return $this->cache[$key];
		}
    }

	/**
	* Установить значение параметра
	*
	* @param string $key Параметр, значение которого необходимо установить
	* @param $value Значение, которое нужно установить для параметра
	*
	* @return void
	*/	
    public function set($key, $value) {
        $segs = explode('.', $key);
        $data = &$this->data;
        $cacheKey = '';
        while ($part = array_shift($segs)) {
            if ($cacheKey != '') {
                $cacheKey .= '.';
            }
            $cacheKey .= $part;
            if (!isset($data[$part]) && count($segs)) {
                $data[$part] = array();
            }
            $data = &$data[$part];
            // Удалить старый кэш
            if (isset($this->cache[$cacheKey])) {
                unset($this->cache[$cacheKey]);
            }
            // Удалить старый кэш в массиве
            if (count($segs) == 0) {
                foreach ($this->cache as $cacheLocalKey => $cacheValue) {
                    if (substr($cacheLocalKey, 0, strlen($cacheKey)) === $cacheKey) {
                        unset($this->cache[$cacheLocalKey]);
                    }
                }
            }
        }
        $this->cache[$key] = $data = $value;
    }		
	
	/**
	* Существует ли такой параметр
	*
	* @param string $key Параметр, значение которого необходимо проверить
	* @param $default Возвращаемое значение, если такой параметр отсутствует
	*
	* @return boolean TRUE Если такой параметр существует
	*/
    public function exists($key, $default = null) {
		$this->default = $default;
        if (isset($this->cache[$key])) {
            return true;
        }
        $segments = explode('.', $key);
        $data = $this->data;
        foreach ($segments as $segment) {
            if (array_key_exists($segment, $data)) {
                $data = $data[$segment];
                continue;
            } else {
                return $this->default;
            }
        }
        $this->cache[$key] = $data;
        return true;		
    }

	/**
	* Получить все параметры
	*
	* @return array Все параметры
	*/	
    public function all() {
        return $this->data;
    }	
	
}