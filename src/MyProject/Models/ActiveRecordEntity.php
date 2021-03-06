<?php


namespace MyProject\Models;


use MyProject\Models\Users\User;
use MyProject\Services\Db;

abstract class ActiveRecordEntity
{
    /** @var int */
    protected $id;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function __set($name, $value)
    {
        $camelCaseName = $this->underscoreToCamelCase($name);
        $this->$camelCaseName = $value;
    }

    public function underscoreToCamelCase(string $source)
    {
        return lcfirst(str_replace('_', '', ucwords($source, '_')));
    }

    private function camelCaseToUnderscore(string $source): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $source));
    }

    public function save(): void
    {
        $mappedProperties = $this->mapPropertiesToDbFormat();
        if ($this->id !== null)
        {
            $this->update($mappedProperties);
        } else
        {
            $this->insert($mappedProperties);
        }
    }

    private function update(array $mappedProperties): void
    {
        $columns2params = [];
        $params2values = [];
        $index = 1;

        foreach ($mappedProperties as $column => $value) {
            $param = ':param' . $index;
            $columns2params[] = $column . ' = ' . $param;
            $params2values[$param] = $value;
            $index++;
        }

        $sql = 'UPDATE ' . static::getTableName() . ' SET ' . implode(', ', $columns2params) . ' WHERE id = ' . $this->id;
        $db = Db::getInstance();
        $db->query($sql, $params2values, static::class);
    }

    private function insert(array $mappedProperties): void
    {
        $filteredProperties = array_filter($mappedProperties); // Очищаем массив от null значений

        $columns = [];
        $paramsNames = [];
        $params2values = [];

        foreach ($filteredProperties as $columnName => $value) {
            $columns[] = '' . $columnName . '';
            $paramName = ':' . $columnName;
            $paramsNames[] = $paramName;
            $params2values[$paramName] = $value;
        }

        $columnsViaSemicolon = implode(', ', $columns);
        $paramsNamesViaSemicolon = implode(', ', $paramsNames);
        $sql = 'INSERT INTO ' . static::getTableName() . ' (' . $columnsViaSemicolon . ') VALUES (' . $paramsNamesViaSemicolon . ');';

        $db = Db::getInstance();
        $db->query($sql, $params2values, static::class);
        $this->id = $db->getLastInsertId();
    }

    public function delete(): void
    {
        $db = Db::getInstance();
        $db->query(
            'DELETE FROM ' . static::getTableName() . ' WHERE id = :id',
            [':id' => $this->id]
        );
        $this->id = null;
    }

    // Получаем имена свойств статьи с помощью рефлексии и изменяем их для дальнейшей отправки в БД
    private function mapPropertiesToDbFormat(): array
    {
        $reflector = new \ReflectionObject($this);
        $properties = $reflector->getProperties();

        $mappedProperties = [];
        foreach ($properties as $property)
        {
            $propertyName = $property->getName();
            $propertyNameAsUnderscore = $this->camelCaseToUnderscore($propertyName);
            $mappedProperties[$propertyNameAsUnderscore] = $this->$propertyName;
        }

        return $mappedProperties;
    }
    
    /**
     * @return static[]
     * Если передано true, статьи будут отображаться в порядке убывания по id (соответственно, и по дате создания)
     */
    public static function findAll($isDesc = false): array
    {
        $db = Db::getInstance();
        return $db->query('SELECT * FROM '. static::getTableName() .' ORDER BY id'. ($isDesc ? ' DESC' : '' .';'), [], static::class);
    }

    public static function findOneByColumn(string $columnName, $value): ?self
    {
        $db = Db::getInstance();
        $result = $db->query(
            'SELECT * FROM '. static::getTableName() .' WHERE '. $columnName .' = :value LIMIT 1;',
            [':value' => $value],
            static::class
        );

        if ($result === [])
        {
            return null;
        }
        return $result[0];
    }

    public static function findAllByColumn(string $columnName, $value, $isDesc = false): ?array
    {
        $db = Db::getInstance();
        $result = $db->query(
            'SELECT * FROM '. static::getTableName() .' WHERE '. $columnName .' = :value'. ' ORDER BY id' .($isDesc ? ' DESC' : '' .';') ,
            [':value' => $value],
            static::class
        );

        if ($result === [])
        {
            return null;
        }
        return $result;
    }

    abstract protected static function getTableName(): string; // если не начну писать комментарии, вскоре я повешаюсь

    /**
     * @param int $id
     * @return static|null
     */
    public static function getById(int $id): ?self
    {
        $db = Db::getInstance();

        $entities = $db->query(
            'SELECT * FROM '. static::getTableName() .' WHERE id = :id;',
            [':id' => $id],
            static::class
        );

        return $entities ? $entities[0] : null;
    }

}