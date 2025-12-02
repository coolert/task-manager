<?php

return [
    'workers' => [
        'default' => [
            'queues' => [
                'task.main.queue',
            ],
            'memory_limit' => 128 * 1024 * 1024,
        ],
    ],
];
