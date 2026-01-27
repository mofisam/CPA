<?php
/**
 * Database Wrapper Class using MySQLi
 * Provides a clean interface for database operations
 */

namespace includes\core;

class Database
{
    private \mysqli $conn;
    private static ?Database $instance = null;

    // Prevent direct instantiation
    private function __construct()
    {
        require_once dirname(__DIR__, 2) . '/config/database.php';

        $this->conn = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            throw new \Exception('Database connection failed: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization (must be public in PHP 8+)
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    // Singleton instance
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    // Get raw MySQLi connection
    public function getConnection(): \mysqli
    {
        return $this->conn;
    }

    // Prepare, bind and execute query
    public function query(string $sql, array $params = []): \mysqli_stmt
    {
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new \Exception('Prepare failed: ' . $this->conn->error);
        }

        if (!empty($params)) {
            $types = '';
            $values = [];

            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }

                $values[] = $param;
            }

            $stmt->bind_param($types, ...$values);
        }

        $stmt->execute();
        return $stmt;
    }

    // Fetch single row
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: null;
    }

    // Fetch all rows
    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Insert record and return insert ID
    public function insert(string $table, array $data): int
    {
        $fields = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return $this->conn->insert_id;
    }

    // Update records
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $params = [];

        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = ?";
            $params[] = $value;
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$where}";
        $params = array_merge($params, $whereParams);

        $stmt = $this->query($sql, $params);
        return $stmt->affected_rows;
    }

    // Delete records
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->affected_rows;
    }

    // Transactions
    public function beginTransaction(): void
    {
        $this->conn->begin_transaction();
    }

    public function commit(): void
    {
        $this->conn->commit();
    }

    public function rollback(): void
    {
        $this->conn->rollback();
    }
}
