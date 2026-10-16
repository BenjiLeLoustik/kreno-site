<?php

namespace App\Log\Service;

use App\Entity\AppLog;
use App\Enum\Log\AppLogLevel;
use App\Enum\Log\AppLogStatus;
use App\Log\Helper\AppLogHelper;
use DateTimeImmutable;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Orm\Type\Type;
use RuntimeException;
use Throwable;

class AppLogRecorder
{
    public function __construct(
        private OrmManagerInterface $orm,
    ) {
    }

    public function record(Throwable $throwable, Request $request, int $statusCode, ?int $userId = null, string $root = '', AppLogLevel $level = AppLogLevel::ERROR): void
    {
        try {
            $this->write($throwable, $request, $statusCode, $userId, $root, $level);
        } catch (Throwable $exception) {
            error_log(sprintf('[AppLog] %s: %s (journal impossible pour %s: %s)', $exception::class, $exception->getMessage(), $throwable::class, $throwable->getMessage()));
        }
    }

    private function write(Throwable $throwable, Request $request, int $statusCode, ?int $userId, string $root, AppLogLevel $level): void
    {
        $connection = $this->orm->getConnection();
        $metadata = $this->orm->getMetadata(AppLog::class);
        $table = $metadata->getTable();
        $column = static fn (string $field): string => $metadata->getColumnName($field);
        $now = Type::toDatabase(new DateTimeImmutable(), Type::DATETIME_IMMUTABLE);
        $idColumn = $metadata->getIdentifierColumn();
        $fingerprint = AppLogHelper::fingerprint($throwable, $root);

        while ($connection->inTransaction()) {
            $connection->rollBack();
        }

        $last = [
            $column('message') => mb_substr($throwable->getMessage(), 0, 5000),
            $column('trace') => AppLogHelper::trace($throwable, $root),
            $column('statusCode') => $statusCode,
            $column('method') => $request->getMethod(),
            $column('url') => AppLogHelper::path($request->getPath()),
            $column('user') => $userId,
            $column('lastSeenAt') => $now,
            $column('updatedAt') => $now,
        ];

        $find = static fn (): ?array => $connection->fetchAssociative(
            sprintf('SELECT %s, %s FROM %s WHERE %s = ?', $connection->quoteIdentifier($idColumn), $connection->quoteIdentifier($column('status')), $connection->quoteIdentifier($table), $connection->quoteIdentifier($column('fingerprint'))),
            [$fingerprint],
        );
        $existing = $find();

        if ($existing === null) {
            try {
                $connection->insert($table, $last + [
                        $column('fingerprint') => $fingerprint,
                        $column('level') => $level->value,
                        $column('status') => AppLogStatus::NEW->value,
                        $column('exceptionClass') => mb_substr($throwable::class, 0, 255),
                        $column('file') => AppLogHelper::file($throwable->getFile(), $root),
                        $column('line') => $throwable->getLine(),
                        $column('occurrences') => 1,
                        $column('firstSeenAt') => $now,
                        $column('createdAt') => $now,
                    ]);

                return;
            } catch (Throwable) {
                $existing = $find();

                if ($existing === null) {
                    throw new RuntimeException('Impossible d\'enregistrer l\'erreur.');
                }
            }
        }

        $id = $existing[$idColumn];
        $status = $existing[$column('status')];

        if ($status === AppLogStatus::RESOLVED->value) {
            $last[$column('status')] = AppLogStatus::NEW->value;
        }

        $connection->update($table, $last, [$idColumn => $id]);
        $connection->executeStatement(
            sprintf('UPDATE %1$s SET %2$s = %2$s + 1 WHERE %3$s = ?', $connection->quoteIdentifier($table), $connection->quoteIdentifier($column('occurrences')), $connection->quoteIdentifier($idColumn)),
            [$id],
        );
    }
}