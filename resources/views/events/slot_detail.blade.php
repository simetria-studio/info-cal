@extends('layouts.auth')
@section('title')
    {{ __('messages.slot_schedule') }}
@endsection
@section('content')
    <div class="container">
        <div class="row">
            <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
                <div class="mb-5">
                    @include('layouts.errors')
                    @include('flash::message')
                </div>
                <div>
                    @php
                        $styleCss = 'style';
                    @endphp
                    <div class="w-lg-900px bg-white rounded shadow-sm">
                        <div class="h-auto">
                            <div class="border-bottom">
                                <div class="card">
                                    <div class="d-sm-flex h-750px main-details">
                                        <div class="border-end py-7 px-7 col-lg-5 col">
                                            <div class="mb-3">
                                                <p class="fs-5 mb-2">
                                                    {{ $event->user->first_name . ' ' . $event->user->last_name }}</p>
                                                <span class="fs-1 fw-bold">{{ $event->name }}</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-4">
                                                <i class="fas fa-clock me-2 fs-2 text-primary"></i>
                                                <span class="fs-4 text-primary fw-bold">{{ $event->slot_time }} min</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-4">
                                                @if ($event->event_location == \App\Models\Event::IN_PERSON_MEETING)
                                                    @php($location = json_decode($event->location_meta))
                                                    <i class="fas fa-map-marker-alt me-2 fs-2 text-primary"></i>
                                                    <span class="fs-4 text-primary fw-bold">{{ $location[1] }}</span>
                                                @elseif($event->event_location == \App\Models\Event::PHONE_CALL)
                                                    @php($phoneCallNumber = json_decode($event->location_meta))
                                                    @if (count($phoneCallNumber) > 0 && !empty($phoneCallNumber[1]))
                                                        @if (!empty($phoneCallNumber[2]) && $phoneCallNumber[1] == 2)
                                                            <i class="fa-solid fa-phone me-2 fs-2 text-primary"></i>
                                                            <span
                                                                class="fs-4 text-primary fw-bold">{{ $phoneCallNumber[2] }}</span>
                                                        @else
                                                            <i class="fa-solid fa-phone  me-2 fs-2 text-primary"></i>
                                                            <span
                                                                class="fs-4 text-primary fw-bold">{{ \App\Models\Event::LOCATION_ARRAY[$event->event_location] }}</span>
                                                        @endif
                                                    @endif
                                                @else
                                                    @php($location = json_decode($event->location_meta))
                                                    @if (!empty($location))
                                                        @if (count($location) > 0 && $location[0] == 3)
                                                            <img src="{{ asset('assets/images/logo_google_meet.svg') }}"
                                                                alt="logo" width="25px" height="25px">
                                                            <span
                                                                class="fs-4 text-primary fw-bold ms-2">{{ \App\Models\Event::LOCATION_ARRAY[$location[0]] }}</span>
                                                        @else
                                                            {{ __('messages.common.n/a') }}
                                                        @endif
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="d-flex align-items-start mb-4">
                                                <i class="far fa-calendar me-2 fs-2"
                                                    {{ $styleCss }}="color: #50cd89"></i>
                                                <span class="text-success fs-5 fw-bold">{{ $orgScheduleTime }}
                                                    ,<br>{{ \Carbon\Carbon::parse($scheduleDate)->dayName }},<br>{{ \Carbon\Carbon::parse($scheduleDate)->translatedFormat('jS M, Y') }}</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-4">
                                                <i class="fas fa-globe-americas me-2 fs-2 text-primary"></i>
                                                <span
                                                    class="fs-5 text-primary fw-bold">{{ \App\Models\User::TIME_ZONE_ARRAY[$event->user->timezone] }}</span>
                                            </div>
                                        </div>
                                        <div class="py-7 px-7 col-md-7 col-12">
                                            {{ Form::open(['id' => 'createEventForm']) }}
                                            <p class="fw-bold fs-3">{{ __('messages.enter_details') }}</p>
                                            {{ Form::hidden('user_id', $event->user->id) }}
                                            {{ Form::hidden('event_id', $event->id) }}
                                            {{ Form::hidden('schedule_date', $scheduleDate) }}
                                            {{ Form::hidden('slot_time', $orgScheduleTime) }}
                                            <div class="mb-5">
                                                {{ Form::label('name', __('messages.common.name') . ':', ['class' => 'required form-label']) }}
                                                {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('messages.common.name'), 'required']) }}
                                            </div>
                                            <div class="mb-5">
                                                {{ Form::label('email', __('messages.user.email') . ':', ['class' => 'required form-label']) }}
                                                {{ Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('messages.user.email'), 'required']) }}
                                            </div>
                                            <div class="mb-5">
                                                {{ Form::label('cpf', 'CPF' . ':', ['class' => 'required form-label']) }}
                                                {{ Form::text('cpf', null, ['class' => 'form-control', 'placeholder' => 'cpf', 'required']) }}
                                            </div>
                                            <p class="form-label">
                                                {{ __('messages.event.please_share_anything_that_will_help_prepare_for') }}
                                                .</p>
                                            <div class="d-flex flex-column mb-5">
                                                {{ Form::textarea('description', null, ['class' => 'form-control', 'placeholder' => __('messages.common.description'), 'rows' => 3]) }}
                                            </div>
                                            @php($checkedPhoneCall = json_decode($event->location_meta))
                                            @if (!empty($checkedPhoneCall))
                                                @if (count($checkedPhoneCall) > 0 && !empty($checkedPhoneCall[1]))
                                                    @if ($event->event_location == \App\Models\Event::PHONE_CALL && $checkedPhoneCall[1] == 1)
                                                        {{ Form::hidden('location_meta', null, ['id' => 'eventLocationPhoneCall']) }}
                                                        <div class="mb-5 mt-5">
                                                            {{ Form::label('name', __('messages.common.phone_number') . ':', ['class' => 'required form-label']) }}
                                                            <br>
                                                            {{ Form::tel('phone_call', null, ['class' => 'form-control', 'onkeyup' => 'if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,"")', 'id' => 'phoneNumber']) }}
                                                            {{ Form::hidden('region_code', null, ['id' => 'prefix_code']) }}
                                                            <span id="valid-msg"
                                                                class="text-success d-none fw-400 fs-small mt-2">{{ __('messages.placeholder.valid_number') }}</span>
                                                            <span id="error-msg"
                                                                class="text-danger d-none fw-400 fs-small mt-2"></span>
                                                        </div>
                                                    @endif
                                                @endif
                                            @endif
                                            {{ Form::tel('phone_call', null, ['class' => 'form-control', 'onkeyup' => 'if (/\D/g.test(this.value)) this.value = this.value.replace(/\D/g,"")', 'id' => 'phoneNumber']) }}
                                            @if ($event->event_type == \App\Models\Event::PAID && $event->payable_amount != 0)
                                                <div class="mb-5">
                                                    {{ Form::label('payment_type', __('messages.schedule_event.payment_type') . ':', ['class' => 'required form-label']) }}
                                                    {{ Form::select('payment_type', $paymentMethod, \App\Models\Subscription::TYPE_STRIPE, ['class' => 'form-select', 'required', 'id' => 'slotPaymentType', 'placeholder' => __('messages.placeholder.select_payment_method')]) }}
                                                </div>
                                            @endif
                                            <div class="mt-6">
                                                {{ Form::button(__('messages.event.schedule_event'), ['type' => 'submit', 'class' => 'btn btn-primary w-100', 'id' => 'slotPaymentSubmitBtn']) }}
                                            </div>
                                            {{ Form::close() }}
                                        </div>
                                    </div>
                                </div>
                                <div class="card d-none" id="pix-response">
                                    <div class="card-body">
                                        <h4 class="card-title text-center mb-4">Pix</h4>
                                        <div class="text-center">
                                            <img src="" width="150" alt="QR Code" id="qrcode"
                                                class="img-fluid mb-3">
                                        </div>
                                        <div class="text-center">
                                            <p id="copy" class="mb-2 bg-light p-2 rounded border"></p>
                                            <button class="btn btn-primary btn-sm" id="copia">COPIA</button>
                                            <span id="copied-message" class="text-success d-none ms-2">Código
                                                copiado!</span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#slotPaymentSubmitBtn').on('click', function(e) {
                    e.preventDefault();
                    let inputs = $('#createEventForm').serializeArray();
                    let data = {};
                    $.ajax({
                        url: '{{ route('scheduled-events.create') }}',
                        type: 'POST',
                        data: inputs,
                        beforeSend: function() {
                            $('#slotPaymentSubmitBtn').attr('disabled', true);
                            $('#slotPaymentSubmitBtn').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processando...');
                        },
                        success: function(response) {
                            console.log(response);
                            $('#pix-response').removeClass('d-none');
                            $('#qrcode').attr('src', response.qrCodeImage);
                            $('#copy').text(response.qrCode);
                            $('#createEventForm').addClass('d-none');
                            
                        },
                        error: function(response) {
                            $('#error-msg').text(response.responseJSON.message);
                            $('#error-msg').removeClass('d-none');
                            $('#slotPaymentSubmitBtn').attr('disabled', false);
                            $('#slotPaymentSubmitBtn').html('Agendar Evento');
                        }
                    });
                });
                $('#copia').on('click', function() {
                    let copyText = $('#copy').text();
                    let tempInput = document.createElement('input');
                    tempInput.value = copyText;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                    $('#copied-message').removeClass('d-none');
                    setTimeout(function() {
                        $('#copied-message').addClass('d-none');
                    }, 2000);
                });
            });
        </script>
    @endpush
