<?php

namespace Alex19pov31\BitrixORMHelper;

use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager as OriginalDataManager;
use Bitrix\Main\ORM\Entity;

class DataManager
{
    private static $managerList;
    private $tableName;
    private $fields;
    private $app;

    public function __construct(string $tableName, $dataManager = null, $app = null)
    {
        $this->tableName = $tableName;
        if ($dataManager instanceof OriginalDataManager) {
            static::$managerList[$tableName] = $dataManager;
        }
    }

    /**
     * Инициализация менеджера данных
     *
     * @param string $tableName
     * @param OriginalDataManager|null $dataManager
     * @param Application|null $app
     * @return OriginalDataManager
     */
    public static function init(string $tableName, $dataManager = null, $app = null): OriginalDataManager
    {
        if (!empty(static::$managerList[$tableName])) {
            return static::$managerList[$tableName];
        }

        $manager = new static($tableName, $dataManager, $app);
        return static::$managerList[$tableName] = $manager->getDataManager();
    }

    /**
     * Отчиска и инициализация менеджера данных
     *
     * @param string $tableName
     * @param OriginalDataManager|null $dataManager
     * @param Application|null $app
     * @return OriginalDataManager
     */
    public static function reinit(string $tableName, $dataManager = null, $app = null): OriginalDataManager
    {
        static::clean($tableName);
        return static::init($tableName, $dataManager, $app);
    }

    /**
     * Отчистка менеджера
     *
     * @param string $tableName
     * @return void
     */
    private static function clean(string $tableName)
    {
        unset(static::$managerList[$this->tableName]);
    }

    /**
     * Отчистка текущего менеджера
     *
     * @return DataManager
     */
    public function reset(): DataManager
    {
        static::clean($this->tableName);
        return $this;
    }

    /**
     * Установить поля в формате bitrix
     *
     * @param array $fields
     * @return DataManager
     */
    public function setFieldsRaw(array $fields): DataManager
    {
        foreach ($fields as $key => $field) {
            $this->addFieldRaw($key, $field);
        }
        $this->reset();
        return $this;
    }

    /**
     * Добавить поле в формате bitrix
     *
     * @param string $fieldName
     * @param array $data
     * @return DataManager
     */
    public function addFieldRaw(string $fieldName, array $data): DataManager
    {
        $this->fields[$fieldName] = $data;
        $this->reset();
        return $this;
    }

    /**
     * Добавить поле
     *
     * @param string $fieldName
     * @param string $dataType
     * @param mixed $defaultValue
     * @param mixed $enumValues
     * @param boolean $isRequired
     * @param boolean $isPrimary
     * @param boolean $isAutocomplete
     * @return DataManager
     */
    public function addField(string $fieldName, string $dataType = 'string', $defaultValue = null, $enumValues = null, bool $isRequired = false, bool $isPrimary = false, bool $isAutocomplete = false): DataManager
    {
        $this->fields[$fieldName] = [
            'data_type' => $dataType,
            'required' => $isRequired,
            'primary' => $isPrimary,
            'autocomplete' => $isAutocomplete,
        ];

        if (!is_null($defaultValue)) {
            $this->fields[$fieldName]['default_value'] = $defaultValue;
        }
        if (!is_null($enumValues)) {
            $this->fields[$fieldName]['values'] = $enumValues;
        }

        $this->reset();
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return Application
     */
    private function getApplication()
    {
        if (is_null($this->app)) {
            $this->app = Application::class;
        }

        return $this->app;
    }

    /**
     * Список полей из описания таблицы
     *
     * @return array
     */
    private function getFields(): array
    {
        if (!is_null($this->fields)) {
            return (array) $this->fields;
        }

        $fields = $this->getApplication()::getConnection()
            ->query('DESC `' . $this->tableName . '`')->fetchAll();

        foreach ($fields as $field) {
            $arType = explode($field, '(');
            $rawType = $arType[0];
            $dataType = 'string';

            switch ($rawType) {
                case 'int':
                    $dataType = 'integer';
                    break;
                case 'timestamp':
                    $dataType = 'datetime';
                    break;
                case 'bool':
                    $dataType = 'boolean';
                    break;
            }

            $fieldName = $field['Field'];
            $isPrimary = $field['Key'] == 'PRI';
            $isAutocomplete = $field['Extra'] == 'auto_increment';
            $defaultValue = ($dataType != 'timestamp') ? $field['Default'] : null;
            $this->addField($fieldName, $dataType, $defaultValue, null, false, $isPrimary, $isAutocomplete);
        }

        return (array) $this->fields;
    }

    /**
     * Менеджер данных
     *
     * @return OriginalDataManager
     */
    public function getDataManager(): OriginalDataManager
    {
        if (!empty(static::$managerList[$this->tableName])) {
            return static::$managerList[$this->tableName];
        }

        $dataManager = new class($this->tableName, $this->getFields()) extends OriginalDataManager
        {
            private static $tableName;
            private static $fields;

            public function __construct(string $tableName, array $fields)
            {
                static::$tableName = $tableName;
                static::$fields = $fields;
            }

            private static function getSelf()
            {
                return new static(static::$tableName, static::$fields);
            }

            public static function getEntity()
            {
                $entity = new Entity();
                $entity->initialize(static::getSelf());
                $entity->postInitialize();
                return $entity;
            }

            public static function getTableName()
            {
                return static::$tableName;
            }

            public static function getMap()
            {
                return static::$fields;
            }
        };

        return static::$managerList[$this->tableName] = $dataManager;
    }
}
