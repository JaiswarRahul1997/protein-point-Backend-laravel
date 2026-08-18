<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Newsletter\NewsletterSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NewsletterController extends Controller
{
    public function subscribe(Request $request, NewsletterSubscriptionService $service): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:30'],
                'subscribe_email' => ['required', 'boolean'],
                'subscribe_whatsapp' => ['required', 'boolean'],
            ]);

            $result = $service->subscribe($data);

            return response()->json([
                'message' => $result['message'],
                'data' => [
                    'email_status' => $result['subscriber']->email_status,
                    'whatsapp_status' => $result['subscriber']->whatsapp_status,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Could not process your subscription. Please try again.',
            ], 500);
        }
    }

    public function confirmEmail(Request $request, NewsletterSubscriptionService $service): JsonResponse
    {
        try {
            $data = $request->validate(['token' => ['required', 'string', 'min:20']]);
            $subscriber = $service->confirmEmail($data['token']);

            return response()->json([
                'message' => 'Email subscription confirmed.',
                'data' => ['email_status' => $subscriber->email_status],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function unsubscribeEmail(Request $request, NewsletterSubscriptionService $service): JsonResponse
    {
        try {
            $data = $request->validate(['token' => ['required', 'string', 'min:20']]);
            $subscriber = $service->unsubscribeEmail($data['token']);

            return response()->json([
                'message' => 'You have been unsubscribed from email updates.',
                'data' => ['email_status' => $subscriber->email_status],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }

    public function unsubscribeWhatsApp(Request $request, NewsletterSubscriptionService $service): JsonResponse
    {
        try {
            $data = $request->validate(['token' => ['required', 'string', 'min:20']]);
            $subscriber = $service->unsubscribeWhatsApp($data['token']);

            return response()->json([
                'message' => 'You have been unsubscribed from WhatsApp updates.',
                'data' => ['whatsapp_status' => $subscriber->whatsapp_status],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        }
    }
}
