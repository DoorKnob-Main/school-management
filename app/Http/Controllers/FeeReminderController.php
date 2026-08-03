<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Interfaces\ReminderInterface;
use App\Interfaces\SchoolClassInterface;
use App\Interfaces\SchoolSessionInterface;
use App\Interfaces\SectionInterface;
use App\Services\MessageService;
use App\Http\Requests\FeeReminderSendRequest;
use App\Traits\SchoolSession;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;

class FeeReminderController extends Controller
{
    use SchoolSession;

    protected $reminderRepository;
    protected $messageService;
    protected $schoolSessionRepository;
    protected $schoolClassRepository;
    protected $sectionRepository;

    public function __construct(
        ReminderInterface $reminderRepository,
        MessageService $messageService,
        SchoolSessionInterface $schoolSessionRepository,
        SchoolClassInterface $schoolClassRepository,
        SectionInterface $sectionRepository
    ) {
        $this->middleware(['can:send fee reminder']);

        $this->reminderRepository     = $reminderRepository;
        $this->messageService         = $messageService;
        $this->schoolSessionRepository = $schoolSessionRepository;
        $this->schoolClassRepository   = $schoolClassRepository;
        $this->sectionRepository       = $sectionRepository;
    }

    public function index(Request $request)
    {
        $current_school_session_id = $this->getSchoolCurrentSession();

        $classes = $this->schoolClassRepository->getAllBySession($current_school_session_id);
        $selected_class_id = $request->get('class_id', 0);
        $selected_section_id = $request->get('section_id', 0);

        $sections = collect();
        if ($selected_class_id > 0) {
            $sections = $this->sectionRepository->getAllByClassId($selected_class_id);
        }

        $pendingStudents = $this->reminderRepository->getPendingStudents(
            $current_school_session_id,
            $selected_class_id > 0 ? $selected_class_id : null,
            $selected_section_id > 0 ? $selected_section_id : null
        );

        $reminderHistory = $this->reminderRepository->getHistory($current_school_session_id);

        $defaultTemplate = "Dear Parent,\n"
            . "This is a reminder that {due_amount} is pending for {student_name} studying in {class_name}.\n"
            . "Kindly submit the pending fee at the earliest.\n"
            . "Thank you.";

        $data = [
            'current_school_session_id' => $current_school_session_id,
            'classes'             => $classes,
            'sections'            => $sections,
            'selected_class_id'   => $selected_class_id,
            'selected_section_id' => $selected_section_id,
            'pendingStudents'     => $pendingStudents,
            'reminderHistory'     => $reminderHistory,
            'defaultTemplate'     => $defaultTemplate,
        ];

        return view('finance.fee-reminder.index', $data);
    }

    public function send(FeeReminderSendRequest $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $channel = $request->input('channel');
            $template = $request->input('message_template');
            $studentIds = $request->input('student_ids');

            $recipientsLog = [];

            foreach ($studentIds as $studentId) {
                $student = User::with(['parent_info'])->find($studentId);
                if (!$student) continue;

                $pendingStudents = $this->reminderRepository->getPendingStudents($sessionId);
                $studentRecord = collect($pendingStudents)->firstWhere('student.id', $studentId);

                if (!$studentRecord) continue;

                $formattedData = [
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'father_name'  => $studentRecord['father_name'],
                    'mother_name'  => $studentRecord['mother_name'],
                    'class_name'   => $studentRecord['school_class']->name ?? '',
                    'section_name' => $studentRecord['section']->name ?? '',
                    'due_amount'   => $studentRecord['due_amount'],
                    'school_name'  => config('app.name', 'Unifiedtransform'),
                    'due_date'     => date('Y-m-d'),
                ];

                $formattedMessage = $this->messageService->formatMessage($template, $formattedData);
                $phone = $studentRecord['father_phone'];

                $dispatchResult = $this->messageService->sendReminder($phone, $formattedMessage, $channel);

                $recipientsLog[] = [
                    'student_id'        => $studentId,
                    'phone_used'        => $phone,
                    'due_amount'        => $studentRecord['due_amount'],
                    'status'            => 'Sent',
                    'provider_response' => $dispatchResult['response_message'],
                ];
            }

            $this->reminderRepository->logReminder($sessionId, $channel, $template, $recipientsLog);

            return back()->with('status', 'Fee reminders sent successfully to ' . count($recipientsLog) . ' parent(s)!');
        } catch (Exception $e) {
            return back()->withError($e->getMessage());
        }
    }
}
