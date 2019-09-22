<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https://github.com/adhocore>
 *
 * Licensed under MIT license.
 */

namespace PhalconExt\Cli\Task;

use Ahc\Cli\Input\Command;
use Ahc\Cron\Expression;
use Phalcon\Cli\Task;
use PhalconExt\Di\ProvidesDi;

class ScheduleTask extends Task
{
    use ProvidesDi;

    public function onConstruct()
    {
        ($console = $this->di('console'))
            ->command('schedule:list', 'List scheduled tasks (if any)', false)
                ->tap($console)
            ->command('schedule:run', 'Run scheduled tasks that are due', true);
    }

    public function listAction()
    {
        $io = $this->di('interactor');

        if ([] === $tasks = $this->di('console')->scheduled()) {
            $io->infoBgRed('No scheduled tasks', true);

            return;
        }

        $io->boldGreen('Schedules:', true);

        $maxLen = \max(\array_map('strlen', \array_keys($tasks)));

        foreach ($tasks as $taskId => $schedule) {
            $io->bold('  ' . \str_pad($taskId, $maxLen + 2))->comment($schedule, true);
        }
    }

    public function runAction()
    {
        $io = $this->di('interactor');

        if ([] === $tasks =  $this->dueTasks()) {
            return $io->infoBgRed('No due tasks for now', true);
        }

        $params = $this->di(Command::class)->values();

        foreach ($tasks as list($task, $action)) {
            $io->line('--------------------', true)
                ->yellow($task . ':' . $action, true)
                ->line('--------------------', true);

            $this->di('console')->doHandle(\compact('task', 'action', 'params'));
        }
    }

    protected function dueTasks(): array
    {
        if ([] === $tasks = $this->console->scheduled()) {
            return [];
        }

        $dues = [];
        $now  = \time();

        foreach ((new Expression)->filter($tasks, $now) as $taskId) {
            $dues[] = \explode(':', $taskId) + ['', 'main'];
        }

        return $dues;
    }
}
