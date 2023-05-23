<?php

namespace Bayfront\Bones\Services\Api\Models\Abstracts;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Abstracts\Model;
use Bayfront\Bones\Application\Services\EventService;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Services\Api\Exceptions\BadRequestException;
use Bayfront\Bones\Services\Api\Exceptions\NotFoundException;
use Bayfront\Bones\Services\Api\Exceptions\UnexpectedApiException;
use Bayfront\PDO\Db;
use Bayfront\PDO\Exceptions\InvalidDatabaseException;
use Bayfront\PDO\Exceptions\QueryException;
use Bayfront\PDO\Query;
use Monolog\Logger;
use PDOException;

abstract class ApiModel extends Model
{

    protected EventService $events;
    protected Db $db;
    protected Logger $log;

    public function __construct(EventService $events, Db $db, Logger $log)
    {
        $this->events = $events;
        $this->db = $db;
        $this->log = $log;

        parent::__construct($events);
    }

    /**
     * JSON encode meta array and remove null values.
     *
     * @param array $array
     * @return string
     */
    protected function encodeMeta(array $array): string
    {

        $array = array_filter(Arr::dot($array), fn($value) => !is_null($value));

        return json_encode(Arr::undot($array));

    }

    /**
     * Create UUID as string and binary values.
     *
     * @return array (Keys = str, bin)
     */
    protected function createUUID(): array
    {

        $uuid = $this->db->single("SELECT UUID()");

        return [
            'str' => $uuid,
            'bin' => $this->db->single("SELECT UUID_TO_BIN(:uuid, 1)", [
                'uuid' => $uuid
            ])
        ];

    }

    /**
     * Convert UUID to binary.
     *
     * @param string $uuid
     * @return mixed
     */
    protected function UUIDtoBIN(string $uuid): mixed
    {
        return $this->db->single("SELECT UUID_TO_BIN(:uuid, 1)", [
            'uuid' => $uuid
        ]);
    }

    /**
     * Convert binary to UUID.
     *
     * @param mixed $bin
     * @return string
     */
    protected function BINtoUUID(mixed $bin): string
    {
        return $this->db->single("SELECT BIN_TO_UUID(:bin, 1)", [
            'bin' => $bin
        ]);
    }

    /**
     * Return new Query instance.
     *
     * @param string $db_name
     * @return Query
     * @throws UnexpectedApiException
     */
    public function startNewQuery(string $db_name = ''): Query
    {

        try {
            return new Query($this->db->get($db_name));
        } catch (InvalidDatabaseException $e) {
            throw new UnexpectedApiException($e->getMessage());
        }

    }

    /**
     * Query a collection using a query builder.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/_docs/query-builder.md
     *
     * @param Query $query
     * @param array $args (Allowed keys: select, where, orderBy, limit, offset)
     * @param array $selectable_cols
     * @param string $default_order_by
     * @param int $max_size
     * @param array $json_cols
     * @return array (Returned keys: data, meta)
     * @throws BadRequestException
     */
    protected function queryCollection(Query $query, array $args, array $selectable_cols, string $default_order_by, int $max_size, array $json_cols = []): array
    {

        // select

        $fields = Arr::get($args, 'select', ['*']);

        if (empty($fields) || in_array('*', $fields)) { // Select all allowed

            $fields = array_keys($selectable_cols);

        } else { // Validate fields

            if (!empty(array_diff($fields, array_keys($selectable_cols)))) {
                throw new BadRequestException('Invalid field(s)');
            }

        }

        foreach ($fields as $k => $field) {
            $fields[$k] = $selectable_cols[$field];
        }

        // orderBy

        $order_by = Arr::get($args, 'orderBy', [$default_order_by]);

        foreach ($order_by as $k => $field) {

            $field = str_replace('.', '->', $field);
            $order_by[$k] = $field;

            if (!in_array(ltrim(strtok($field, '->'), '-+'), array_keys($selectable_cols))) {
                throw new BadRequestException('Invalid orderBy field: Field (' . ltrim($field, '-+') . ') does not exist');
            }

        }

        // limit & offset

        $limit = ceil(min(Arr::get($args, 'limit', $max_size), $max_size));
        $offset = ceil(min(Arr::get($args, 'offset', 0), $max_size));

        $query->select($fields)
            ->limit($limit)
            ->offset($offset)
            ->orderBy($order_by);

        // where

        if (isset($args['where']) && is_array($args['where'])) {

            foreach ($args['where'] as $column => $filter) {

                $column = str_replace('.', '->', $column);

                if (!in_array(strtok($column, '->'), array_keys($selectable_cols))) {
                    throw new BadRequestException('Invalid where key: Field (' . $column . ') does not exist');
                }

                foreach ($filter as $operator => $value) {

                    try {
                        $query->where($column, $operator, $value);
                    } catch (QueryException) {
                        throw new BadRequestException('Invalid operator (' . $operator . ') for field (' . $column . ')');
                    }

                }

            }

        }

        // Fetch results

        try {
            $results = $query->get();
        } catch (PDOException) {
            throw new BadRequestException('Invalid request');
        }

        $total = $query->getTotalRows();

        // json_decode

        $json_cols = Arr::getAnyValues($fields, $json_cols);

        if (!empty($json_cols)) {

            foreach ($results as $k => $v) {

                foreach ($json_cols as $field) {

                    if ($results[$k][$field]) { // May be NULL
                        $results[$k][$field] = json_decode($v[$field], true);
                    }

                }

            }

        }

        return [
            'data' => $results,
            'meta' => [
                'count' => count($results),
                'total' => $total,
                'pages' => ceil($total / $limit),
                'pageSize' => $limit,
                'pageNumber' => floor(($offset / $limit) + 1)
            ]
        ];

    }

    /**
     * Return a mock empty collection from queryCollection method.
     *
     * @param array $args
     * @param int $max_size
     * @return array
     */
    protected function returnEmptyCollection(array $args, int $max_size): array
    {

        // limit & offset

        $limit = ceil(min(Arr::get($args, 'limit', $max_size), $max_size));
        $offset = ceil(min(Arr::get($args, 'offset', 0), $max_size));

        return [
            'data' => [],
            'meta' => [
                'count' => 0,
                'total' => 0,
                'pages' => 0,
                'pageSize' => $limit,
                'pageNumber' => floor(($offset / $limit) + 1)
            ]
        ];

    }

    /**
     * Query a single row using a query builder.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/_docs/query-builder.md
     *
     * @param Query $query
     * @param array $cols
     * @param array $selectable_cols
     * @param array $json_cols
     * @return array
     * @throws BadRequestException
     * @throws NotFoundException
     */
    public function querySingle(Query $query, array $cols, array $selectable_cols, array $json_cols = []): array {

        if (empty($cols) || in_array('*', $cols)) {

            $cols = array_keys($selectable_cols);

        } else { // Validate fields

            if (!empty(array_diff($cols, array_keys($selectable_cols)))) {
                throw new BadRequestException('Invalid field(s)');
            }

        }

        foreach ($cols as $k => $col) {
            $cols[$k] = $selectable_cols[$col];
        }

        $results = $query->select($cols)->row();

        if (!$results) {
            throw new NotFoundException('Does not exist');
        }

        // json_decode

        $json_cols = Arr::getAnyValues($cols, $json_cols);

        if (!empty($json_cols)) {

            foreach ($json_cols as $col) {

                if ($results[$col]) { // May be NULL
                    $results[$col] = json_decode($results[$col], true);
                }

            }

        }

        return $results;

    }

    /**
     * Return a secure password hash using a plaintext password and user-specific salt.
     *
     * @param string $password (Plaintext password)
     * @param string $salt (User-specific salt)
     *
     * @return string (Hashed password)
     */
    protected function hashPassword(string $password, string $salt): string
    {

        $salt = hash_hmac('sha512', $salt, App::getConfig('app.key')); // Database & server supplied
        $salt = hash_hmac('sha512', $salt, $password); // User supplied

        return password_hash($salt . $password, PASSWORD_DEFAULT); // Create a one-way hash, verified using password_verify

    }

    /**
     * Verify a plaintext password and user-specific salt against a hashed password.
     *
     * @param string $password
     * @param string $salt
     * @param string $hashed_password
     *
     * @return bool
     */
    protected function verifyPassword(string $password, string $salt, string $hashed_password): bool
    {

        $salt = hash_hmac('sha512', $salt, App::getConfig('app.key')); // Database & server supplied
        $salt = hash_hmac('sha512', $salt, $password); // User supplied

        return (password_verify($salt . $password, $hashed_password));

    }

}