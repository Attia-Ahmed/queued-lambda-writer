<?php

require __DIR__.'/vendor/autoload.php';

use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsHandler;
use Illuminate\Database\Capsule\Manager as DB;

class Handler extends SqsHandler
{

    public function init()
    {

        $capsule = new DB;

        $capsule->addConnection([
            'driver'    => 'pgsql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '5432'),
            'database'  => env('DB_DATABASE', 'forge'),
            'username'  => env('DB_USERNAME', 'forge'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();

    }

    public function handleSqs(SqsEvent $event, Context $context): void
    {
        $this->init();
        foreach ($event->getRecords() as $record) {
            $body = $record->getBody();
            echo $body."\n";
            $data = json_decode($body, true);
            if (!$data) {
                return;
            }
            $type = $data['type'] ?? null;
            match ($type) {
                'sql_insert' => $this->insert($data['table'], $data['content']),
                default => throw new InvalidArgumentException()
            };
        }
    }

    private function insert(string $table, array $content)
    {
        DB::table($table)->insert($content);
    }
}

return new Handler();