<?php

namespace Admin\Http\Controllers;

use Admin\Models\NewsletterSubscriber;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'email' => $request->query('email'),
            'phone' => $request->query('phone'),
            'email_status' => $request->query('email_status'),
            'whatsapp_status' => $request->query('whatsapp_status'),
        ];

        $subscribers = NewsletterSubscriber::query()
            ->with('customer:id,name,email')
            ->when(filled($filters['email']), fn ($q) => $q->where('email', 'ilike', '%'.$filters['email'].'%'))
            ->when(filled($filters['phone']), fn ($q) => $q->where('phone', 'ilike', '%'.$filters['phone'].'%'))
            ->when(filled($filters['email_status']), fn ($q) => $q->where('email_status', $filters['email_status']))
            ->when(filled($filters['whatsapp_status']), fn ($q) => $q->where('whatsapp_status', $filters['whatsapp_status']))
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin::newsletter.subscribers.index', compact('subscribers', 'filters'));
    }
}
