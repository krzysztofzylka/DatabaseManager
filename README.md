# DatabaseManager

Wydajna biblioteka PHP do zarządzania operacjami bazodanowymi. Wspiera MySQL i SQLite, oferując wysoką wydajność oraz czytelny interfejs.

[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.1-blue.svg)](https://www.php.net/releases/8.1/en.php)

## Wymagania

- PHP 8.1 lub nowszy
- Rozszerzenie PDO
- MySQL lub SQLite

## Instalacja

```bash
composer require krzysztofzylka/database-manager
```

## Podstawowe użycie

### Nawiązywanie połączenia

```php
$databaseManager = new \krzysztofzylka\DatabaseManager\DatabaseManager();

try {
    $connect = \krzysztofzylka\DatabaseManager\DatabaseConnect::create()
        ->setType(\krzysztofzylka\DatabaseManager\Enum\DatabaseType::mysql)
        ->setDatabaseName('database')
        ->setUsername('username')
        ->setPassword('password');
    
    $databaseManager->connect($connect);
} catch (\krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException $exception) {
    die($exception->getHiddenMessage());
}
```

### Operacje CRUD

#### Pobieranie danych

```php
// Pobranie jednego rekordu
$table = new \krzysztofzylka\DatabaseManager\Table('users');
$user = $table->find(['id' => 1]);

// Pobranie wszystkich rekordów
$users = $table->findAll();

// Liczenie rekordów
$count = $table->findCount(['status' => 'active']);

// Sprawdzenie istnienia
$exists = $table->findIsset(['email' => 'example@domain.com']);
```

#### Dodawanie rekordów

```php
$table = new \krzysztofzylka\DatabaseManager\Table('users');
$table->insert([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'created_at' => date('Y-m-d H:i:s')
]);

// Pobranie ID nowo utworzonego rekordu
$newId = $table->getId();
```

#### Aktualizacja rekordów

```php
$table = new \krzysztofzylka\DatabaseManager\Table('users');
$table->setId(1)->update([
    'username' => 'updated_username',
    'last_login' => date('Y-m-d H:i:s')
]);

// Aktualizacja pojedynczej kolumny
$table->setId(1)->updateValue('status', 'inactive');
```

#### Usuwanie rekordów

```php
$table = new \krzysztofzylka\DatabaseManager\Table('users');
$table->delete(1); // Usunięcie po ID

// Usuwanie według warunków
$table->deleteByConditions(['status' => 'deleted']);
```

### Zaawansowane zapytania

#### Złożone warunki wyszukiwania

```php
// Proste warunki z operatorami
$conditions = [
    new \krzysztofzylka\DatabaseManager\Condition('age', '>', 18),
    new \krzysztofzylka\DatabaseManager\Condition('status', 'IN', ['active', 'pending']),
    'is_verified' => true
];

$results = $table->findAll($conditions);
```

#### Złączenia tabel

```php
$table = new \krzysztofzylka\DatabaseManager\Table('users');
$table->bind(
    \krzysztofzylka\DatabaseManager\Enum\BindType::leftJoin,
    'orders',
    'users.id',
    'orders.user_id'
);

$userWithOrders = $table->findAll();
```

#### Transakcje

```php
$transaction = new \krzysztofzylka\DatabaseManager\Transaction();

try {
    $transaction->begin();
    
    // Operacje bazodanowe
    $table->insert(['name' => 'Product 1']);
    $table->setId(5)->update(['stock' => 10]);
    
    $transaction->commit();
} catch (\Exception $e) {
    $transaction->rollback();
    echo "Błąd: " . $e->getMessage();
}
```

## Schemat bazy danych

### Tworzenie tabel

```php
$createTable = new \krzysztofzylka\DatabaseManager\CreateTable();
$createTable->setName('products');
$createTable->addIdColumn()
    ->addSimpleVarcharColumn('name', 255, false)
    ->addSimpleDecimalColumn('price', '10,2', 0.00)
    ->addSimpleIntColumn('stock', false, true)
    ->addDateCreatedColumn()
    ->addDateModifyColumn();

$createTable->execute();
```

### Modyfikacja struktury tabeli

```php
$alterTable = new \krzysztofzylka\DatabaseManager\AlterTable('products');

// Dodawanie nowej kolumny
$column = new \krzysztofzylka\DatabaseManager\Column();
$column->setName('description')
    ->setType(\krzysztofzylka\DatabaseManager\Enum\ColumnType::text)
    ->setNull(true);

$alterTable->addColumn($column);

// Modyfikacja typu kolumny
$alterTable->modifyColumn('name', \krzysztofzylka\DatabaseManager\Enum\ColumnType::varchar, 100);

// Usunięcie kolumny
$alterTable->removeColumn('old_column');

$alterTable->execute();
```

## Zaawansowane funkcje

### Cache zapytań

```php
// Zapisanie danych w cache
\krzysztofzylka\DatabaseManager\Cache::saveData('key', $value);

// Odczytanie danych z cache
$data = \krzysztofzylka\DatabaseManager\Cache::getData('key');
```

### Blokady bazodanowe

```php
$lock = new \krzysztofzylka\DatabaseManager\DatabaseLock();

// Zablokowanie zasobu
if ($lock->lock('import_process', 300)) {
    // Wykonaj operację wymagającą wyłącznego dostępu
    
    // Zwolnienie blokady
    $lock->unlock('import_process');
}
```

## Obsługa błędów

Biblioteka wykorzystuje dedykowane klasy wyjątków:

```php
try {
    // Kod korzystający z DatabaseManager
} catch (\krzysztofzylka\DatabaseManager\Exception\ConnectException $e) {
    // Błąd połączenia
    echo "Nie można połączyć z bazą danych: " . $e->getHiddenMessage();
} catch (\krzysztofzylka\DatabaseManager\Exception\TransactionException $e) {
    // Błąd transakcji
    echo "Błąd transakcji: " . $e->getHiddenMessage();
} catch (\krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException $e) {
    // Ogólny błąd
    echo "Błąd bazy danych: " . $e->getHiddenMessage();
}
```

## Licencja

MIT License. Pełna treść w pliku [LICENSE](LICENSE).