<?php

namespace App\Log\Service;

use App\Entity\AppLog;
use App\Log\Enum\AppLogLevel;
use App\Log\Enum\AppLogStatus;
use App\Log\Exception\AppLogNotRecordedException;
use App\Log\Helper\AppLogHelper;
use DateTimeImmutable;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Orm\Type\Type;
use Throwable;

class AppLogRecorder
{
    public const MESSAGE_LENGTH = 5000;

    public const CLASS_LENGTH = 255;

    public function __construct(
        private OrmManagerInterface $orm,
    ) {
    }

    public function record(
        Throwable $throwable,
        Request $request,
        int $statusCode,
        ?int $userId = null,
        string $root = '',
        AppLogLevel $level = AppLogLevel::ERROR,
    ): void {
        try {
            $this->write($throwable, $request, $statusCode, $userId, $root, $level);
        } catch (Throwable $exception) {
            error_log($this->failure($exception, $throwable));
        }
    }

    private function write(
        Throwable $throwable,
        Request $request,
        int $statusCode,
        ?int $userId,
        string $root,
        AppLogLevel $level,
    ): void {
        $this->rollBackAll();

        $fingerprint = AppLogHelper::fingerprint($throwable, $root);
        $now = Type::toDatabase(new DateTimeImmutable(), Type::DATETIME_IMMUTABLE);
        $last = $this->lastValues($throwable, $request, $statusCode, $userId, $root, $now);
        $first = $this->firstValues($throwable, $fingerprint, $root, $level, $now);
        $existing = $this->find($fingerprint);

        if ($existing === null && $this->insert($last + $first)) {
            return;
        }

        $existing ??= $this->find($fingerprint);

        if ($existing === null) {
            throw new AppLogNotRecordedException();
        }

        $this->update($existing, $last);
    }

    private function rollBackAll(): void
    {
        $connection = $this->orm->getConnection();

        while ($connection->inTransaction()) {
            $connection->rollBack();
        }
    }

    private function lastValues(
        Throwable $throwable,
        Request $request,
        int $statusCode,
        ?int $userId,
        string $root,
        mixed $now,
    ): array {
        return [
            $this->column('message') => mb_substr($throwable->getMessage(), 0, self::MESSAGE_LENGTH),
            $this->column('trace') => AppLogHelper::trace($throwable, $root),
            $this->column('statusCode') => $statusCode,
            $this->column('method') => $request->getMethod(),
            $this->column('url') => AppLogHelper::path($request->getPath()),
            $this->column('user') => $userId,
            $this->column('lastSeenAt') => $now,
            $this->column('updatedAt') => $now,
        ];
    }

    private function firstValues(
        Throwable $throwable,
        string $fingerprint,
        string $root,
        AppLogLevel $level,
        mixed $now,
    ): array {
        return [
            $this->column('fingerprint') => $fingerprint,
            $this->column('level') => $level->value,
            $this->column('status') => AppLogStatus::NEW->value,
            $this->column('exceptionClass') => mb_substr($throwable::class, 0, self::CLASS_LENGTH),
            $this->column('file') => AppLogHelper::file($throwable->getFile(), $root),
            $this->column('line') => $throwable->getLine(),
            $this->column('occurrences') => 1,
            $this->column('firstSeenAt') => $now,
            $this->column('createdAt') => $now,
        ];
    }

    private function find(string $fingerprint): ?array
    {
        $sql = sprintf(
            'SELECT %s, %s FROM %s WHERE %s = ?',
            $this->quote($this->idColumn()),
            $this->quote($this->column('status')),
            $this->quote($this->table()),
            $this->quote($this->column('fingerprint')),
        );

        return $this->orm->getConnection()->fetchAssociative($sql, [$fingerprint]);
    }

    private function insert(array $values): bool
    {
        try {
            $this->orm->getConnection()->insert($this->table(), $values);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function update(array $existing, array $values): void
    {
        $connection = $this->orm->getConnection();
        $id = $existing[$this->idColumn()];

        if ($existing[$this->column('status')] === AppLogStatus::RESOLVED->value) {
            $values[$this->column('status')] = AppLogStatus::NEW->value;
        }

        $connection->update($this->table(), $values, [$this->idColumn() => $id]);
        $connection->executeStatement($this->incrementSql(), [$id]);
    }

    private function incrementSql(): string
    {
        return sprintf(
            'UPDATE %1$s SET %2$s = %2$s + 1 WHERE %3$s = ?',
            $this->quote($this->table()),
            $this->quote($this->column('occurrences')),
            $this->quote($this->idColumn()),
        );
    }

    private function failure(Throwable $exception, Throwable $throwable): string
    {
        return sprintf(
            '[AppLog] %s: %s (journal impossible pour %s: %s)',
            $exception::class,
            $exception->getMessage(),
            $throwable::class,
            $throwable->getMessage(),
        );
    }

    private function table(): string
    {
        return $this->orm->getMetadata(AppLog::class)->getTable();
    }

    private function idColumn(): string
    {
        return $this->orm->getMetadata(AppLog::class)->getIdentifierColumn();
    }

    private function column(string $field): string
    {
        return $this->orm->getMetadata(AppLog::class)->getColumnName($field);
    }

    private function quote(string $identifier): string
    {
        return $this->orm->getConnection()->quoteIdentifier($identifier);
    }
}