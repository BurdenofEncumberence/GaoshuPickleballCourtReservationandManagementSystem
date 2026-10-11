<?php

namespace App\Http\Controllers;

use App\Models\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('contact', [
            'siteSettings' => SiteSettings::first(),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:150'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $settings = SiteSettings::first();
        $to = $settings->email_address ?? 'support@gaoshou.ph';

        Mail::raw(
            "From: {$data['name']} <{$data['email']}>\n\n{$data['message']}",
            function ($mail) use ($to, $data) {
                $mail->to($to)
                     ->replyTo($data['email'], $data['name'])
                     ->subject('[Contact Desk] ' . $data['subject']);
            }
        );

        return back()->with('status', "Message sent! We'll get back to you soon.");
    }
}