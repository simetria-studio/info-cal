<?php

namespace App\Http\Controllers;

use DateTime;
use Exception;
use DateTimeZone;
use Carbon\Carbon;
use Stripe\Stripe;
use App\Models\User;
use App\Models\Event;
use Laracasts\Flash\Flash;
use App\Models\Transaction;
use App\Models\UserSetting;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\EventSchedule;
use Spatie\CalendarLinks\Link;
use App\Mail\SendVerifyOtpMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use App\Mail\CancelEventScheduleMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Stripe\Exception\ApiErrorException;
use App\Http\Requests\CreateVerifyOtpRequest;
use App\Repositories\ScheduleEventRepository;
use Illuminate\Contracts\Foundation\Application;
use App\Http\Requests\CreateEventScheduleRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ScheduleEventController extends AppBaseController
{
    /** @var ScheduleEventRepository */
    private $scheduleEventRepo;

    public function __construct(ScheduleEventRepository $scheduleEventRepository)
    {
        $this->scheduleEventRepo = $scheduleEventRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $eventStatus = $request->today;

        return view('schedule_event.index', compact('eventStatus'));
    }

    /**
     * Store a newly created resource in storage.
     *
     *
     * @throws ApiErrorException
     */


    public function createEvent(Request $request)
    {
        $input = $request->all();

        $iodapay = new IodaPayController();
        $userStripeSecret = UserSetting::whereUserId($input['user_id'])
            ->where('key', '=', 'stripe_secret')->first();
        $userStripePublic = UserSetting::whereUserId($input['user_id'])
            ->where('key', '=', 'stripe_key')->first();

        $event = Event::whereId($input['event_id'])->first();
        $value = (string) $event->payable_amount; // Converte o valor para string
        $reference = Str::random(6);
        $cpf = str_replace(['.', '-'], '', $input['cpf']);

        $payment = $iodapay->createPayment(
            $userStripeSecret->value,
            $userStripePublic->value,
            $input['email'],
            $cpf,
            $input['name'],
            'CPF',
            $value, // Valor convertido para string
            3600,
            $reference
        );

        // Decodificando o corpo da resposta
        $paymentResponse = json_decode($payment, true);

        \Log::info('Payment Response: ', $paymentResponse);

        $transaction = Transaction::create([
            'user_id' => $input['user_id'],
            'transaction_id' => $paymentResponse['transactionId'],
            'amount' => $value,
            'type' => 1,
            'status_pay' => 0,
            'schedule_event_id' => $input['event_id'], // Adicionando o ID do evento agendado
            'meta' => json_encode($paymentResponse)
        ]);

        $bookedSlots = EventSchedule::whereEventId($input['event_id'])
            ->whereDate('schedule_date', '=', $input['schedule_date'])
            ->where('slot_time', '=', $input['slot_time'])
            ->where('status', '!=', EventSchedule::CANCELLED)
            ->pluck('slot_time')->toArray();

        $eventSchedule = $this->scheduleEventRepo->store($input);

        if ($bookedSlots) {
            return $this->sendError('This Event schedule is already booked.');
        }


        return response()->json($paymentResponse);
    }



    public function store(CreateEventScheduleRequest $request): JsonResponse
    {
        $outData = [];
        $input = $request->all();

        \Log::info('Event Schedule Input: ', $input);

        if (assignPlanFeatures($input['user_id'])->schedule_events <= getActiveScheduleEventsCount($input['user_id'])) {
            return $this->sendError(__('messages.success_message.schedule_events_upgrade'));
        }

        $bookedSlots = EventSchedule::whereEventId($input['event_id'])
            ->whereDate('schedule_date', '=', $input['schedule_date'])
            ->where('slot_time', '=', $input['slot_time'])
            ->where('status', '!=', EventSchedule::CANCELLED)
            ->pluck('slot_time')->toArray();

        if ($bookedSlots) {
            return $this->sendError('This Event schedule is already booked.');
        }

        $eventSchedule = $this->scheduleEventRepo->store($input);

        $outData['redirectUrl'] = getSlotConfirmPageUrl($eventSchedule);
        $outData['eventType'] = $eventSchedule->event->event_type;

        if ($eventSchedule->event->event_type == Event::PAID) {
            $eventSchedule->update(['status' => EventSchedule::HOLD]);
            if ($eventSchedule->payment_type == EventSchedule::STRIPE) {
                $result = $this->scheduleEventRepo->createSession($eventSchedule);

                return $this->sendResponse([
                    'scheduleEventId' => $eventSchedule->id,
                    'redirectUrl' => $outData['redirectUrl'],
                    'eventType' => $outData['eventType'],
                    $result,
                ], 'Stripe session created successfully.');
            } else {
                return $this->sendResponse([
                    'scheduleEventId' => $eventSchedule->id,
                    'redirectUrl' => $outData['redirectUrl'],
                    'eventType' => $outData['eventType'],
                ], 'retrieved data successfully.');
            }
        }

        return $this->sendResponse($outData, 'Event schedule created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @return Application|Factory|View|RedirectResponse
     */
    public function show($id)
    {
        $eventSchedule = EventSchedule::findOrFail($id);
        if (getLogInUserId() !== $eventSchedule->user_id) {
            return redirect()->back();
        }

        return view('schedule_event.show', compact('eventSchedule'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EventSchedule $eventSchedule): JsonResponse
    {
        $eventSchedule = $eventSchedule->delete();

        return $this->sendSuccess('Scheduled event deleted successfully.');
    }

    /**
     * @return Application|Factory|View
     */
    public function confirmEventSchedule($domain_url, $event_link, $uuid, Request $request): \Illuminate\View\View
    {
        $cancelScheduleEvent = $request->get('cancel');
        $eventSchedule = EventSchedule::with(['event', 'user', 'userGoogleEventSchedule'])->whereUuid($uuid)->first();

        if (!$eventSchedule) {
            abort(404);
        }

        $timeZone = isset(getLoginUser()->timezone) ?
            User::TIME_ZONE_ARRAY[getLoginUser()->timezone] : User::TIME_ZONE_ARRAY[getTimeZone()];
        date_default_timezone_set($timeZone);
        $time = explode(' -', $eventSchedule->slot_time);
        $startTime = Carbon::parse($eventSchedule->schedule_date . ' ' . $time[0])->format('Y-m-d H:i');
        $endTime = Carbon::parse($eventSchedule->schedule_date . ' ' . $time[1])->format('Y-m-d H:i');
        $from = DateTime::createFromFormat('Y-m-d H:i', $startTime);
        $to = DateTime::createFromFormat('Y-m-d H:i', $endTime);
        $description = $event->eventSchedule->description ?? '';

        $link = Link::create($eventSchedule->event->name, $from, $to)
            ->description($description);
        $downloadIcsFileLink = $link->ics();

        return view(
            'events.confirm_event_schedule',
            compact('eventSchedule', 'downloadIcsFileLink', 'cancelScheduleEvent')
        );
    }

    /**
     * @return mixed
     */
    public function cancelScheduledEvent(Request $request, $id)
    {
        $scheduledEventIds = EventSchedule::whereUserId(getLogInUserId())->pluck('id')->toArray();

        if (!in_array($id, $scheduledEventIds)) {
            return $this->sendError(__('messages.schedule_event.this_schedule_event_can_not_be_cancelled'));
        }

        $cancelReason = $request->get('cancel_reason');
        $scheduledEvent = $this->scheduleEventRepo->cancelScheduleEvent($cancelReason, $id);

        return $this->sendSuccess('Cancel Scheduled Event Successfully.');
    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function paymentSuccess(Request $request): RedirectResponse
    {
        $sessionId = $request->get('session_id');
        $eventScheduleUserID = $request->get('eventScheduleUserID');
        if (empty($sessionId)) {
            throw new UnprocessableEntityHttpException('session id is required');
        }

        try {
            DB::beginTransaction();
            Stripe::setApiKey(getStripeSecretKey($eventScheduleUserID));
            $sessionData = Session::retrieve($sessionId);
            $scheduleEvent = EventSchedule::find($sessionData->client_reference_id);
            $user = User::find($scheduleEvent->user_id);

            $transaction = [
                'user_id' => $user->id,
                'transaction_id' => $sessionData->id,
                'schedule_event_id' => $sessionData->client_reference_id,
                'amount' => $scheduleEvent->event->payable_amount,
                'type' => $scheduleEvent->payment_type,
                'meta' => $sessionData,
            ];

            Transaction::create($transaction);
            $scheduleEvent->update(['status' => EventSchedule::BOOKED]);
            $redirectUrl = getSlotConfirmPageUrl($scheduleEvent);

            $this->scheduleEventRepo->scheduleEventMails($scheduleEvent, $scheduleEvent->email, true);

            DB::commit();
            Flash::success(__('messages.success_message.payment_completed'));

            return redirect(url($redirectUrl));
        } catch (Exception $e) {
            DB::rollBack();
            throw new UnprocessableEntityHttpException($e->getMessage());
        }
    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function handleFailedPayment(Request $request): RedirectResponse
    {
        $eventScheduleUserID = $request->get('eventScheduleUserID');
        Stripe::setApiKey(getStripeSecretKey($eventScheduleUserID));

        Flash::error(__('messages.success_message.payment_not_completed'));

        return redirect(route('events.index'));
    }

    /**
     * @return Application|Redirector|RedirectResponse
     *
     * @throws Exception
     */
    public function verifyOTP(Request $request): RedirectResponse
    {
        $data = [];
        $id = $request->get('schedule_event_id');
        $eventSchedule = EventSchedule::findOrFail($id);
        $data['otp'] = EventSchedule::generateOTP();
        $data['name'] = $eventSchedule->name;
        $data['email'] = $eventSchedule->email;
        $eventSchedule->update(['otp' => $data['otp']]);

        Mail::to($data['email'])->send(new SendVerifyOtpMail($data));

        Flash::success(__('messages.success_message.otp_sent'));

        return redirect(route(
            'verify.otp.page',
            [$eventSchedule->event->user->domain_url, $eventSchedule->event->event_link, $eventSchedule->uuid]
        ));
    }

    /**
     * @return Application|Factory|View
     */
    public function verifyOTPPage($domain_url, $event_link, $uuid): \Illuminate\View\View
    {
        return view('schedule_event.verify_otp_page');
    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function checkGivenOTP(CreateVerifyOtpRequest $request): RedirectResponse
    {
        $otp = $request->get('otp');
        $eventSchedule = EventSchedule::whereOtp($otp)->first();

        if (empty($eventSchedule)) {
            Flash::error(__('messages.success_message.otp_not_matched'));

            return redirect()->back();
        }

        $eventSchedule->update(['status' => EventSchedule::CANCELLED]);
        $data['name'] = $eventSchedule->user->full_name;
        $data['email'] = $eventSchedule->user->email;
        $data['schedule_date'] = $eventSchedule->schedule_date;
        $data['scheduleTime'] = $eventSchedule->slot_time;

        Flash::success('Your schedule event cancel successfully.');

        Mail::to($data['email'])->send(new CancelEventScheduleMail($data));

        return redirect(route(
            'sc.cancel',
            [$eventSchedule->event->user->domain_url, $eventSchedule->event->event_link, $eventSchedule->uuid]
        ));
    }

    /**
     * @return Application|RedirectResponse|Redirector
     */
    public function addCalendar($id): RedirectResponse
    {


        /** @var EventSchedule $scheduleEvent */
        $scheduleEvent = EventSchedule::with('user')->find($id);
        // $timezoneWiseTime = getTimezoneWiseUserSlotTime($scheduleEvent->user, $scheduleEvent->slot_time);
        $time = explode(' - ', $scheduleEvent->slot_time);
        $startTime = Carbon::parse($scheduleEvent->schedule_date . ' ' . $time[0])->format('Y-m-d H:i');
        $endTime = Carbon::parse($scheduleEvent->schedule_date . ' ' . $time[1])->format('Y-m-d H:i');

        $link = Link::create(
            $scheduleEvent->event->name,
            DateTime::createFromFormat('Y-m-d H:i', $startTime, new DateTimeZone(User::TIME_ZONE_ARRAY[$scheduleEvent->user->timezone])),
            DateTime::createFromFormat('Y-m-d H:i', $endTime, new DateTimeZone(User::TIME_ZONE_ARRAY[$scheduleEvent->user->timezone]))
        )->google();

        return redirect(url($link));
    }

    /**`
     * @return Application|Factory|View
     */
    public function cancelPage($domain_url, $event_link, $uuid): \Illuminate\View\View
    {
        $eventSchedule = EventSchedule::with(['event', 'user'])->whereUuid($uuid)->first();

        if (empty($eventSchedule)) {
            abort(404);
        }

        return view('schedule_event.cancel_page', compact('eventSchedule'));
    }

    public function removeHoldStatusUser($id): RedirectResponse
    {
        $scheduledEvent = EventSchedule::whereId($id)->whereUserId(getLogInUserId())->firstOrFail();

        $createdTime = Carbon::parse($scheduledEvent->created_at);
        $diff = $createdTime->diffInMinutes(Carbon::now());
        if ($diff > 30) {
            $scheduledEvent->update(['status' => EventSchedule::CANCELLED]);
        }

        Flash::success(__('messages.hold_status_remove'));

        return redirect(route('scheduled-events.show', $scheduledEvent->id));
    }
}
