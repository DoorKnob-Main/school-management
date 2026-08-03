<?php

namespace App\Repositories;

use App\Interfaces\ReminderInterface;
use App\Models\FeeReminder;
use App\Models\FeeReminderRecipient;
use App\Models\Promotion;
use App\Models\User;
use App\Repositories\PaymentRepository;
use Illuminate\Support\Facades\DB;

class ReminderRepository implements ReminderInterface
{
    protected $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    public function getPendingStudents($sessionId, $classId = null, $sectionId = null)
    {
        $query = Promotion::with(['student.parent_info', 'schoolClass', 'section'])
            ->where('session_id', $sessionId);

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        $promotions = $query->get();
        $pendingStudents = [];

        foreach ($promotions as $promo) {
            if (!$promo->student) continue;

            $summary = $this->paymentRepository->getStudentFeeSummary($promo->student_id, $sessionId);

            if ($summary['remaining_due'] > 0) {
                $pendingStudents[] = [
                    'promotion'     => $promo,
                    'student'       => $promo->student,
                    'school_class'  => $promo->schoolClass,
                    'section'       => $promo->section,
                    'father_name'   => $promo->student->parent_info->father_name ?? 'Parent',
                    'mother_name'   => $promo->student->parent_info->mother_name ?? '',
                    'father_phone'  => $promo->student->parent_info->father_phone ?? ($promo->student->phone ?? ''),
                    'due_amount'    => $summary['remaining_due'],
                    'total_fee'     => $summary['total_fee'],
                    'paid_amount'   => $summary['paid_amount'],
                    'status'        => $summary['status'],
                ];
            }
        }

        return $pendingStudents;
    }

    public function logReminder($sessionId, $channel, $messageTemplate, array $recipients)
    {
        return DB::transaction(function () use ($sessionId, $channel, $messageTemplate, $recipients) {
            $reminder = FeeReminder::create([
                'session_id'       => $sessionId,
                'channel'          => $channel,
                'message_template' => $messageTemplate,
                'created_by'       => auth()->id(),
            ]);

            foreach ($recipients as $recipient) {
                FeeReminderRecipient::create([
                    'fee_reminder_id'   => $reminder->id,
                    'student_id'        => $recipient['student_id'],
                    'phone_used'        => $recipient['phone_used'],
                    'due_amount'        => $recipient['due_amount'],
                    'status'            => $recipient['status'] ?? 'Sent',
                    'provider_response' => $recipient['provider_response'] ?? 'Logged successfully',
                ]);
            }

            return $reminder;
        });
    }

    public function getHistory($sessionId = null)
    {
        $query = FeeReminder::with(['creator', 'session', 'recipients.student.parent_info']);

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
