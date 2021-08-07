<?php

declare(strict_types=1);

namespace Hgraca\BenchmarkIds;

use PDO;
use Ramsey\Uuid\Uuid;

final class BenchmarkId
{
    private PDO $pdo;
    private string $dbName;
    private string $benchmarkName;

    /** @var callable|null */
    private $idGenerator;
    private string $primaryKeyType;
    private string $foreignKeyType;

    public function __construct(
        PDO $pdo,
        string $dbName,
        string $primaryKeyType,
        string $foreignKeyType,
        string $benchmarkName,
        callable $idGenerator = null
    ) {
        $this->pdo = $pdo;
        $this->dbName = $dbName;
        $this->primaryKeyType = $primaryKeyType;
        $this->foreignKeyType = $foreignKeyType;
        $this->benchmarkName = $benchmarkName;
        $this->idGenerator = $idGenerator === null
            ? function () {
                return null;
            }
            : $idGenerator;
    }

    public function createTables(): float
    {

        echo "Dropping existing DB tables... ";
        $this->pdo->exec(
            <<<SQL
            DROP TABLE IF EXISTS table_{$this->benchmarkName}_ab;
            DROP TABLE IF EXISTS table_{$this->benchmarkName}_a;
            DROP TABLE IF EXISTS table_{$this->benchmarkName}_b;
            SQL
        );
        echo "Done!\n";

        echo "Creating DB tables... ";
        $timer = Timer::startTimer();
        $this->pdo->exec(
            <<<SQL
            DROP TABLE IF EXISTS table_{$this->benchmarkName}_a;
            CREATE TABLE IF NOT EXISTS table_{$this->benchmarkName}_a
            (
                id {$this->primaryKeyType} NOT NULL PRIMARY KEY,
                some_data CHAR(1) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
            ENGINE=InnoDB CHARSET=UTF8;

            DROP TABLE IF EXISTS table_{$this->benchmarkName}_b;
            CREATE TABLE IF NOT EXISTS table_{$this->benchmarkName}_b
            (
                id {$this->primaryKeyType} NOT NULL PRIMARY KEY,
                some_data CHAR(1) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
            ENGINE=InnoDB CHARSET=UTF8;

            DROP TABLE IF EXISTS table_{$this->benchmarkName}_ab;
            CREATE TABLE IF NOT EXISTS table_{$this->benchmarkName}_ab
            (
                some_data CHAR(1) NOT NULL,
                id_a {$this->foreignKeyType} NOT NULL,
                id_b {$this->foreignKeyType} NOT NULL,
                INDEX idx_table_{$this->benchmarkName}_ab_a (id_a),
                INDEX idx_table_{$this->benchmarkName}_ab_b (id_b),
                FOREIGN KEY (id_a) REFERENCES table_{$this->benchmarkName}_a(id),
                FOREIGN KEY (id_b) REFERENCES table_{$this->benchmarkName}_b(id)
            )
            ENGINE=InnoDB CHARSET=UTF8;
            SQL
        );
        $time = $timer->stopTimer();
        echo "Done!\n";

        return $time;
    }

    public function insertDataInTableA(int $amountOfRows): float
    {
        return $this->insertDataInTable('a', $amountOfRows);
    }

    public function insertDataInTableB(int $amountOfRows): float
    {
        return $this->insertDataInTable('b', $amountOfRows);
    }

    private function insertDataInTable(string $table, int $amountOfRows): float
    {
        echo "Inserting data in DB table $table... ";
        $generateId = $this->idGenerator;
        $idList = [];
        for ($i = 0; $i < $amountOfRows; $i++) {
            $idList[] = $generateId();
        }
        $timer = Timer::startTimer();
        $stmt = $this->pdo->prepare(
            <<<SQL
            INSERT INTO table_{$this->benchmarkName}_$table(id, some_data) VALUES (:id, '$table')
            SQL
        );
        for ($i = 0; $i < $amountOfRows; $i++) {
            $stmt->execute(['id' => $idList[$i]]);
        }
        $timer->stopTimer();

        echo "Done!\n";

        return $timer->getLastTime();
    }

    public function insertDataInTableAB(int $amountOfRows): float
    {
        echo "Inserting data in DB table AB... ";
        $timer = Timer::startTimer();
        $stmt = $this->pdo->prepare(
            <<<SQL
            INSERT INTO table_{$this->benchmarkName}_ab(some_data, id_a, id_b)
                 SELECT 'X',
                 (SELECT table_{$this->benchmarkName}_a.id FROM table_{$this->benchmarkName}_a ORDER BY RAND() LIMIT 1),
                 (SELECT table_{$this->benchmarkName}_b.id FROM table_{$this->benchmarkName}_b ORDER BY RAND() LIMIT 1);
            SQL
        );
        for ($i = 0; $i < $amountOfRows; $i++) {
            $stmt->execute();
        }
        $timer->stopTimer();

        echo "Done!\n";

        return $timer->getLastTime();
    }

    public function getIdList(): array
    {
        $stmt = $this->pdo->query("SELECT id FROM table_{$this->benchmarkName}_a");
        $idList = array_map(
            function ($row) {
                return $row['id'];
            },
            $stmt->fetchAll()
        );
        shuffle($idList);

        return $idList;
    }

    public function querySingleTable(array $idList): float
    {
        $timer = Timer::startTimer();
        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT *
            FROM table_{$this->benchmarkName}_a
            WHERE table_{$this->benchmarkName}_a.id = :aid;
            SQL
        );
        foreach ($idList as $id) {
            $stmt->execute([':aid' => $id]);
        }

        return $timer->stopTimer();
    }

    public function queryJoinedTables(array $idList): float
    {
        $timer = Timer::startTimer();
        $stmt = $this->pdo->prepare(
            <<<SQL
            SELECT *
            FROM table_{$this->benchmarkName}_a
                INNER JOIN table_{$this->benchmarkName}_ab on table_{$this->benchmarkName}_a.id = table_{$this->benchmarkName}_ab.id_a
                INNER JOIN table_{$this->benchmarkName}_b on table_{$this->benchmarkName}_ab.id_b = table_{$this->benchmarkName}_b.id
            WHERE table_{$this->benchmarkName}_a.id = :aid;
            SQL
        );
        foreach ($idList as $id) {
            $stmt->execute([':aid' => $id]);
        }

        return $timer->stopTimer();
    }

    public function getTablesSize(): string
    {
        $stmt = $this->pdo->query(
            <<<SQL
            SELECT SUM(bytes) as `bytes`
            FROM (
                     SELECT
                         ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024) AS `bytes`
                     FROM
                         information_schema.TABLES
                     WHERE
                             TABLE_SCHEMA = '{$this->dbName}'
                             AND TABLE_NAME LIKE 'table_{$this->benchmarkName}_%'
                 ) table_size
            SQL
        );
        return $stmt->fetchColumn();
    }
}
