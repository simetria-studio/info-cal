<div class="col-xl-4 my-4">
    <div class="d-flex h-100">
        <div class="w-100 d-flex flex-column flex-center rounded-3 bg-light bg-opacity-75 py-15 px-10">
            <div class="mb-7 text-center">
                <h1 class="text-dark mb-5 fw-boldest">{{ $subscriptionsPricingPlan->name }}</h1>
                <div class="text-center">
                    <span class="mb-2 text-dark">{{ getSubscriptionPlanCurrencyIcon($subscriptionsPricingPlan->currency) }}</span>
                    <span class="">{{ number_format($subscriptionsPricingPlan->price) }}</span>
                    <span class="fs-7 fw-bold opacity-50">/{{ \App\Models\SubscriptionPlan::PLAN_TYPE[$subscriptionsPricingPlan->frequency] }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-center pricing-card__data mt-5">
                    <ul class="pl-0 list-style-none">
                        @if (isAuth() && count($subscriptionsPricingPlan->subscriptions) > 0)
                            @if(getSubscription()->trial_ends_at != null && getSubscription()->subscription_plan_id == $subscriptionsPricingPlan->id)
                                <li>
                                    <h4>{{ __('messages.subscription_plan.valid_until') }}
                                        : {{ $subscriptionsPricingPlan->trial_days }}
                                    </h4>
                                </li>
                            @endif
                            @php
                                $activeSubscription = getSubscription();
                            @endphp
                            @if($activeSubscription && isAuth() &&  $activeSubscription->subscriptionPlan->id == $subscriptionsPricingPlan->id)
                                <li>
                                    <h4>
                                        {{ __('messages.subscription_plan.end_date') }}
                                        :
                                        {{ getParseDate($activeSubscription->ends_at)->format('d-m-Y') }}
                                    </h4>
                                </li>
                            @endif
                        @endif
                    </ul>
                </div>
                <div class="separator mt-3"></div>
                <div class="d-flex align-items-center justify-content-center pricing-card__data mt-5">
                    <ul class="pl-0 list-style-none fs-4">
                        <li><i class="fas fa-check-double"></i> {{__('messages.events')}}
                            : {{ $subscriptionsPricingPlan->planFeature->events }}</li>
                        <li><i class="fas fa-check-double me-1"></i>{{__('messages.schedule_events')}}
                            : {{ $subscriptionsPricingPlan->planFeature->schedule_events }}</li>
                    </ul>
                </div>
            </div>

            @if($subscriptionsPricingPlan->id == currentActiveSubscription()->subscription_plan_id && !currentActiveSubscription()->isExpired())
                @if($subscriptionsPricingPlan->price != 0)
                    <button type="button"
                            class="btn btn-success rounded-pill mx-auto d-block cursor-remove-plan pricing-plan-button-active"
                            data-id="{{ $subscriptionsPricingPlan->id }}">
                        {{ __('messages.subscription_pricing_plans.currently_active') }}</button>
                @else
                    <button type="button" class="btn btn-info rounded-pill mx-auto d-block cursor-remove-plan">
                        {{ __('messages.subscription_pricing_plans.renew_free_plan') }}
                    </button>
                @endif
            @else
                @if(!currentActiveSubscription()->isExpired() && ($subscriptionsPricingPlan->price == 0 || $subscriptionsPricingPlan->price != 0))
                    @if($subscriptionsPricingPlan->hasZeroPlan->count() == 0)
                        <a href="{{ $subscriptionsPricingPlan->price != 0 ? route('choose.payment.type', $subscriptionsPricingPlan->id) : 'javascript:void(0)' }}"
                           class="btn btn-primary rounded-pill mx-auto d-block {{ $subscriptionsPricingPlan->price == 0 ? 'freePayment' : ''}}"
                           data-id="{{ $subscriptionsPricingPlan->id }}"
                           data-plan-price="{{ $subscriptionsPricingPlan->price }}" data-turbo="false">
                            {{ __('messages.subscription_pricing_plans.switch_plan') }}</a>
                    @else
                        <button type="button" class="btn btn-info rounded-pill mx-auto d-block cursor-remove-plan">
                            {{ __('messages.subscription_pricing_plans.renew_free_plan') }}
                        </button>
                    @endif
                @else
                    @if($subscriptionsPricingPlan->hasZeroPlan->count() == 0)
                        <a href="{{ $subscriptionsPricingPlan->price != 0 ? route('choose.payment.type', $subscriptionsPricingPlan->id) : 'javascript:void(0)' }}"
                           class="btn btn-primary rounded-pill mx-auto d-block {{ $subscriptionsPricingPlan->price == 0 ? 'freePayment' : ''}}"
                           data-id="{{ $subscriptionsPricingPlan->id }}"
                           data-plan-price="{{ $subscriptionsPricingPlan->price }}" data-turbo="false">
                            {{ __('messages.subscription_pricing_plans.choose_plan') }}</a>
                    @else
                        <button type="button" class="btn btn-info rounded-pill mx-auto d-block cursor-remove-plan">
                            {{ __('messages.subscription_pricing_plans.renew_free_plan') }}
                        </button>
                    @endif
                @endif
            @endif

        </div>
    </div>
</div>
