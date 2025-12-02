<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('outbox:dispatch')->everyMinute();
