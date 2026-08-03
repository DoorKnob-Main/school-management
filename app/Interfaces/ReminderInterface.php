<?php

namespace App\Interfaces;

interface ReminderInterface
{
    public function getPendingStudents($sessionId, $classId = null, $sectionId = null);
    public function logReminder($sessionId, $channel, $messageTemplate, array $recipients);
    public function getHistory($sessionId = null);
}
