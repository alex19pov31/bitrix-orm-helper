# Bitrix ORM helper

Генерирует объект D7 **Bitrix\Main\ORM\Data\DataManager** по названию таблицы без необходимости создания отдельного класса.

## Простая генерация

Поля для описания класса вытягиваются из описания таблицы запросом - DESC table_name.

```php
$data = DataManager::init('b_iblock') // Имя таблицы - b_iblock
    ->getList([
        'filter' => [ // Выборка элементов с ID > 0
            '>ID' => 0,
        ],
        'cache' => [
            'ttl' => 86400, // Кешируем данные на сутки
        ],
    ])
    ->fetchAll(); 
```

## Генерация с настройкой полей

```php
$manager = new DataManager('b_iblock')
$manager->reset(); // Удаляем ранее сгенерированный класс для работы с данными
$manager->setFieldsRaw([ // Описание полей в формате bitrix
	'ID' => [
		'data_type' => 'integer',
        'required' => false,
        'primary' => true,
        'autocomplete' => false,
	],
	'NAME' => [
		'data_type' => 'string',
	],
]);
$manager->addFieldRaw(
    'IBLOCK_TYPE_ID', // Код добавляемого поля
    ['data_type' => 'string'] // описание поля в формате bitrix
);
$manager->addField('NAME', 'string'); // Добаление нового поля
$datamanager = $manager->getDataManager(); // получаем объект для работы с данными
$data = $datamanager->getList([
        'filter' => [ // Выборка элементов с ID > 0
            '>ID' => 0,
        ],
        'cache' => [
            'ttl' => 86400, // Кешируем данные на сутки
        ],
    ])
    ->fetchAll();
```